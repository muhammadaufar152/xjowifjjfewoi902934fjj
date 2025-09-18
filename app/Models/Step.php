<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Step extends Model
{
    protected $fillable = [
        'form_review_id',
        'tahapan',
        'status',
        'verifikator',
        'tanggal',
        'keterangan',
    ];
}
