<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $table = 'quiz';
    protected $primaryKey = 'id_quiz';
    protected $fillable = ['id_quiz','id_categories','title','slug','subtitle','thumbnail','description','today_status','draft','soft_delete'];

    public function Categories()
    {
        return $this->hasOne('App\Models\Categories','id_categories','id_categories');
    }

    public function QuizQuestions()
    {
        return $this->hasMany('App\Models\QuizQuestions','id_quiz','id_quiz');
    }

    public function QuizListChoice()
    {
        return $this->hasMany('App\Models\QuizListChoice','id_quiz','id_quiz');
    }
}
