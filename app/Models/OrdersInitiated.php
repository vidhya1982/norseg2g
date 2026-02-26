<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdersInitiated extends Model
{
    protected $table = 'orders_initiated'; // change if different

    protected $primaryKey = 'id';

    public $timestamps = false; 
    // true only if table has created_at & updated_at

    protected $fillable = [
        'userid',
        'usd',
        'paymentStatus',
        'transId',
        'custom',
    ];

    protected $casts = [
        'usd' => 'float',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class, 'userid');
    }
}