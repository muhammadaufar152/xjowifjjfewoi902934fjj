<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_document',
        'nomor_document',
        'tanggal_terbit',
        'business_cycle_id',
        'business_process_id',
        'document_type_id',
        'status',
        'version',
        'additional_file',
        'parent_id',
        'created_by',
    ];

    // --- Konstanta Status (mirip ActionItem) ---
    public const ST_DRAFT      = 'Draft';
    public const ST_PROGRESS   = 'Progress';
    public const ST_REQ_CLOSE  = 'Request Close';
    public const ST_CLOSED     = 'Closed';
    public const ST_REJECTED   = 'Rejected';
    public const ST_CANCELLED  = 'Cancelled';

    public static function statuses(): array
    {
        return [
            self::ST_DRAFT,
            self::ST_PROGRESS,
            self::ST_REQ_CLOSE,
            self::ST_CLOSED,
            self::ST_REJECTED,
            self::ST_CANCELLED,
        ];
    }

    // --- Relasi ---
    public function parent()
    {
        return $this->belongsTo(Document::class, 'parent_id');
    }

    public function relatedVersions()
    {
        return $this->hasMany(Document::class, 'parent_id');
    }

    public function downloads()
    {
        return $this->hasMany(DocumentDownload::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relasi ke DocumentStep (mirip ActionItem::reviewSteps)
    public function documentSteps(): HasMany
    {
        return $this->hasMany(DocumentStep::class)->ordered();
    }

    // --- Helper untuk cek apakah semua step sudah disetujui ---
    public function allStepsApproved(): bool
    {
        return ! $this->documentSteps()
            ->where('status', '!=', DocumentStep::STATUS_APPROVED)
            ->exists();
    }

    // --- Scopes ---
    public function scopeOnlyLatest(Builder $q): Builder
    {
        return $q->whereIn('id', function ($sub) {
            $sub->select(DB::raw('MAX(id)'))
                ->from('documents')
                ->groupBy(DB::raw('COALESCE(parent_id, id)'));
        });
    }

    public function scopeOwnedBy(Builder $q, int $userId): Builder
    {
        return $q->where('user_id', $userId);
    }

    public function scopeOwnedByGuessing(Builder $q, string $userName, string $userEmail): Builder
    {
        return $q->where(function ($w) use ($userName, $userEmail) {
            $w->where('bpo_uic', 'like', "%{$userName}%")
              ->orWhere('bpo_uic', 'like', "%{$userEmail}%");
        });
    }
    
    // Relasi many-to-many ke BusinessProcessOwner
    public function businessProcessOwners(): BelongsToMany
    {
        return $this->belongsToMany(BusinessProcessOwner::class, 'document_business_process_owner', 'document_id', 'business_process_owner_id');
    }

    public function businessCycle()
    {
        return $this->belongsTo(
            related: BusinessCycle::class,
            foreignKey: 'business_cycle_id',
            ownerKey: 'id'
        );
    }

    public function businessProcess()
    {
        return $this->belongsTo(
            related: BusinessProcess::class,
            foreignKey: 'business_process_id',
            ownerKey: 'id'
        );
    }
    
    public function documentType()
    {
        return $this->belongsTo(
            related: DocumentType::class,
            foreignKey: 'document_type_id',
            ownerKey: 'id'
        );
    }
}
