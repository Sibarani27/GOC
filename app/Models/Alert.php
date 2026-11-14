<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Alert extends Model
{
    use HasFactory;

    public const SEVERITIES = ['low', 'medium', 'high', 'critical'];
    public const STATUSES = ['new', 'acknowledged', 'in_progress', 'resolved', 'false_positive'];

    protected $fillable = [
        'source', 'external_id', 'title', 'description', 'severity', 'status',
        'asset_id', 'source_ip', 'destination_ip', 'mitre_tactic', 'mitre_technique',
        'fingerprint', 'occurrences', 'detected_at', 'acknowledged_by', 'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'detected_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function incidents(): BelongsToMany
    {
        return $this->belongsToMany(Incident::class, 'incident_alert');
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', ['resolved', 'false_positive']);
    }
}
