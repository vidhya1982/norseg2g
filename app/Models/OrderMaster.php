<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderMaster extends Model
{
    protected $table = 'order_master';
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'amount',
        'user_id',
        'order_type',
        'plans_details',
        'sixth_promo_code',
        'vip_promo_code',
        'recharge_inventoryId',
        'origin',
        'order_status',
        'payment_status',
        'payment_gateway_response',
        'payment_gateway',
        'txn_id'
    ];

    protected $casts = [
        'plans_details' => 'array',
        'origin' => 'array',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'my_uid', 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}