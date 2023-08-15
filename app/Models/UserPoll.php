<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPoll extends Model
{
    protected $table = 'user_poll';
    protected $primaryKey = 'id_user_poll';
    protected $fillable = ['id_user_poll','id_user','id_poll','id_poll_list_choice','date_time','soft_delete'];

    public function Users()
    {
        return $this->hasOne('App\Models\Users','id_user','id_user');
    }

    public function Poll()
    {
        return $this->hasOne('App\Models\Poll','id_poll','id_poll');
    }
}
