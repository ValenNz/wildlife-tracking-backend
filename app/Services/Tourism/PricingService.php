<?php

namespace App\Services\Tourism;

use App\Models\PublicHoliday;
use App\Models\TicketPrice;
use App\Models\TourRoute;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class PricingService
{
    public function dayType(CarbonInterface $date): string
    {
        if ($date->isSunday()) {
            return 'holiday';
        }

        return PublicHoliday::whereDate('holiday_date', $date->toDateString())->exists() ? 'holiday' : 'weekday';
    }

    public function quote(TourRoute $route, string $visitDate, int $wni, int $wna, int $students, int $drones = 0, int $porters = 0): array
    {
        $date = Carbon::parse($visitDate);
        $dayType = $this->dayType($date);
        $days = max(1, (int) $route->duration_days);
        $participants = $wni + $wna + $students;

        if ($participants < 1) {
            throw ValidationException::withMessages(['participants' => 'Minimal satu peserta.']);
        }

        $lines = [];
        $add = function (string $code, int $quantity, int $multiplierDays) use (&$lines) {
            if ($quantity <= 0) {
                return;
            }
            $price = TicketPrice::where('code', $code)->where('is_active', true)->first();
            if (! $price) {
                throw ValidationException::withMessages(['price' => "Tarif {$code} belum diatur. Hubungi pengelola."]);
            }
            if ($quantity < $price->min_quantity) {
                throw ValidationException::withMessages([
                    'student_count' => "Tarif {$price->name} berlaku untuk minimal {$price->min_quantity} orang.",
                ]);
            }
            $lines[] = [
                'code' => $price->code,
                'label' => $price->name,
                'quantity' => $quantity,
                'unit_price' => $price->amount,
                'days' => $multiplierDays,
                'subtotal' => $quantity * $price->amount * $multiplierDays,
            ];
        };

        $add("entry_wni_{$dayType}", $wni, $days);
        $add("entry_student_{$dayType}", $students, $days);
        $add('entry_wna', $wna, $days);
        $add('activity_drone', $drones, $days);

        $lines[] = [
            'code' => 'guide_fee',
            'label' => "Jasa pemandu & paket {$route->name}",
            'quantity' => $participants,
            'unit_price' => (int) $route->guide_fee_per_person,
            'days' => 1,
            'subtotal' => $participants * (int) $route->guide_fee_per_person,
        ];

        if ($porters > 0) {
            $porterFee = max(0, (int) ($route->porter_fee_per_day ?? 150000));
            $lines[] = [
                'code' => 'addon_porter',
                'label' => "Tambahan Jasa Porter ({$route->name})",
                'quantity' => $porters,
                'unit_price' => $porterFee,
                'days' => $days,
                'subtotal' => $porters * $porterFee * $days,
            ];
        }

        return [
            'day_type' => $dayType,
            'lines' => $lines,
            'total' => array_sum(array_column($lines, 'subtotal')),
        ];
    }
}
