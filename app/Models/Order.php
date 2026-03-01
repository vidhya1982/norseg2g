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
        'userId',
        'email',
        'plan_id',
        'plan_moniker',
        'customer_group',
        'GB',
        'add_GB',
        'Mins',
        'SMS',
        'Days',
        'esimLive',
        'autorenew',
        'orderType',
        'USD',
        'status',
        'paymentStatus',
        'transId',
        'transCode',
        'authCode',
        'msgCode',
        'desc',
        'date',
        'plan_start_date',
        'plan_end_date',
        'reclaimDate',
        'loc_update_at',
        'activationFrom',
        'activationBy',
        'activationName',
        'profileId',
        'paymentProfileId',
        'inventoryId',
        'msisdn',
        'customerId',
        'subscriberId',
        'my_uid',
        'apiRequest',
        'apiResponse',
        'apiDetails',
        'reclaimApi',
        'stepCount',
        'emailMsg',
        'activation_alert',
        'alert_70',
        'alert_100',
        'alert_data_70',
        'alert_data_100',
        'alert_bonus_70',
        'alert_bonus_100',
        'alert_tt_70',
        'alert_tt_in_70',
        'alert_tt_in_100',
        'alert_tt_out_70',
        'alert_tt_out_100',
        'alert_expiry',
        'alert_tt_70',
        'alert_tt_100',
        'bonus_data',
        'bonus_type',
        'promocode',
        'network',
        'lang',
        'source',
        'esim_status',
        'last_location',
    ];

    protected $casts = [
        'userid' => 'integer',
        'plan_id' => 'integer',
        'USD' => 'float',
        'date' => 'datetime',
        'plan_start_date' => 'datetime',
        'plan_end_date' => 'datetime',
        'loc_update_at' => 'datetime',
        'activationBy' => 'integer',
        'activation_alert' => 'boolean',
        'alert_70' => 'boolean',
        'alert_100' => 'boolean',
        'alert_data_70' => 'boolean',
        'alert_data_100' => 'boolean',
        'alert_bonus_70' => 'boolean',
        'alert_bonus_100' => 'boolean',
        'alert_tt_70' => 'boolean',
        'alert_tt_in_70' => 'boolean',
        'alert_tt_in_100' => 'boolean',
        'alert_tt_out_70' => 'boolean',
        'alert_tt_out_100' => 'boolean',
        'alert_expiry' => 'boolean',
        'alert_ft_70' => 'boolean',
        'alert_ft_100' => 'boolean',
    ];
    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function getRouteKeyName()
    {
        return 'msisdn';
    }

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
            get: fn() =>
            optional($this->date)->format('d M Y')
        );
    }

    protected function usdFormatted(): Attribute
    {
        return Attribute::make(
            get: fn() =>
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
