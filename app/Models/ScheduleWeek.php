<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleWeek extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'date',
        'is_online',
        'status',
        'opened_at',
        'week_id',
        'schedule_id',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'date' => 'date',
        'is_online' => 'boolean',
        'week_id' => 'integer',
        'schedule_id' => 'integer',
    ];

    protected function cast() : array {
        return[
            'is_online' => 'boolean'
        ];
    }

    public function week(): BelongsTo
    {
        return $this->belongsTo(Week::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function permissionDetails(): HasMany
    {
        return $this->hasMany(PermitDetail::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
