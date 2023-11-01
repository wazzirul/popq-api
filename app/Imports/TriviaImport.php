<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithConditionalSheets;

use App\Models\Categories;
use App\Models\Poll;
use App\Models\PollListChoice;
use App\Models\Quiz;
use App\Models\QuizListChoice;
use App\Models\QuizQuestions;
use Illuminate\Support\Facades\Storage;

class TriviaImport implements ToModel, WithStartRow
{
  /**
  * @param array $row
  *
  * @return \Illuminate\Database\Eloquent\Model|null
  */
  public function startRow(): int
  {
      return 2;
  }

  private function uploadImageFromBase64($base64Icon)
  {
      // Extract the MIME type and base64 data from the input
      list($mime, $base64Data) = explode(';', $base64Icon);
      list(, $base64Data) = explode(',', $base64Data);

      // Determine the file extension based on the MIME type
      $mimeParts = explode('/', $mime);
      $extension = end($mimeParts);

      // Generate a unique filename
      $filename = 'thumbnail_' . time() . '.' . $extension;

      // Convert base64 to binary data and save it to a file
      $decodedData = base64_decode($base64Data);
      $path = 'images/' . $filename; // Set the desired directory within the public storage folder

      // Save the file to the storage disk
      Storage::disk('public')->put($path, $decodedData);

      // Return the path to the uploaded file
      return $path;
  }

  public function model(array $row)
  {
    if(!empty($row[3])){
      $categories = Categories::where('name_category', $row[3])->first();
  
      if (!$categories) {
          $categories = new Categories;
          $categories->name_category = $row[3]; // Set the name_category from the Excel data
          $categories->slug = strtolower(str_replace(' ', '-', $row[3]));
          $categories->color = "Transparent";
          $categories->color_hex = "#00000000";
          $categories->save();
      }
      
      $idCategories = $categories->id_categories;
    }
    
    $thumbnailQuiz = $row[5] ?? null;
    $thumbnailUrl = null;
    $imagesIdQuiz = null;
    if ($thumbnailQuiz) {
      if (filter_var($thumbnailQuiz, FILTER_VALIDATE_URL)) {
        // Thumbnail is a URL
        $urlParts = parse_url($thumbnailQuiz);
        $pathInfo = pathinfo($urlParts['path']);
        $extension = isset($pathInfo['extension']) ? $pathInfo['extension'] : 'png'; // Default to jpeg if no extension is found
        
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            $downloadedData = file_get_contents($thumbnailQuiz);
            if ($downloadedData !== false) {
                // Upload the downloaded image
                $thumbnailPath = $this->uploadImageFromBase64('data:image/' . $extension . ';base64,' . base64_encode($downloadedData));
                $thumbnailUrl = Storage::disk('public')->url($thumbnailPath);
            }
        } else {
            // Handle unsupported file format
            return ResultHelpers::errors('Unsupported file format for thumbnail', 400, false);
        }
      }else{
        $imagesIdQuiz = $row[5];
      } 
    } else {
        $thumbnailPath = null;
    }
    
