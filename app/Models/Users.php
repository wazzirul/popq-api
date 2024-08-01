<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id_user';
    protected $fillable = ['id_user','username','fb_ids','go_ids','status','soft_delete'];

    public function UserQuiz()
    {
        return $this->hasMany(UserQuiz::class,'id_user','id_user');
    }

    public function UserPoll()
    {
        return $this->hasMany(UserPoll::class,'id_user','id_user');
    }
}
