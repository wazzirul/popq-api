<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdsManagement extends Model
{
    protected $table = 'ads_management';
    protected $primaryKey = 'id_ads_management';
    protected $fillable = [
        'id_ads_management',
        'page_location',
        'slug',
        'type',
        'script',
        'status',
        'soft_delete'
    ];
}
