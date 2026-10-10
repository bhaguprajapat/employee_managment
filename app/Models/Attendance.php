<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
     use HasFactory;
    // Explicit table name: Laravel would otherwise pluralise this to "attendances".
    protected $table = 'attendance';

    protected $fillable = [
        'employee_id',
        'attendance_date',
        'check_in_at',
        'check_out_at',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Shared filter logic for the web and API attendance lists.
     * Employees are always restricted to their own rows, whatever filters they send.
     *
     * @param  array{employee_id?: int|string|null, date?: string|null, from?: string|null, to?: string|null}  $filters
     */
    public function scopeForFilters(Builder $query, array $filters, User $user): Builder
    {
        if ($user->isEmployee()) {
            // No linked employee record means no rows at all, never "everyone".
            $query->where('employee_id', $user->employee?->id ?? 0);
        } elseif (! empty($filters['employee_id'])) {
            $query->where('employee_id', (int) $filters['employee_id']);
        }

        if (! empty($filters['date'])) {
            $query->whereDate('attendance_date', $filters['date']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('attendance_date', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('attendance_date', '<=', $filters['to']);
        }

        return $query;
    }

    /**
     * Whole minutes between check-in and check-out, or null while still checked in.
     */
    public function workingMinutes(): ?int
    {
        if ($this->check_out_at === null) {
            return null;
        }

        return (int) $this->check_in_at->diffInMinutes($this->check_out_at);
    }

    /**
     * Human readable working hours, e.g. "7h 45m", or null while still checked in.
     */
    public function workingHoursLabel(): ?string
    {
        $minutes = $this->workingMinutes();

        if ($minutes === null) {
            return null;
        }

        return sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60);
    }
}
