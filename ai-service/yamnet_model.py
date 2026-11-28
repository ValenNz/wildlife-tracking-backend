# Copyright 2019 The TensorFlow Authors All Rights Reserved.
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
# ==============================================================================

import csv
from dataclasses import dataclass

import numpy as np
import tensorflow as tf
from tf_keras import Model, layers


@dataclass(frozen=True)
class Params:
    sample_rate: float = 16000.0
    stft_window_seconds: float = 0.025
    stft_hop_seconds: float = 0.010
    mel_bands: int = 64
    mel_min_hz: float = 125.0
    mel_max_hz: float = 7500.0
    log_offset: float = 0.001
    patch_window_seconds: float = 0.96
    patch_hop_seconds: float = 0.48
    num_classes: int = 521
    conv_padding: str = "same"
    batchnorm_center: bool = True
    batchnorm_scale: bool = False
    batchnorm_epsilon: float = 1e-4
    classifier_activation: str = "sigmoid"

    @property
    def patch_frames(self):
        return int(round(self.patch_window_seconds / self.stft_hop_seconds))

    @property
    def patch_bands(self):
        return self.mel_bands


def _log_mel_patches(waveform, params):
    window = int(round(params.sample_rate * params.stft_window_seconds))
    hop = int(round(params.sample_rate * params.stft_hop_seconds))
    fft_length = 2 ** int(np.ceil(np.log(window) / np.log(2.0)))
    magnitude = tf.abs(tf.signal.stft(signals=waveform, frame_length=window, frame_step=hop, fft_length=fft_length))
    mel_matrix = tf.signal.linear_to_mel_weight_matrix(
        num_mel_bins=params.mel_bands,
        num_spectrogram_bins=fft_length // 2 + 1,
        sample_rate=params.sample_rate,
        lower_edge_hertz=params.mel_min_hz,
        upper_edge_hertz=params.mel_max_hz,
    )
    log_mel = tf.math.log(tf.matmul(magnitude, mel_matrix) + params.log_offset)

    spectrogram_rate = params.sample_rate / hop
    patch_window = int(round(spectrogram_rate * params.patch_window_seconds))
    patch_hop = int(round(spectrogram_rate * params.patch_hop_seconds))
    patches = tf.signal.frame(signal=log_mel, frame_length=patch_window, frame_step=patch_hop, axis=0)
    return log_mel, patches


def _pad_waveform(waveform, params):
    min_seconds = params.patch_window_seconds + params.stft_window_seconds - params.stft_hop_seconds
    min_samples = tf.cast(min_seconds * params.sample_rate, tf.int32)
    num_samples = tf.shape(waveform)[0]
    padding = tf.maximum(0, min_samples - num_samples)

    num_samples = tf.maximum(num_samples, min_samples)
    after_first = num_samples - min_samples
    hop_samples = tf.cast(params.patch_hop_seconds * params.sample_rate, tf.int32)
    hops = tf.cast(tf.math.ceil(tf.cast(after_first, tf.float32) / tf.cast(hop_samples, tf.float32)), tf.int32)
    padding += hop_samples * hops - after_first

    return tf.pad(waveform, [[0, padding]], mode="CONSTANT", constant_values=0.0)


def _batch_norm(name, params):
    return lambda x: layers.BatchNormalization(
        name=name, center=params.batchnorm_center, scale=params.batchnorm_scale, epsilon=params.batchnorm_epsilon
    )(x)


def _conv(name, kernel, stride, filters, params):
    def build(x):
        x = layers.Conv2D(name=f"{name}/conv", filters=filters, kernel_size=kernel, strides=stride,
                          padding=params.conv_padding, use_bias=False, activation=None)(x)
        x = _batch_norm(f"{name}/conv/bn", params)(x)
        return layers.ReLU(name=f"{name}/relu")(x)
    return build


def _separable_conv(name, kernel, stride, filters, params):
    def build(x):
        x = layers.DepthwiseConv2D(name=f"{name}/depthwise_conv", kernel_size=kernel, strides=stride,
                                   depth_multiplier=1, padding=params.conv_padding, use_bias=False,
                                   activation=None)(x)
        x = _batch_norm(f"{name}/depthwise_conv/bn", params)(x)
        x = layers.ReLU(name=f"{name}/depthwise_conv/relu")(x)
        x = layers.Conv2D(name=f"{name}/pointwise_conv", filters=filters, kernel_size=(1, 1), strides=1,
                          padding=params.conv_padding, use_bias=False, activation=None)(x)
        x = _batch_norm(f"{name}/pointwise_conv/bn", params)(x)
        return layers.ReLU(name=f"{name}/pointwise_conv/relu")(x)
    return build


_LAYERS = [
    (_conv, [3, 3], 2, 32),
    (_separable_conv, [3, 3], 1, 64),
    (_separable_conv, [3, 3], 2, 128),
    (_separable_conv, [3, 3], 1, 128),
    (_separable_conv, [3, 3], 2, 256),
    (_separable_conv, [3, 3], 1, 256),
    (_separable_conv, [3, 3], 2, 512),
    (_separable_conv, [3, 3], 1, 512),
    (_separable_conv, [3, 3], 1, 512),
    (_separable_conv, [3, 3], 1, 512),
    (_separable_conv, [3, 3], 1, 512),
    (_separable_conv, [3, 3], 1, 512),
    (_separable_conv, [3, 3], 2, 1024),
    (_separable_conv, [3, 3], 1, 1024),
]


def _frames_model(params):
    waveform = layers.Input(batch_shape=(None,), dtype=tf.float32)
    log_mel, patches = _log_mel_patches(_pad_waveform(waveform, params), params)
    net = layers.Reshape((params.patch_frames, params.patch_bands, 1),
                         input_shape=(params.patch_frames, params.patch_bands))(patches)
    for i, (build, kernel, stride, filters) in enumerate(_LAYERS):
        net = build(f"layer{i + 1}", kernel, stride, filters, params)(net)
    embeddings = layers.GlobalAveragePooling2D()(net)
    logits = layers.Dense(units=params.num_classes, use_bias=True)(embeddings)
    predictions = layers.Activation(activation=params.classifier_activation)(logits)
    return Model(name="yamnet_frames", inputs=waveform, outputs=[predictions, embeddings, log_mel])


def load_class_names(class_map_csv: str) -> list[str]:
    with open(class_map_csv, encoding="utf-8") as f:
        reader = csv.reader(f)
        next(reader)
        return [row[2] for row in reader]


class YamnetModel:
    def __init__(self, weights_path: str, class_map_csv: str):
        self.params = Params()
        self.model = _frames_model(self.params)
        self.model.load_weights(weights_path)
        self.class_names = load_class_names(class_map_csv)
        if len(self.class_names) != self.params.num_classes:
            raise ValueError(f"Class map berisi {len(self.class_names)} kelas, seharusnya {self.params.num_classes}.")

    @property
    def sample_rate(self) -> int:
        return int(self.params.sample_rate)

    @property
    def frame_hop_seconds(self) -> float:
        return self.params.patch_hop_seconds

    def scores(self, waveform: np.ndarray) -> np.ndarray:
        return self.analyze(waveform)[0]

    def analyze(self, waveform: np.ndarray) -> tuple[np.ndarray, np.ndarray]:
        scores, embeddings, _ = self.model(np.asarray(waveform, dtype=np.float32))
        return scores.numpy(), embeddings.numpy()
