<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActionItem extends Model
{
    protected $table = 'action_items';

    protected $fillable = [
        'nama_dokumen',
        'no_dokumen',
        'action_item',
        'bpo_uic',
        'target',
        'status',
        'keterangan',
        'lampiran',
        'no_fr',
        'is_upload_locked',
        'upload_lock_reason',
        'upload_locked_by',
        'upload_locked_at',
    ];

    protected $casts = [
        'target'            => 'date',
        'is_upload_locked'  => 'boolean',
        'upload_locked_at'  => 'datetime',
    ];

    public const ST_OPEN       = 'Open';
    public const ST_PROGRESS   = 'Progress';
    public const ST_REQ_CLOSE  = 'Request Close';
    public const ST_CLOSED     = 'Closed';
    public const ST_PENDING    = 'Pending';
    public const ST_CANCELLED  = 'Cancelled';

    public static function statuses(): array
    {
        return [
            self::ST_OPEN,
            self::ST_PROGRESS,
            self::ST_PENDING,
            self::ST_REQ_CLOSE,
            self::ST_CLOSED,
            self::ST_CANCELLED,
        ];
    }

    public function reviewSteps(): HasMany
    {
        return $this->hasMany(\App\Models\ReviewStep::class)->ordered();
    }

    public function allStepsApproved(): bool
    {
        return ! $this->reviewSteps()
            ->where('status', '!=', 'Approved')
            ->exists();
    }

    public function lampirans(): HasMany
    {
        return $this->hasMany(\App\Models\Lampiran::class);
    }

    public function uploadLocker(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'upload_locked_by');
    }

    public function getUploadLockedInfoAttribute(): ?string
    {
        if (! $this->is_upload_locked) {
            return null;
        }

        $by   = $this->uploadLocker?->name ?? 'unknown';
        $time = optional($this->upload_locked_at)->format('d-m-Y H:i');

        return trim("Dikunci oleh {$by}" . ($time ? " pada {$time}" : ''));
    }

    public function lockUploads(int $byUserId, ?string $reason = null): void
    {
        $this->forceFill([
            'is_upload_locked'   => true,
            'upload_lock_reason' => $reason,
            'upload_locked_by'   => $byUserId,
            'upload_locked_at'   => now(),
        ])->save();
    }

    public function resumeUploads(): void
    {
        $this->forceFill([
            'is_upload_locked'   => false,
            'upload_lock_reason' => null,
            'upload_locked_by'   => null,
            'upload_locked_at'   => null,
        ])->save();
    }

    public function uploadIsBlocked(): bool
    {
        return (bool) $this->is_upload_locked;
    }
}
