<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Poll extends Model
{
    protected $table = 'poll';
    protected $primaryKey = 'id_poll';
    protected $fillable = ['id_poll','id_categories','topic','title','slug','subtitle','images_id','thumbnail','description','today_status','draft','soft_delete'];

    public function Categories()
    {
        return $this->hasOne('App\Models\Categories','id_categories','id_categories');
    }

    public function PollListChoice()
    {
        return $this->hasMany('App\Models\PollListChoice','id_poll','id_poll');
    }
}
