<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentProfileLog extends Model
{
    protected $table = 'payment_profile_log';

    protected $primaryKey = 'id';

    public $timestamps = false; 
    // Set true ONLY if table has created_at & updated_at

    protected $fillable = [
        'userId',
        'profileId',
        'paymentProfileId',
        'orderId',
        'order_total',
        'creationdate',
    ];

    protected $casts = [
        'order_total' => 'float',
        'creationdate' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function initiatedOrder()
    {
        return $this->belongsTo(OrdersInitiated::class, 'orderId');
    }
}