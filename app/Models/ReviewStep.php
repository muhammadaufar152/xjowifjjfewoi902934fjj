<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewStep extends Model
{
    use HasFactory;

    public const STAGE_OFFICER = 'Officer';
    public const STAGE_MANAGER = 'Manager';
    public const STAGE_AVP     = 'AVP';

    public const STATUS_PENDING  = 'Pending';
    public const STATUS_APPROVED = 'Approved';
    public const STATUS_REJECTED = 'Rejected';

    protected $fillable = [
        'form_review_id',
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

    public function formReview()
    {
        return $this->belongsTo(FormReview::class, 'form_review_id');
    }

    public function actionItem()
    {
        return $this->belongsTo(ActionItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

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

    public static function orderedStages(): array
    {
        return [self::STAGE_OFFICER, self::STAGE_MANAGER, self::STAGE_AVP];
    }
}
