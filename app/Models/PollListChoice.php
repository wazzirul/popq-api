<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PollListChoice extends Model
{
    protected $table = 'poll_list_choice';
    protected $primaryKey = 'id_poll_list_choice';
    protected $fillable = ['id_poll_list_choice','id_poll','poll_choice','soft_delete'];

    public function Poll()
    {
        return $this->hasOne('App\Models\Poll','id_poll','id_poll');
    }
}
