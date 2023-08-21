<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizQuestions extends Model
{
    protected $table = 'quiz_questions';
    protected $primaryKey = 'id_quiz_questions';
    protected $fillable = ['id_quiz_questions','id_quiz','number_question','question','duration','soft_delete'];

    public function Quiz()
    {
        return $this->hasOne('App\Models\Quiz','id_quiz','id_quiz');
    }

    public function QuizListChoice()
    {
        return $this->hasMany('App\Models\QuizListChoice','id_quiz_questions','id_quiz_questions');
    }
}
