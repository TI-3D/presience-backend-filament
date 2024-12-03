<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sakit',
        'izin',
        'alpha',
        'entry_time',
        'is_changed',
        'lecturer_verified',
        'student_id',
        'schedule_week_id',
        'is_confirm'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'is_changed' => 'boolean',
        'lecturer_verified' => 'boolean',
        'student_id' => 'integer',
        'schedule_week_id' => 'integer',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scheduleWeek(): BelongsTo
    {
        return $this->belongsTo(ScheduleWeek::class);
    }
}
