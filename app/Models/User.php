<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    public $timestamps = false;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
   protected $fillable = [
    'fname',
    'lname',
    'email',
    'password',

    // legacy required fields
    'mobile',
    'company',
    'emailCode',
    'mobileCode',
    'isdcode',
    'emailMsg',
    'verifyMobile',
    'verifyEmail',

    // social login
    'oauth_provider',
    'oauth_uid',
    'picture',
    'oauth_modified',
    'last_login',
    'login_type',

    // status
    'role',
    'status',
    'country',
];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function orders()
    {
        return $this->hasMany(Order::class, 'userid');
    }
}
