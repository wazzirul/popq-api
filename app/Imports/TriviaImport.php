<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

use App\Models\Categories;
use App\Models\Poll;
use App\Models\PollListChoice;
use App\Models\Quiz;
use App\Models\QuizListChoice;
use App\Models\QuizQuestions;
use Illuminate\Support\Facades\Storage;

class TriviaImport implements ToModel, WithStartRow, WithCustomCsvSettings
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
    if (!empty($row[3])) {
      // Check if the category name contains the word "games" or is related to video games
      if (stripos($row[3], 'Game') !== false || stripos($row[3], 'Games') !== false || stripos($row[3], 'game') !== false || stripos($row[3], 'games') !== false) {
          // If it does, find or create the "Games" category
          $gamesCategory = Categories::where('name_category', 'like', '%Games%')->first();
  
          if (!$gamesCategory) {
              $gamesCategory = new Categories;
              $gamesCategory->name_category = 'Games';
              $gamesCategory->slug = 'games';
              $gamesCategory->color = "cyan"; // Set the desired color for Games category
              $gamesCategory->color_hex = "#88E2CE"; // Set the desired color code for Games category
              $gamesCategory->save();
          }
  
          // Assign the "Games" category ID
          $idCategories = $gamesCategory->id_categories;
  
      } else {
          // If it doesn't contain "games", proceed with the original logic
          $categories = Categories::where('name_category', 'like', '%' . $row[3] . '%')->first();
  
          if (!$categories) {
              $categories = new Categories;
              $categories->name_category = $row[3];
              $categories->slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $row[3]));
              $categories->color = "Transparent";
              $categories->color_hex = "#00000000";
              $categories->save();
          }
  
          $idCategories = $categories->id_categories;
      }
    }
    
    $thumbnailQuiz = $row[9] ?? null;
    $QuizID = $row[9] ?? null; // from csv file
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
        $imagesIdQuiz = rtrim($row[9], ';');
      } 
    } else {
        $thumbnailPath = null;
    }
    
    if (!empty($row[4]) && !empty($row[3]) && !empty($row[0]) && !empty($row[2]) && !empty($row[1])) {
      $slugQuiz = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $row[0])).'_'.preg_replace('/[^a-zA-Z0-9]+/', '',$QuizID);
      $titleQuiz = $row[0];
      $topicQuiz = $row[0];
      $subtitleQuiz = $row[2];
      $descriptionQuiz = $row[1];
      $questionQuiz = $row[4];
      $correctAnswer = $row[5];
      $incorrectAnswerOne = $row[6];
      $incorrectAnswerTwo = $row[7];
      $incorrectAnswerThree = $row[8];
      
      $quiz = Quiz::where('slug', $slugQuiz)->first();
      if(!$quiz){
        $quiz = new Quiz;
      }
        
      $quiz->id_categories = $idCategories;
      $quiz->topic = $topicQuiz;
      $quiz->title = $titleQuiz;
      $quiz->slug = $slugQuiz;
      $quiz->subtitle = $subtitleQuiz;
      $imagesIdQuiz && $quiz->images_id = $imagesIdQuiz;
      $quiz->description = $descriptionQuiz;
      $quiz->today_status = 0;
      $quiz->draft = 0;
      $quiz->soft_delete = 0;
      $quiz->save();
      
      
      if (!empty($row[4])) {
          $quizQuestions = QuizQuestions::where('question', $questionQuiz)
          ->where('id_quiz', $quiz->id_quiz)
          ->first();
          
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

      
      if ($quizQuestions && !empty($row[5])) {
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
      
      if ($quizQuestions && !empty($row[6])) {
          $quizListChoiceIncorrectOne = QuizListChoice::where('id_quiz', $quiz->id_quiz)->where('id_quiz_questions', $quizQuestions->id_quiz_questions)->where('answer', $incorrectAnswerOne)->first();
          if(!$quizListChoiceIncorrectOne){
            $quizListChoiceIncorrectOne = new QuizListChoice;
          }
          
          $quizListChoiceIncorrectOne->id_quiz = $quiz->id_quiz;
          $quizListChoiceIncorrectOne->id_quiz_questions = $quizQuestions->id_quiz_questions;
          $quizListChoiceIncorrectOne->answer = $incorrectAnswerOne;
          $quizListChoiceIncorrectOne->correct = 0;
          $quizListChoiceIncorrectOne->soft_delete = 0;
          $quizListChoiceIncorrectOne->save();
      }
      
      if ($quizQuestions && !empty($row[7])) {
          $quizListChoiceIncorrectTwo = QuizListChoice::where('id_quiz', $quiz->id_quiz)->where('id_quiz_questions', $quizQuestions->id_quiz_questions)->where('answer', $incorrectAnswerTwo)->first();
          if(!$quizListChoiceIncorrectTwo){
            $quizListChoiceIncorrectTwo = new QuizListChoice;
          }
          
          $quizListChoiceIncorrectTwo->id_quiz = $quiz->id_quiz;
          $quizListChoiceIncorrectTwo->id_quiz_questions = $quizQuestions->id_quiz_questions;
          $quizListChoiceIncorrectTwo->answer = $incorrectAnswerTwo;
          $quizListChoiceIncorrectTwo->correct = 0;
          $quizListChoiceIncorrectTwo->soft_delete = 0;
          $quizListChoiceIncorrectTwo->save();
      }
      
      if ($quizQuestions && !empty($row[8])) {
          $quizListChoiceIncorrectThree = QuizListChoice::where('id_quiz', $quiz->id_quiz)->where('id_quiz_questions', $quizQuestions->id_quiz_questions)->where('answer', $incorrectAnswerThree)->first();
          if(!$quizListChoiceIncorrectThree){
            $quizListChoiceIncorrectThree = new QuizListChoice;
          }
          
          $quizListChoiceIncorrectThree->id_quiz = $quiz->id_quiz;
          $quizListChoiceIncorrectThree->id_quiz_questions = $quizQuestions->id_quiz_questions;
          $quizListChoiceIncorrectThree->answer = $incorrectAnswerThree;
          $quizListChoiceIncorrectThree->correct = 0;
          $quizListChoiceIncorrectThree->soft_delete = 0;
          $quizListChoiceIncorrectThree->save();
      }
    }
  }
    
  public function getCsvSettings(): array
  {
    return [
      'delimiter' => "|"
    ];
  }
}