    if (!empty($row[4]) && !empty($row[3]) && !empty($row[0]) && !empty($row[2]) && !empty($row[1])) {
      $idCategories = $categories->id_categories;
      $slugQuiz = strtolower(str_replace(' ', '-', $row[0]));
      $titleQuiz = $row[0];
      $topicQuiz = $row[0];
      $subtitleQuiz = $row[2];
      $descriptionQuiz = $row[1];
      $questionQuiz = $row[4];
      $correctAnswer = $row[6];
      $incorrectAnswerOne = $row[7];
      $incorrectAnswerTwo = $row[8];
      $incorrectAnswerThree = $row[9];
      
      $quiz = Quiz::where('slug', $slugQuiz)->orWhere('title', $titleQuiz)->first();
      if(!$quiz){
        $quiz = new Quiz;
      }
        
      $quiz->id_categories = $idCategories;
      $quiz->topic = $topicQuiz;
      $quiz->title = $titleQuiz;
      $quiz->slug = $slugQuiz;
      $quiz->subtitle = $subtitleQuiz;
      $quiz->images_id = $imagesIdQuiz;
      $quiz->thumbnail = $thumbnailUrl;
      $quiz->description = $descriptionQuiz;
      $quiz->today_status = 0;
      $quiz->draft = 0;
      $quiz->soft_delete = 0;
      $quiz->save();
      
      
      if (!empty($row[4])) {
          $quizQuestions = QuizQuestions::where('question', $questionQuiz)->first();
          if(!$quizQuestions){
            $quizQuestions = new QuizQuestions;
          }
      
          $quizQuestions->id_quiz = $quiz->id_quiz;
          $quizQuestions->number_question = null;
          $quizQuestions->question = $questionQuiz;
          $quizQuestions->duration = 15;
          $quizQuestions->soft_delete = 0;
          $quizQuestions->save();
      }

      
      if ($quizQuestions && !empty($row[6])) {
          $quizListChoiceCorrect = QuizListChoice::where('id_quiz', $quiz->id_quiz)->where('id_quiz_questions', $quizQuestions->id_quiz_questions)->where('answer', $correctAnswer)->first();
          if(!$quizListChoiceCorrect){
            $quizListChoiceCorrect = new QuizListChoice;
          }
          
          $quizListChoiceCorrect->id_quiz = $quiz->id_quiz;
          $quizListChoiceCorrect->id_quiz_questions = $quizQuestions->id_quiz_questions;
          $quizListChoiceCorrect->answer = $correctAnswer;
          $quizListChoiceCorrect->correct = 1;
          $quizListChoiceCorrect->soft_delete = 0;
          $quizListChoiceCorrect->save();
      }
      
      if ($quizQuestions && !empty($row[7])) {
          $quizListChoiceIncorrectOne = QuizListChoice::where('id_quiz', $quiz->id_quiz)->where('id_quiz_questions', $quizQuestions->id_quiz_questions)->where('answer', $correctAnswer)->first();
          if(!$quizListChoiceIncorrectOne){
            $quizListChoiceIncorrectOne = new QuizListChoice;
          }
          
          $quizListChoiceIncorrectOne->id_quiz = $quiz->id_quiz;
          $quizListChoiceIncorrectOne->id_quiz_questions = $quizQuestions->id_quiz_questions;
          $quizListChoiceIncorrectOne->answer = $correctAnswer;
          $quizListChoiceIncorrectOne->correct = 0;
          $quizListChoiceIncorrectOne->soft_delete = 0;
          $quizListChoiceIncorrectOne->save();
      }
      
      if ($quizQuestions && !empty($row[8])) {
          $quizListChoiceIncorrectTwo = QuizListChoice::where('id_quiz', $quiz->id_quiz)->where('id_quiz_questions', $quizQuestions->id_quiz_questions)->where('answer', $correctAnswer)->first();
          if(!$quizListChoiceIncorrectTwo){
            $quizListChoiceIncorrectTwo = new QuizListChoice;
          }
          
          $quizListChoiceIncorrectTwo->id_quiz = $quiz->id_quiz;
          $quizListChoiceIncorrectTwo->id_quiz_questions = $quizQuestions->id_quiz_questions;
          $quizListChoiceIncorrectTwo->answer = $correctAnswer;
          $quizListChoiceIncorrectTwo->correct = 0;
          $quizListChoiceIncorrectTwo->soft_delete = 0;
          $quizListChoiceIncorrectTwo->save();
      }
      
      if ($quizQuestions && !empty($row[9])) {
          $quizListChoiceIncorrectThree = QuizListChoice::where('id_quiz', $quiz->id_quiz)->where('id_quiz_questions', $quizQuestions->id_quiz_questions)->where('answer', $correctAnswer)->first();
          if(!$quizListChoiceIncorrectThree){
            $quizListChoiceIncorrectThree = new QuizListChoice;
          }
          
          $quizListChoiceIncorrectThree->id_quiz = $quiz->id_quiz;
          $quizListChoiceIncorrectThree->id_quiz_questions = $quizQuestions->id_quiz_questions;
          $quizListChoiceIncorrectThree->answer = $correctAnswer;
          $quizListChoiceIncorrectThree->correct = 0;
          $quizListChoiceIncorrectThree->soft_delete = 0;
          $quizListChoiceIncorrectThree->save();
      }
    }
  }
}
