<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessProcessOwner extends Model
{
    protected $connection = 'mysql_sipatra'; // penting! database kedua
    protected $table = 'm_organisasi';    // nama tabel
    protected $primaryKey = 'id';           // atau key yang sesuai
    public $timestamps = false;             // kalau tabel tidak ada created_at/updated_at
}
