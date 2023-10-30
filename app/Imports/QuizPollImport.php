<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class QuizPollImport implements WithMultipleSheets 
{
    public function sheets(): array
    {
        return [
            new TriviaImport(),
            new PollsImport()
        ];
    }
}
