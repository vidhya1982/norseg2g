<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ICCID extends Model
{
      protected $table = 'ICCID';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'ICCID',
        'Camel_IMSI',
        'Camel_MSISDN',
        'LPA_Value',
        'status',
    ];
}
