<?php

namespace App\Filament\Lecturer\Resources\PresensiResource\Widgets;

use App\Models\Attendance;
use App\Models\ScheduleWeek;
use Filament\Forms\Components\Card;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AttendanceCountWidget extends BaseWidget
{

    public ?int $scheduleWeekId = null;

    public function mount($scheduleWeekId)
    {
        // dd($scheduleWeekId);
        $this->scheduleWeekId = $scheduleWeekId;
    }

    protected function getCards(): array
    {
        $courseTime = ScheduleWeek::where('id', $this->scheduleWeekId)
            ->with('schedule.course')  // Load relasi schedule dan course
            ->first()
            ->schedule->course->time;  // Ambil waktu dari course

        $alphaCount = Attendance::where('schedule_week_id', $this->scheduleWeekId)->where(function ($query) use ($courseTime) {
            $query->where('alpha', '=', $courseTime);
        })->count();
        $sakitIzinCount = Attendance::where('schedule_week_id', $this->scheduleWeekId)
            ->where(function ($query) use ($courseTime) {
                $query->where('sakit', '=', $courseTime)
                    ->orWhere('izin', '=', $courseTime);
            })->count();
        $hadirCount = Attendance::where('schedule_week_id', $this->scheduleWeekId)
            ->where('sakit', 0)
            ->where('izin', 0)
            ->where('alpha', 0)
            ->count();
        $lateCount = Attendance::where('schedule_week_id', $this->scheduleWeekId)
            ->where(function ($query) use ($courseTime) {
                $query->whereBetween('sakit', [1, $courseTime - 1])
                    ->orWhereBetween('izin', [1, $courseTime - 1])
                    ->orWhereBetween('alpha', [1, $courseTime - 1]);
            })
            ->count();
        return [
            Stat::make('Mahasiswa Terlambat', $lateCount),
            Stat::make('Mahasiswa Sakit/Izin', $sakitIzinCount),
            Stat::make('Mahasiswa Hadir', $hadirCount),
            Stat::make('Mahasiswa Alpha', $alphaCount),

        ];
    }
}
