<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\GeoZone;
use App\Models\Notification;
use App\Models\TrackingDevice;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->orderBy('user_id')->get();
        $animals = Animal::query()->orderBy('animal_id')->get();
        $zones = GeoZone::query()->orderBy('zone_id')->get();
        $devices = TrackingDevice::query()->orderBy('device_id')->get();

        $templates = [
            ['title' => 'Zona Berisiko', 'message' => 'Satwa terdeteksi berada di dalam zona berisiko.'],
            ['title' => 'Baterai Rendah', 'message' => 'Level baterai perangkat pelacak berada di bawah ambang batas.'],
            ['title' => 'Perangkat Bermasalah', 'message' => 'Sinyal perangkat terputus selama lebih dari satu siklus pengiriman.'],
            ['title' => 'Peringatan Sistem', 'message' => 'Pemeriksaan terjadwal perangkat pelacak telah selesai.'],
            ['title' => 'Zona Berisiko', 'message' => 'Aktivitas satwa terdeteksi di dekat wilayah permukiman.'],
        ];

        foreach ($templates as $index => $template) {
            $user = $users->get($index % max($users->count(), 1));
            $animal = $animals->get($index % max($animals->count(), 1));
            $zone = $zones->get($index % max($zones->count(), 1));
            $device = $devices->get($index % max($devices->count(), 1));

            Notification::query()->updateOrCreate(
                [
                    'user_id' => $user->user_id,
                    'title' => $template['title'],
                    'message' => $template['message'],
                ],
                [
                    'animal_id' => $animal?->animal_id,
                    'zone_id' => $zone?->zone_id,
                    'device_id' => $device?->device_id,
                    'is_read' => $index % 2 === 0,
                ],
            );
        }
    }
}
