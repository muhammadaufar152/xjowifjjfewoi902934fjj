<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_document',
        'nomor_document',
        'tanggal_terbit',
        'siklus_bisnis',
        'proses_bisnis',
        'business_process_owner',
        'jenis_document',
        'status',
        'version',
        'additional_file',
        'parent_id',
        'created_by',
    ];
    // Dokumen induk jika ini adalah versi revisi
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

}
