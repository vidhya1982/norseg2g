<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $table = 'zones';

    protected $fillable = [
        'zone_name',
        'zone_name_ar',
        'zone_name_tr',
        'zone_name_il',
        'image',
        'zone_url',
        'zone_flag',
        'startingprice',
        'status',
        'position',
    ];

    public function plans()
    {
        return $this->hasMany(Plans::class, 'zone_id');
    }

    // Multilang name
    public function getNameAttribute()
    {
        $locale = app()->getLocale();
        
        return match ($locale) {
            'ar' => $this->zone_name_ar ?? $this->zone_name,
            'he' => $this->zone_name_il ?? $this->zone_name,
            default => $this->zone_name,
        };
    }

    // Minimum price
    public function getStartingPriceAttribute()
    {
        return $this->plans()
            ->active()
            ->min('USD');
    }
}
