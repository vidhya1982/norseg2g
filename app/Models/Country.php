<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'countries';
    public $timestamps = false;


    public function scopeActiveCountries($query, $countryIds = null)
    {
        $query->where('status', 'A');

        if (!empty($countryIds)) {
            // single id ho ya multiple, dono handle karega
            $query->whereIn('id', (array) $countryIds);
        }

        return $query;
    }
}
