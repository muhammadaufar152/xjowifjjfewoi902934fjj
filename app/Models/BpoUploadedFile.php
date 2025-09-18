<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BpoUploadedFile extends Model
{
    use HasFactory;

    protected $table = 'bpo_uploaded_files';

    protected $fillable = [
        'form_review_id',
        'path',
        'original_name',
        'keterangan',
        'uploaded_by',
        'uploaded_role',
    ];

    public function review()
    {
        return $this->belongsTo(FormReview::class, 'form_review_id');
    }
}
