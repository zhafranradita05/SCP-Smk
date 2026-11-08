<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Setting extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_schedule_locked'   => 'boolean',
        'cycle_start_date'     => 'date',
        'cycle_interval_weeks' => 'integer',
    ];

    /**
     * Accessor cerdas untuk active_cycle:
     * Jika cycle_mode == 'auto', hitung otomatis berdasarkan siklus 2 minggu.
     * Jika cycle_mode == 'manual', gunakan nilai aktif yang disimpan di database.
     */
    public function getActiveCycleAttribute($value): string
    {
        $mode = $this->attributes['cycle_mode'] ?? 'auto';

        if ($mode === 'manual') {
            return $value ?? 'putaran_1';
        }

        return $this->calculateCurrentCycle();
    }

    /**
     * Hitung putaran yang sedang aktif berdasarkan selisih minggu dari cycle_start_date.
     */
    public function calculateCurrentCycle(): string
    {
        $startDateVal = $this->attributes['cycle_start_date'] ?? null;
        if (empty($startDateVal)) {
            return $this->attributes['active_cycle'] ?? 'putaran_1';
        }

        try {
            $startDate = Carbon::parse($startDateVal, 'Asia/Jakarta')->startOfDay();
            $now = Carbon::now('Asia/Jakarta')->startOfDay();

            $intervalWeeks = max(1, (int)($this->attributes['cycle_interval_weeks'] ?? 2));
            $intervalDays = $intervalWeeks * 7;

            // Selisih hari dari tanggal acuan
            $diffDays = $startDate->diffInDays($now, false);

            // Indeks blok (setiap intervalDays hari = 1 blok putaran)
            $blockIndex = (int) floor($diffDays / $intervalDays);

            $startCycle = $this->attributes['cycle_start_cycle'] ?? 'putaran_1';
            $alternateCycle = ($startCycle === 'putaran_1') ? 'putaran_2' : 'putaran_1';

            // Jika blok genap -> siklus awal; jika blok ganjil -> siklus sebaliknya
            return (abs($blockIndex) % 2 === 0) ? $startCycle : $alternateCycle;
        } catch (\Throwable $e) {
            return $this->attributes['active_cycle'] ?? 'putaran_1';
        }
    }

    /**
     * Dapatkan rincian informasi tanggal pergantian putaran selanjutnya untuk Dashboard Waka.
     */
    public function getNextRotationDetails(): array
    {
        $mode = $this->attributes['cycle_mode'] ?? 'auto';
        $isAuto = ($mode === 'auto');
        $currentCycle = $this->active_cycle;
        $currentLabel = ($currentCycle === 'putaran_2') ? 'Putaran 2' : 'Putaran 1';
        $nextCycle = ($currentCycle === 'putaran_1') ? 'putaran_2' : 'putaran_1';
        $nextCycleLabel = ($nextCycle === 'putaran_2') ? 'Putaran 2' : 'Putaran 1';

        $startDateVal = $this->attributes['cycle_start_date'] ?? null;

        if (!$isAuto || empty($startDateVal)) {
            return [
                'is_auto'                 => false,
                'current_cycle'           => $currentCycle,
                'current_label'           => $currentLabel,
                'next_cycle'              => null,
                'next_cycle_label'        => null,
                'next_rotation_date'      => null,
                'next_rotation_formatted' => null,
                'days_remaining'          => null,
                'remaining_text'          => null,
                'info_text'               => 'Mode Manual (Tidak berputar otomatis)',
            ];
        }

        try {
            $startDate = Carbon::parse($startDateVal, 'Asia/Jakarta')->startOfDay();
            $now = Carbon::now('Asia/Jakarta')->startOfDay();

            $intervalWeeks = max(1, (int)($this->attributes['cycle_interval_weeks'] ?? 2));
            $intervalDays = $intervalWeeks * 7;

            $diffDays = $startDate->diffInDays($now, false);
            $blockIndex = (int) floor($diffDays / $intervalDays);

            // Awal blok siklus saat ini
            $currentBlockStart = $startDate->copy()->addDays($blockIndex * $intervalDays);
            // Awal blok siklus berikutnya (tanggal tepat pergantian putaran)
            $nextRotationDate = $currentBlockStart->copy()->addDays($intervalDays);

            $daysRemaining = (int) $now->diffInDays($nextRotationDate, false);

            $daysMap = [
                'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
            ];
            $monthsMap = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
                7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];

            $dayName = $daysMap[$nextRotationDate->format('l')] ?? $nextRotationDate->format('l');
            $monthName = $monthsMap[(int)$nextRotationDate->format('n')] ?? $nextRotationDate->format('F');
            $formattedDate = "{$dayName}, {$nextRotationDate->format('d')} {$monthName} {$nextRotationDate->format('Y')}";

            $remainingText = ($daysRemaining <= 0) ? 'Hari ini' : "{$daysRemaining} hari lagi";

            return [
                'is_auto'                 => true,
                'current_cycle'           => $currentCycle,
                'current_label'           => $currentLabel,
                'next_cycle'              => $nextCycle,
                'next_cycle_label'        => $nextCycleLabel,
                'next_rotation_date'      => $nextRotationDate->toDateString(),
                'next_rotation_formatted' => $formattedDate,
                'days_remaining'          => $daysRemaining,
                'remaining_text'          => $remainingText,
                'info_text'               => "Berganti ke {$nextCycleLabel} pada {$formattedDate} ({$remainingText})",
            ];
        } catch (\Throwable $e) {
            return [
                'is_auto'                 => false,
                'current_cycle'           => $currentCycle,
                'current_label'           => $currentLabel,
                'next_cycle'              => null,
                'next_cycle_label'        => null,
                'next_rotation_date'      => null,
                'next_rotation_formatted' => null,
                'days_remaining'          => null,
                'remaining_text'          => null,
                'info_text'               => 'Gagal menghitung kalender putaran',
            ];
        }
    }
}
