<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    public $timestamps = false;

    use HasFactory, Notifiable;

    protected $fillable = [
        'fname',
        'lname',
        'email',
        'password',
        'mobile',
        'company',
        'emailCode',
        'mobileCode',
        'isdcode',
        'emailMsg',
        'verifyMobile',
        'verifyEmail',
        'oauth_provider',
        'oauth_uid',
        'picture',
        'oauth_modified',
        'last_login',
        'login_type',
        'role',
        'status',
        'country',
        // ── Airwallex ──
        'airwallex_customer_id',
        'default_payment_method_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Relationships
    // ─────────────────────────────────────────────────────────────────────────

    public function orders()
    {
        return $this->hasMany(Order::class, 'userid');
    }

    /**
     * Saare verified saved cards
     */
    public function savedCards()
    {
        return $this->hasMany(PaymentProfileLog::class, 'userId')
            ->where('consent_status', 'VERIFIED')
            ->whereNotNull('payment_method_id')
            ->orderByDesc('is_default')
            ->orderByDesc('creationdate');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Default saved card row, ya null
     */
    public function defaultCard(): ?PaymentProfileLog
    {
        return $this->savedCards()->where('is_default', 1)->first();
    }

    /**
     * Koi bhi verified saved card hai?
     */
    public function hasSavedCard(): bool
    {
        return $this->savedCards()->exists();
    }

    /**
     * Full name helper
     */
    public function getFullNameAttribute(): string
    {
        return trim(($this->fname ?? '') . ' ' . ($this->lname ?? ''));
    }

    /**
     * Airwallex customer linked hai?
     */
    public function hasAirwallexCustomer(): bool
    {
        return !empty($this->airwallex_customer_id);
    }
}