<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class QuizPollImport implements WithMultipleSheets 
{
    public function sheets(): array
    {
        return [
            'Trivia' => new TriviaImport(),
            'Polls' => new PollsImport(),
        ];
    }
}
