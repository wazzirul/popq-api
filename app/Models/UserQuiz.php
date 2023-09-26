<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserQuiz extends Model
{
    protected $table = 'user_quiz';
    protected $primaryKey = 'id_user_quiz';
    protected $fillable = ['id_user_quiz','id_user','id_quiz','answered','status','result','date_time','soft_delete'];

    public function Users()
    {
        return $this->hasOne('App\Models\Users','id_user','id_user');
    }

    public function Quiz()
    {
        return $this->hasOne('App\Models\Quiz','id_quiz','id_quiz');
    }

    public function UserQuizAnswer()
    {
        return $this->hasMany('App\Models\UserQuizAnswer','id_user_quiz','id_user_quiz');
    }
}
