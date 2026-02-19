<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Casts\Attribute;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\ICCID;

class Order extends Model
{
    use HasFactory;

    /**
     * Existing table name
     */
    protected $table = 'orders';

    /**
     * Primary key
     */
    protected $primaryKey = 'id';

    /**
     * CI3 table me timestamps non-standard hain
     */
    public const CREATED_AT = 'date';
    public const UPDATED_AT = null;

    /**
     * Mass assignable fields
     * (sirf commonly used fields rakhe gaye hain)
     */
    protected $fillable = [
        'userid',
        'email',
        'plan_id',
        'plan_moniker',
        'customer_group',
        'GB',
        'add_GB',
        'Mins',
        'SMS',
        'Days',
        'orderType',
        'USD',
        'status',
        'paymentStatus',
        'tranId',
        'tranCode',
        'desc',
        'plan_start_date',
        'plan_end_date',
        'reclaimDate',
        'customerid',
        'my_uid',
        'bonus_data',
        'bonus_type',
        'promocode',
        'network',
        'lang',
        'source',
        'esim_status',
    ];

    /**
     * Type casting (VERY IMPORTANT for future use)
     */
    protected $casts = [
        'userid' => 'integer',
        'plan_id' => 'integer',
        'USD' => 'float',
        'date' => 'datetime',
        'plan_start_date' => 'datetime',
        'plan_end_date' => 'datetime',
        'reclaimDate' => 'datetime',
        'activationBy' => 'integer',
        'alert_70' => 'boolean',
        'alert_100' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Order belongs to User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'userid');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes (Future Reusability)
    |--------------------------------------------------------------------------
    */

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('paymentStatus', 'Paid');
    }

    public function scopeOfUser($query, $userId)
    {
        return $query->where('userid', $userId);
    }
    protected function dateFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () =>
                optional($this->date)->format('d M Y')
        );
    }

    protected function usdFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () =>
                '$' . number_format($this->USD, 2)
        );
    }


    public function plan()
{
    return $this->belongsTo(Plans::class, 'plan_id');
}

public function iccid()
{
    return $this->belongsTo(ICCID::class, 'inventoryId', 'id');
}


}
