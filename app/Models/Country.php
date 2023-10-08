<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'country_name',
        'ar_country_name',
        'en_country_name',
        'country_code',
        'dial_code'
    ];
}
