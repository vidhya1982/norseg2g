<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plans extends Model
{
    protected $table = 'plans';

    protected $fillable = [
        'zone_id',
        'plan_name',
        'plan_name_ar',
        'plan_name_tr',
        'plan_name_il',
        'USD',
        'EUR',
        'status',
        'Days',
        'GB',
        'SMS',
    ];

    /* =====================
     |  Relationships
     ===================== */
    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    /* =====================
     |  Scopes (Reusable)
     ===================== */
    public function scopeActive($query)
    {
        return $query->where('status', 'A');
    }

    public function scopeByZone($query, $zoneId)
    {
        return $query->where('zone_id', $zoneId);
    }

    /* =====================
     |  Multilang Plan Name
     ===================== */
    public function getNameAttribute()
    {
        $locale = app()->getLocale();

        return match ($locale) {
            'ar' => $this->plan_name_ar ?? $this->plan_name,
            'he' => $this->plan_name_il ?? $this->plan_name,
            default => $this->plan_name,
        };
    }
}
