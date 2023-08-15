<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserQuizAnswer extends Model
{
    protected $table = 'user_quiz_answer';
    protected $primaryKey = 'id_user_quiz_answer';
    protected $fillable = ['id_user_quiz_answer','id_user_quiz','id_quiz_questions','id_quiz_list_choice','correct','soft_delete'];

    public function UserQuiz()
    {
        return $this->hasOne('App\Models\Users','id_user_quiz','id_user_quiz');
    }

    public function QuizQuestions()
    {
        return $this->hasOne('App\Models\Quiz','id_quiz_questions','id_quiz_questions');
    }

    public function QuizListChoice()
    {
        return $this->hasOne('App\Models\QuizListChoice','id_quiz_list_choice','id_quiz_list_choice');
    }
}
