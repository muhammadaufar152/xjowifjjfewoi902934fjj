<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SirkulirFile extends Model
{
    protected $fillable = [
        'form_review_id',
        'uploaded_by',
        'uploaded_role',
        'path',
        'original_name',
        'mime',
        'size',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(FormReview::class, 'form_review_id');
    }
}
