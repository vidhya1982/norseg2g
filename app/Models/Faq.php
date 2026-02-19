<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $table = 'faq';

    protected $fillable = [
        'q_en',
        'ans_en',
        'q_he',
        'ans_he',
        'q_tr',
        'ans_tr',
        'q_ar',
        'ans_ar',
        'status',
    ];

    public $timestamps = false;
}
