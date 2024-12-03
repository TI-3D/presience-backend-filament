<?php

namespace App\Filament\Lecturer\Resources\PresensiResource\Pages;

use App\Filament\Lecturer\Resources\PresensiResource;
use App\Filament\Lecturer\Resources\PresensiResource\Widgets\AttendanceCountWidget as WidgetsAttendanceCountWidget;
use App\Livewire\AttendanceAlphaTable;
use App\Livewire\AttendancePermissionTable;
use App\Livewire\AttendanceLateTable;
use App\Livewire\AttendanceTable;
use App\Models\Attendance;
use App\Models\ScheduleWeek;
use Carbon\Carbon;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Livewire;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\confirm;

class DetailPresensiPage extends Page
{
    // use InteractsWithTable;

    protected static string $resource = PresensiResource::class;

    protected static string $view = 'filament.lecturer-resource.detail';

    public $scheduleWeekId;

    public function mount()
    {
        $this->scheduleWeekId = request()->query('scheduleWeekId', null); // Default ke null jika tidak ada
    }

    protected static ?string $title = "Detail Presensi Mahasiswa";

    public function getHeaderWidgets(): array
    {
        return [
            WidgetsAttendanceCountWidget::make(['scheduleWeekId' => $this->scheduleWeekId]),
        ];
    }

    protected function getHeaderWidgetsData(): array
    {
        return [
            'scheduleWeekId' => $this->scheduleWeekId, // Pastikan scheduleWeekId diambil dari query string
        ];
    }



    public function getActions(): array
    {
        return [
            // dd('ok'),

            // Action::make('confirmAll')
            //     ->label('Konfirmasi Presensi')
            //     // ->requiresConfirmation()
            //     ->action(function () {
            //         dd('Function action dipanggil');
            //     })

            Action::make('confirmAll')
                ->label('Konfirmasi Presensi')
                ->color('primary')
                ->extraAttributes(['class' => 'ml-auto'])
                ->action(function () {
                    $this->performConfirmationAction();
                    // redirect()->route('filament.lecturer.resources.presensis.detail',);
                    redirect()->route('filament.lecturer.resources.presensis.index');
                })
                // ->requiresConfirmation()
                ->color(Color::Indigo) // Button color
            // ->disabled(fn(Model $record) => $record->status === 'closed') // Disable if already closed
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $courseTime = ScheduleWeek::where('id', $this->scheduleWeekId)
            ->with('schedule.course')  // Load relasi schedule dan course
            ->first()
            ->schedule->course->time;  // Ambil waktu dari course

        return $infolist
            ->schema([
                Tabs::make('Tabs')
                    ->contained(false)
                    ->tabs([
                        Tabs\Tab::make('attendace-late-table')->label('Terlambat')
                            ->schema([
                                Livewire::make(AttendanceLateTable::class)->key('attendance-izin-sakit-table')->data([
                                    'scheduleWeekId' => $this->scheduleWeekId,
                                    'courseTime' => $courseTime,
                                ])->lazy()
                            ]),
                        Tabs\Tab::make('attendance-izin-sakit-table')->label('Izin/Sakit')
                            ->schema([
                                Livewire::make(AttendancePermissionTable::class)->key('attendance-izin-sakit-table')->data([
                                    'scheduleWeekId' => $this->scheduleWeekId,
                                    'courseTime' => $courseTime,
                                ])->lazy()
                            ]),
                        Tabs\Tab::make('attendance-table')->label('Hadir')
                            ->schema([
                                Livewire::make(AttendanceTable::class)->key('attendance-table')->data([
                                    'scheduleWeekId' => $this->scheduleWeekId, // Pass variable to mount method
                                ])->lazy()
                            ]),
                        Tabs\Tab::make('attendance-alpha-table')->label('Alpha')
                            ->schema([
                                Livewire::make(AttendanceAlphaTable::class)->key('attendance-izin-sakit-table')->data([
                                    'scheduleWeekId' => $this->scheduleWeekId,
                                    'courseTime' => $courseTime,
                                ])->lazy()
                            ]),
                    ]),
            ]);
    }


    // PERLU DIUBAH
    public function performConfirmationAction()
    {
        try {
            // Get all attendance records for the given scheduleWeekId
            $attendances = Attendance::where('schedule_week_id', $this->scheduleWeekId)
                ->with('student') // Load relasi scheduleWeek
                ->get();
            // Update lecturer_verified and is_confirm in a transaction
            DB::transaction(function () use ($attendances) {
                foreach ($attendances as $attendance) {
                    DB::table('attendances')
                        ->where('student_id', $attendance->student_id)
                        ->where('schedule_week_id', $this->scheduleWeekId)
                        ->where('sakit', 0)
                        ->where('izin', 0)
                        ->update(['lecturer_verified' => true]);
                }

                $scheduleWeek = DB::table('schedule_weeks')
                    ->where('id', $this->scheduleWeekId)
                    ->update([ 'is_confirm' => true]);
            });

            // Send success notification
            Notification::make()
                ->title('Berhasil konfirmasi presensi untuk semua mahasiswa')
                ->success()
                ->send();
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }
}
