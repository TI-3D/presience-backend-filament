<?php

namespace App\Livewire;

use App\Models\Attendance;
use Filament\Infolists\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
// use Filament\Tables\Actions\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Livewire\Component;
use Illuminate\Database\Eloquent\Model;

class AttendanceLateTable extends Component implements HasTable, HasForms
{

    use InteractsWithTable;
    use InteractsWithForms;

    public ?int $scheduleWeekId = null;
    public ?int $courseTime = null;

    public function mount($scheduleWeekId, $courseTime)
    {
        $this->scheduleWeekId = $scheduleWeekId;
        $this->courseTime = $courseTime;
    }

    public function table(Table $table): Table
    {
        $courseTime = $this->courseTime;
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.nim')
                    ->label('NIM')
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Nama')
                    ->sortable(),
                Tables\Columns\TextColumn::make('entry_time')
                    ->label('Waktu Presensi')
                    ->dateTime('H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('late')
                    ->label('Terlambat')
                    ->getStateUsing(function (Model $record) {
                        // Extract values from the 3 columns
                        $values = [];
                        if ($record->sakit > 0) {
                            $values[] = $record->sakit;
                        }
                        if ($record->izin > 0) {
                            $values[] = $record->izin;
                        }
                        if ($record->alpha > 0) {
                            $values[] = $record->alpha;
                        }

                        // Return the combined values as a comma-separated string
                        return !empty($values) ? implode(', ', $values) : '-';
                    })
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status Terlambat')
                    ->sortable()
                    ->getStateUsing(function (Model $record) {
                        // Assuming you have 'sakit', 'izin', and 'alpha' columns in the attendance table
                        $statuses = [];

                        if ($record->sakit > 0) {
                            $statuses[] = 'Sakit';
                        }

                        if ($record->izin > 0) {
                            $statuses[] = 'Izin';
                        }

                        if ($record->alpha > 0) {
                            $statuses[] = 'Alpha';
                        }

                        // Join the statuses to display them together, separated by commas
                        return !empty($statuses) ? implode(', ', $statuses) : 'Hadir';
                    })->colors([
                        'danger' => fn($state) => str_contains($state, 'Alpha'), // Red for Alpha
                        'warning' => fn($state) => str_contains($state, 'Sakit') || str_contains($state, 'Izin'), // Yellow for Sakit/Izin
                        'success' => fn($state) => $state === 'Hadir', // Green for Hadir
                    ]),

            ])
            ->actions([
                // You may add these actions to your table if you're using a simple
                // resource, or you just want to be able to delete records without
                // leaving the table.
                Tables\Actions\Action::make('lihat')
                    ->label('Lihat') // Label of the button
                    ->icon('heroicon-o-eye') // Optional: You can use an icon from Heroicons
                    ->color('gray') // Optional: Set the color of the button
                    ->tooltip('Detail') // Optional: Tooltip when hovering over the button
                    ->modalWidth("Medium")
                    ->modalHeading('Perizinan')
                    ->infolist(
                        function (Model $record) {
                            // dd($record->student);
                            return [
                                Section::make()
                                    ->columns([
                                        'sm' => 1,
                                        'lg' => 3,
                                    ])
                                    ->schema([
                                        TextEntry::make('nim')
                                            ->label('NIM')
                                            ->default(fn() => $record->student->nim)->columnSpan([
                                                'sm' => 1,
                                                'lg' => 1,
                                            ]),

                                        TextEntry::make('name')
                                            ->label('Nama')
                                            ->default(fn() => $record->student->name)->columnSpan([
                                                'sm' => 1,
                                                'lg' => 1,
                                            ]),
                                        TextEntry::make('class')
                                            ->label('Kelas')
                                            ->default(fn() => $record->scheduleWeek->schedule->group->name)->columnSpan([
                                                'sm' => 1,
                                                'lg' => 1,
                                            ]),
                                    ])
                            ];
                        }
                    )
            ])
            ->query(fn() => Attendance::where('schedule_week_id', $this->scheduleWeekId)
                ->where(function ($query) use ($courseTime) {
                    $query->whereBetween('sakit', [1, $courseTime - 1])
                        ->orWhereBetween('izin', [1, $courseTime - 1])
                        ->orWhereBetween('alpha', [1, $courseTime - 1]);
                })
                ->with('student'));
    }

    public function render()
    {
        return view('livewire.attendance-late-table');
    }
}
