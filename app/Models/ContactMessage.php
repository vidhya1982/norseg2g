<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $table = 'contact_messages';

    protected $fillable = [
        'first_name',
        'last_name',
        'country_code',
        'phone',
        'email',
        'message',
        'ip_address',
    ];

    public $timestamps = false;
}
