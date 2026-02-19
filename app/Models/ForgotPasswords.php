<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForgotPasswords extends Model
{
    public $timestamps = false;

    protected $table = 'forgot_passwords';

    protected $fillable = [
        'user_id',
        'email',
        'token',
        'ip_address',
        'user_agent',
        'expires_at',
        'used_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
