<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Incident extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'code', 'title', 'description', 'category', 'severity', 'status',
        'assignee_id', 'reporter_id', 'detected_at', 'opened_at',
        'contained_at', 'closed_at', 'root_cause', 'resolution',
    ];

    protected function casts(): array
    {
        return [
            'detected_at' => 'datetime',
            'opened_at' => 'datetime',
            'contained_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Incident $incident) {
            if (empty($incident->code)) {
                $year = now()->year;
                $count = static::withTrashed()->whereYear('created_at', $year)->count() + 1;
                $incident->code = sprintf('INC-%d-%04d', $year, $count);
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function alerts(): BelongsToMany
    {
        return $this->belongsToMany(Alert::class, 'incident_alert');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(IncidentNote::class)->latest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(IncidentAttachment::class);
    }
}
