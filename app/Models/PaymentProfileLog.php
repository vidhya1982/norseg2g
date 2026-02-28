<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentProfileLog extends Model
{
    protected $table      = 'payment_profile_log';
    protected $primaryKey = 'id';
    public $timestamps    = false;

    protected $fillable = [
        'userId',
        'profileId',
        'paymentProfileId',
        'orderId',
        'order_total',
        'creationdate',
        // ── Airwallex saved card ──
        'payment_method_id',
        'consent_id',
        'consent_status',
        'brand',
        'last4',
        'expiry_month',
        'expiry_year',
        'is_default',
    ];

    protected $casts = [
        'order_total'  => 'float',
        'creationdate' => 'datetime',
        'is_default'   => 'boolean',
        'expiry_month' => 'integer',
        'expiry_year'  => 'integer',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    //  Relationships
    // ─────────────────────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function initiatedOrder()
    {
        return $this->belongsTo(OrdersInitiated::class, 'orderId');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Accessors
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Human-readable: "Visa •••• 4242 (05/2027)"
     */
    public function getDisplayLabelAttribute(): string
    {
        if ($this->payment_method_id && $this->last4) {
            $brand  = ucfirst($this->brand ?? 'Card');
            $last4  = '•••• ' . $this->last4;
            $expiry = ($this->expiry_month && $this->expiry_year)
                ? ' (' . str_pad($this->expiry_month, 2, '0', STR_PAD_LEFT) . '/' . $this->expiry_year . ')'
                : '';
            return "{$brand} {$last4}{$expiry}";
        }

        return 'Saved Card';
    }

    /**
     * Brand icon path
     */
    public function getBrandIconAttribute(): string
    {
        return match (strtolower($this->brand ?? '')) {
            'visa'       => 'images/visa.png',
            'mastercard' => 'images/mastercard.png',
            'amex'       => 'images/amex.png',
            default      => 'images/card-generic.png',
        };
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Scopes
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Only verified saved cards for a user
     */
    public function scopeSavedCards($query, int $userId)
    {
        return $query
            ->where('userId', $userId)
            ->where('consent_status', 'VERIFIED')
            ->whereNotNull('payment_method_id')
            ->orderByDesc('is_default')
            ->orderByDesc('creationdate');
    }
}