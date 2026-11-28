<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class GlbValidator
{
    public const MAX_KB = 30720;

    public static function assertValid(UploadedFile $file, string $field = 'file'): void
    {
        $fail = fn (string $message) => throw ValidationException::withMessages([$field => $message]);

        $handle = fopen($file->getRealPath(), 'rb');
        $header = fread($handle, 20);
        $size = $file->getSize();

        if (strlen($header) < 20) {
            fclose($handle);
            $fail('Berkas bukan model GLB yang valid.');
        }

        $magic = substr($header, 0, 4);
        ['version' => $version, 'length' => $length] = unpack('Vversion/Vlength', substr($header, 4, 8));
        ['chunkLength' => $chunkLength, 'chunkType' => $chunkType] = unpack('VchunkLength/VchunkType', substr($header, 12, 8));

        if ($magic !== 'glTF' || $version !== 2) {
            fclose($handle);
            $fail('Berkas harus model glTF 2.0 biner (.glb).');
        }
        if ($length !== $size) {
            fclose($handle);
            $fail('Ukuran berkas GLB tidak cocok dengan header (berkas rusak/terpotong).');
        }
        if ($chunkType !== 0x4E4F534A || $chunkLength <= 0 || $chunkLength > $size) {
            fclose($handle);
            $fail('Chunk JSON GLB tidak valid.');
        }

        $json = json_decode((string) fread($handle, $chunkLength), true);
        fclose($handle);
        if (! is_array($json)) {
            $fail('Isi JSON GLB tidak valid.');
        }

        foreach (['buffers', 'images'] as $key) {
            foreach ($json[$key] ?? [] as $item) {
                if (isset($item['uri']) && ! str_starts_with((string) $item['uri'], 'data:')) {
                    $fail('Model tidak boleh merujuk berkas/URL eksternal; ekspor sebagai .glb dengan tekstur tertanam.');
                }
            }
        }
    }
}
