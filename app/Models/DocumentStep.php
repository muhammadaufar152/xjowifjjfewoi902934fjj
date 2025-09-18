<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentStep extends Model
{
    use HasFactory;

    // --- Konstanta Tahapan ---
    public const STAGE_OFFICER = 'Officer';
    public const STAGE_MANAGER = 'Manager';
    public const STAGE_AVP     = 'AVP';

    // --- Konstanta Status ---
    public const STATUS_PENDING  = 'Pending';
    public const STATUS_APPROVED = 'Approved';
    public const STATUS_REJECTED = 'Rejected';

    protected $fillable = [
        'document_id',
        'action_item_id',
        'tahapan',
        'status',
        'keterangan',
        'verifikator',
        'tanggal',
        'user_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    // --- Relasi ---
    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function actionItem()
    {
        return $this->belongsTo(ActionItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // --- Scopes ---
    public function scopeForAI($q, int $aiId)
    {
        return $q->where('action_item_id', $aiId);
    }

    public function scopePending($q)
    {
        return $q->where('status', self::STATUS_PENDING);
    }

    public function scopeNotApproved($q)
    {
        return $q->where('status', '!=', self::STATUS_APPROVED);
    }

    public function scopeOrdered($q)
    {
        return $q->orderByRaw("FIELD(tahapan, 'Officer','Manager','AVP')");
    }

    // --- Helper untuk ambil urutan tahapan ---
    public static function orderedStages(): array
    {
        return [self::STAGE_OFFICER, self::STAGE_MANAGER, self::STAGE_AVP];
    }
}
