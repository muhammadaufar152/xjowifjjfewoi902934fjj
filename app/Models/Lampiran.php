<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lampiran extends Model
{
    protected $fillable = ['action_item_id','path','uploaded_by'];

    public function actionItem()
    {
        return $this->belongsTo(ActionItem::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
