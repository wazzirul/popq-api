<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;

use App\Imports\TriviaImport;

use App\Models\Categories;
use App\Models\Quiz;
use App\Models\QuizListChoice;
use App\Models\QuizQuestions;
use App\Models\Users;

class QwizardController extends Controller
{
  /**
   * Compress image using pure PHP.
   *
   * @param string $imageData Binary image data
   * @param string $extension Image file extension
   * @param int $quality Compression quality (0 to 100)
   * @return string Compressed image data
   */
  private function compressImage($imageData, $extension, $quality)
  {
    $image = imagecreatefromstring($imageData);

    ob_start();
    switch ($extension) {
      case 'jpg':
      case 'jpeg':
        imagejpeg($image, null, $quality);
        break;
      case 'png':
        imagepng($image, null, (int)($quality / 10));
        break;
      case 'gif':
        imagegif($image);
        break;
    }
    $compressedImage = ob_get_clean();

    imagedestroy($image);

    return $compressedImage;
  }

  /**
   * Convert base64 icon to file and upload it to public folder.
   *
   * @param string $base64Icon
   * @return string|null
   */
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
    $path = 'quiz_thumbnail/' . $filename; // Set the desired directory within the public storage folder

    // Save the file to the storage disk
    Storage::disk('public')->put($path, $decodedData);

    // Return the path to the uploaded file
    return $path;
  }

  //
  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  /**
   * @OA\Post(
   *      path="/qwizard/store",
   *      operationId="storeqwizard",
   *      tags={"Qwizard"},
   *      summary="Store Qwizard Data",
   *      description="Returns Qwizard Data",
   *      @OA\RequestBody(
   *          description="<b>Note:</b>
               <br><b>id_user</b> : id of users
               <br><b>id_category</b> : id of categories
               <br><b>topic</b> : topic quiz
               <br><b>media</b> : media quiz
               <br><b>franchise</b> : franchise quiz
               <br><b>thumbnail</b> : must be base64 string
               <br><b>soft_delete</b> : 0 = active, 1 = deleted
               <br>________________________________________________
               <br><br><b>Example of Thumbnail Base64:</b>
               data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAFoAAABaCAYAAAA4qEECAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAASRSURBVHgB7Z3NThNRFMf/RSPBSDTxK7g14QHsA1j24B72yl7dG/b6APAC7qV75wV4gSa4hEAgQSUQiATvn95bLpf5KG3vmWnn/JLjZDpThF9Pz5w7t81toE8uLy/vm81jE7MmZkxMm7iHenFh4szEqYm/Jn43Go1//TyxUXSCEUyhL0w8Rf3E9sOhiV0j/CzvpEzRNoPn0JWsFLNvYscIv0g7mCraZvG8iQdQ7sK5iU5adt8SbSQ/NJvXUMmDQtnbRvaJ/+CUv2MzWSUPB929ti579ETbmqzlYjTQ4bxx2mse/Iyeg0oeJXT5yu1cifZaOGW0vHAlxGX0HJRYPOc/U7aOPIUSi2d0zIx+AiUmTOQnFD0LJTazFD0DJTYzFD0NJTbTFK135OJzbwqKCCpaCBUthIoWoo6iOW6YhzD3UQ8otmmiZbecWH1ntyJMumhK/Yrbo1/uL5r4DiEmqXQ0bfh0cs5vQZBxzmhmZcvEG7vlflgSuKXsZsrzm95zojNuojljsYyupLQLGsW9NdH2HmsjXTQRKx9VLx1hbWX2UXRe17AU7CfIztoWhKiaaIpl9n0ysWniR3CcwrYKfoYrCf5zOn2eG40qiOYfyixdR1fsut2fw7V4nwTFLAf7GznnLkKAKohmGWAGZ2VXK9hvo5hQNDPaLx+76Nbm1T5/3tBIXQybuNl+rXrHWAr4h2dNEDPjmJF+J7GF7Ascce+ELe85LqvbEByoOGKKZofwHtetl48vgbAef8j4OWmDiwT5osl88H+IDU7SiFk6mKHsAPopB0UXuPD8tLc7s5Qv2JqJBZQsNqRhpsKLMmMYfiJdNKUsBI/xIpj3uyzg5lt+3W63vKgssS+GWReatG6iSFR4gVu1sYGKSyaxRSc5x8KanPWiUOJYyMwjdtdBOXy7p5WP8F7Djj2fF7E2rsuBeIcQA4n2jtKWM47xcX8w8RkTIjZEYsCSpDzmBgzhsYmUTCQy2g1IWBoSG7uoGbHbO0dR61YVNpB/X2RgdBZcCBUthIoWooypLF4U11Ad1iFAGaLZcYz1KG8Qyp6cHfRLSscYs567TNEcfm9icFaQ/7mNSlGmaGYka/UgWZ034VpJyi4dw2T0WKHtnRBlZjTnFD8izucq0m5YlUrZNZqya/E9x7JFr6AmaI0WooyM5lTVF9SMMkSzJi+hZmjpEEJFC6GihVDRQqhoIVS0ECpaCCnR4/KBmWizNlKiE4wHCSIhKTrKJ4BGyDdEfOdJfSTMkfZ97bJhuUgQubxJi64t2nUIoaKFUNFCqGghVLQQKloIir6AEpsLij6DEpszij6FEpsTip7Y7/ZViGOKPoLW6dgcTdnVyg6gxOKAjl17tw8lFld3Ba9EG+NcsWwPyqjZs25vDFhoXlu90UGXvXvcPdG2VnegskcBHXb81TpvDMFtmv+Cyh4Gutt2JcORtRTq1Xp80DVa7orL5PPwQN7ivlyfhV9NewmlH9hM7N5pcV8fm90U/gxKCNcEPzSxn5bFPoWiHTbDuVLcIxNcAJgvQF3WCHBQLIXy/tAfdBdg72tU/R8v2ARhjZN8fwAAAABJRU5ErkJggg==",
   *          required=true,
   *          @OA\JsonContent(
   *              type="object",
   *              @OA\Property(property="id_user", type="int", example="32"),
   *              @OA\Property(property="id_category", type="int", example="1"),
   *              @OA\Property(property="topic", type="string", example="Obi-wan Kenobi"),
   *              @OA\Property(property="media", type="string", example="Star Wars"),
   *              @OA\Property(property="franchise", type="string", example="The Last Jedi"),
   *              @OA\Property(property="thumbnail", type="string", example=null),
   *              @OA\Property(
   *                  property="questions",
   *                  type="array",
   *                  example={"What is Princess Peach's primary role in the Super Mario series?",
                        "Which character often kidnaps Princess Peach?",
                        "In which game did Princess Peach first appear?",
                        "What is the name of Princess Peach's kingdom?",
                        "What unique ability does Princess Peach have in some games?",
                        "Who is Princess Peach's main ally throughout the series?",
                        "In which game does Princess Peach take on a more active role in gameplay?",
                        "What color is Princess Peach's signature dress?",
                        "Which item does Princess Peach commonly give to Mario as a power-up?",
                        "What is the name of Princess Peach's castle?",
                      },
   *                  @OA\Items(type="string"),
   *              ),
   *              @OA\Property(
   *                  property="correctAnswer",
   *                  type="array",
   *                  example={"What is Princess Peach's primary role in the Super Mario series?",
                        "Damsel in distress",
                        "Bowser",
                        "Donkey Kong",
                        "Mushroom Kingdom",
                        "Floating ability",
                        "Mario",
                        "Super Princess Peach",
                        "Pink",
                        "Cake",
                        "Peach's Castle
                      "},
   *                  @OA\Items(type="string"),
   *              ),
   *              @OA\Property(
   *                  property="wrongAnswer1",
   *                  type="array",
   *                  example={"What is Princess Peach's primary role in the Super Mario series?",
                        "Hero",
                        "Wario",
                        "Super Mario Bros.",
                        "Toad Town",
                        "Teleportation",
                        "Luigi",
                        "Mario Kart",
                        "Blue",
                        "Mushroom",
                        "Toad's Castle",
                      },
   *                  @OA\Items(type="string"),
   *              ),
   *              @OA\Property(
   *                  property="wrongAnswer2",
   *                  type="array",
   *                  example={"What is Princess Peach's primary role in the Super Mario series?",
                        "Villain",
                        "Kamek",
                        "Super Mario 64",
                        "Flower Kingdom",
                        "Super strength",
                        "Yoshi",
                        "Mario Party",
                        "Green",
                        "Star",
                        "Daisy's Castle",
                      },
   *                  @OA\Items(type="string"),
   *              ),
   *              @OA\Property(
   *                  property="wrongAnswer3",
   *                  type="array",
   *                  example={"What is Princess Peach's primary role in the Super Mario series?",
                        "Sidekick",
                        "Donkey Kong",
                        "Mario Bros.",
                        "Flower Kingdom",
                        "Invisibility",
                        "Bowser",
                        "Super Mario Galaxy",
                        "Yellow",
                        "Fire Flower",
                        "Wario's Castle",
                      },
   *                  @OA\Items(type="string"),
   *              ),
   *          )
   *      ),
   *      @OA\Response(
   *          response=200,
   *          description="Successful Operation",
   *       ),
   *      @OA\Response(
   *          response=400,
   *          description="Bad Request"
   *      ),
   *      @OA\Response(
   *          response=401,
   *          description="Unauthorized",
   *      ),
   *      @OA\Response(
   *          response=500,
   *          description="Server Error"
   *      )
   * )
   */
  public function store(Request $request)
  {
    $requestData = $request->only([
      'id_user',
      'id_category',
      'topic',
      'media',
      'franchise',
      'thumbnail',
      'questions',
      'correctAnswer',
      'wrongAnswer1',
      'wrongAnswer2',
      'wrongAnswer3',
    ]);

    $idUser = $requestData['id_user'] ?? null;
    $idCategories = $requestData['id_category'] ?? null;
    $topicQuiz = $requestData['topic'] ?? null;
    $subtitleQuiz = $requestData['media'] ?? null;
    $descriptionQuiz = $requestData['franchise'] ?? 0;
    $thumbnailQuiz = $requestData['thumbnail'] ?? null;
    $questions = $requestData['questions'] ?? null;
    $correctAnswer = $requestData['correctAnswer'] ?? null;
    $wrongAnswer1 = $requestData['wrongAnswer1'] ?? null;
    $wrongAnswer2 = $requestData['wrongAnswer2'] ?? null;
    $wrongAnswer3 = $requestData['wrongAnswer3'] ?? null;

    $titleQuiz = $topicQuiz;

    if ($idCategories) {
      $dataCategories = Categories::find($idCategories);
      if (!$dataCategories) {
        return ResultHelpers::errors('Categories Data Not Found from id_category inputed', 400, false);
      }
    }

    $dataUsers = Users::find($idUser);
    if (!$dataUsers) return ResultHelpers::errors('Users Data Not Found from id_user inputed', 400, false);

    // Generate the URL of the uploaded file
    $thumbnailUrl = null;
    if ($thumbnailQuiz) {
      // Check if thumbnail input data is a valid base64 string
      if (!preg_match('/data:image\/(.*?);base64,/', $thumbnailQuiz)) {
        return ResultHelpers::errors('thumbnail must be a valid base64 string', 400, false);
      }

      // Upload the base64 thumbnail to the public folder and get the path
      $thumbnailPath = $this->uploadImageFromBase64($thumbnailQuiz);

      // Generate the URL of the uploaded file
      $thumbnailUrl = Storage::disk('public')->url($thumbnailPath);
    } else {
      $thumbnailPath = null;
    }

    $data = new Quiz();
    $slug = str_replace(" ", "-", strtolower($topicQuiz)) . "_Q";
    $slug_id = Quiz::count() + 1;
    for ($i = strlen($slug_id); $i < 8; $i++) {
      $slug .= "0";
    }
    $slug .= $slug_id;

    $data->slug = $slug;
    $data->id_user = $dataUsers->id_user;

    $idCategories && $data->id_categories = $idCategories;
    $topicQuiz && $data->topic = $topicQuiz;
    $titleQuiz && $data->title = $titleQuiz;
    $subtitleQuiz && $data->subtitle = $subtitleQuiz;
    $descriptionQuiz && $data->description = $descriptionQuiz;
    $thumbnailUrl != null && $data->thumbnail = $thumbnailUrl;
    $data->save();

    foreach ($questions as $key => $value) {
      $store_question = new QuizQuestions();
      $store_question->id_quiz = $data->id_quiz;
      $store_question->question = $value;
      $store_question->duration = 15;
      $store_question->save();

      $store_correct = new QuizListChoice();
      $store_correct->id_quiz = $data->id_quiz;
      $store_correct->id_quiz_questions = $store_question->id_quiz_questions;
      $store_correct->answer = $correctAnswer[$key];
      $store_correct->correct = 1;
      $store_correct->save();

      $store_wrong = new QuizListChoice();
      $store_wrong->id_quiz = $data->id_quiz;
      $store_wrong->id_quiz_questions = $store_question->id_quiz_questions;
      $store_wrong->answer = $wrongAnswer1[$key];
      $store_wrong->correct = 0;
      $store_wrong->save();

      $store_wrong = new QuizListChoice();
      $store_wrong->id_quiz = $data->id_quiz;
      $store_wrong->id_quiz_questions = $store_question->id_quiz_questions;
      $store_wrong->answer = $wrongAnswer2[$key];
      $store_wrong->correct = 0;
      $store_wrong->save();

      $store_wrong = new QuizListChoice();
      $store_wrong->id_quiz = $data->id_quiz;
      $store_wrong->id_quiz_questions = $store_question->id_quiz_questions;
      $store_wrong->answer = $wrongAnswer3[$key];
      $store_wrong->correct = 0;
      $store_wrong->save();
    }

    $data = Quiz::with(['Categories'])
      ->find($data->id_quiz);
    $quizQuestions = QuizQuestions::where('id_quiz', $data->id_quiz)->where('soft_delete', 0)->get();

    // Shuffle the quiz list choices for each quiz question
    $quizQuestions->each(function ($question) {
      $question->QuizListChoice = QuizListChoice::where('id_quiz_questions', $question->id_quiz_questions)->get()->shuffle();
    });

    // Assign the quiz questions to the quiz
    $data->QuizQuestions = $quizQuestions;

    return ResultHelpers::success($data, 200, true);
  }

  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  /**
   * @OA\Post(
   *      path="/qwizard/myQwizard",
   *      operationId="myqwizardqwizard",
   *      tags={"Qwizard"},
   *      summary="Show My Qwizard Data",
   *      description="Returns Qwizard Data based on id_user",
   *      @OA\RequestBody(
   *          description="<b>Note:</b>
                <br> - id_user as a reference to the quiz data that will be returned",
   *          required=true,
   *          @OA\JsonContent(
   *              type="object",
   *              @OA\Property(property="id_user", type="integer", example="32"),
   *          )
   *      ),
   *      @OA\Response(
   *          response=200,
   *          description="Successful Operation",
   *       ),
   *      @OA\Response(
   *          response=400,
   *          description="Bad Request"
   *      ),
   *      @OA\Response(
   *          response=401,
   *          description="Unauthorized",
   *      ),
   *      @OA\Response(
   *          response=500,
   *          description="Server Error"
   *      )
   * )
   */
  public function myQwizard(Request $request)
  {
    $requestData = $request->only([
      'id_user',
    ]);

    $idUser = $requestData['id_user'] ?? null;

    $dataUsers = Users::find($idUser);
    if (!$dataUsers) return ResultHelpers::errors('Users Data Not Found from id_user inputed', 400, false);

    $data = Quiz::where('soft_delete', 0)
      ->where('draft', 0)
      ->where('id_user', $idUser)
      ->with(['Categories'])
      ->orderBy('id_quiz', 'DESC')
      ->get();

    return ResultHelpers::success($data, 200, true);
  }


  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  /**
   * @OA\Post(
   *      path="/qwizard/show",
   *      operationId="showqwizard",
   *      tags={"Qwizard"},
   *      summary="Show Qwizard Data",
   *      description="Returns Qwizard Data",
   *      @OA\RequestBody(
   *          description="<b>Note:</b>
                <br> - search as search for quiz with references of topic, title, subtitle, description (default null)
                <br> - id_categories as id_categories that the quiz assigned to (default null)
                <br> - slug_categories as slug_categories that the quiz assigned to (default null)
                <br> - draft as draft show data (default 0 / published) (1 = drafted quiz) (100 = all data published / drafted)
                <br> - limit as limit show data (default 10)
                <br> - page as page show data (default 1)
                <br> - sort as sort show data (default 1, 1 = most recent, 2 = least recent, 3 = alphabetical)",
   *          required=true,
   *          @OA\JsonContent(
   *              type="object",
   *              @OA\Property(property="search", type="string", example=null),
   *              @OA\Property(property="id_categories", type="int", example=null),
   *              @OA\Property(property="slug_categories", type="string", example=null),
   *              @OA\Property(property="draft", type="int", example="0"),
   *              @OA\Property(property="limit", type="int", example="10"),
   *              @OA\Property(property="page", type="int", example="1"),
   *              @OA\Property(property="sort", type="int", example="1"),
   *          )
   *      ),
   *      @OA\Response(
   *          response=200,
   *          description="Successful Operation",
   *       ),
   *      @OA\Response(
   *          response=400,
   *          description="Bad Request"
   *      ),
   *      @OA\Response(
   *          response=401,
   *          description="Unauthorized",
   *      ),
   *      @OA\Response(
   *          response=500,
   *          description="Server Error"
   *      )
   * )
   */
  public function show(Request $request)
  {
    $requestData = $request->only([
      'search',
      'id_categories',
      'slug_categories',
      'draft',
      'limit',
      'page',
      'sort',
    ]);

    $search = $requestData['search'] ?? null;
    $idCategories = $requestData['id_categories'] ?? null;
    $slugCategories = $requestData['slug_categories'] ?? null;
    $draft = $requestData['draft'] ?? 0;
    $limit = $requestData['limit'] ?? 10; // Default limit is 10
    $page = $requestData['page'] ?? 1;
    $sort = $requestData['sort'] ?? 1;

    // Calculate the offset based on the page and limit
    $offset = ($page - 1) * $limit;

    // Initialize the query builder for the main data retrieval
    $query = Quiz::leftJoin('categories', 'categories.id_categories', '=', 'quiz.id_categories')
      ->where('quiz.soft_delete', 0)
      ->with('Categories')
      ->withCount('quizQuestions'); // Load the total quiz questions count

    if ($sort === 1) {
      $query->orderBy('id_quiz', 'DESC');
    } else if ($sort === 2) {
      $query->orderBy('id_quiz', 'ASC');
    } else if ($sort === 3) {
      $query->orderBy('title', 'ASC');
    } else {
      $query->orderBy('id_quiz', 'DESC');
    }

    // Initialize the query builder for the total records count
    $totalRecordsQuery = Quiz::leftJoin('categories', 'categories.id_categories', '=', 'quiz.id_categories')->where('quiz.soft_delete', 0);

    if ($search) {
      $query->where(function ($query) use ($search) {
        $query->where('topic', 'LIKE', '%' . $search . '%')
          ->orWhere('title', 'LIKE', '%' . $search . '%')
          ->orWhere('subtitle', 'LIKE', '%' . $search . '%')
          ->orWhere('description', 'LIKE', '%' . $search . '%');
      });

      $totalRecordsQuery->where(function ($query) use ($search) {
        $query->where('topic', 'LIKE', '%' . $search . '%')
          ->orWhere('title', 'LIKE', '%' . $search . '%')
          ->orWhere('subtitle', 'LIKE', '%' . $search . '%')
          ->orWhere('description', 'LIKE', '%' . $search . '%');
      });
    }

    if ($idCategories) {
      $query->where('id_categories', $idCategories);
      $totalRecordsQuery->where('id_categories', $idCategories);
    }

    if ($slugCategories) {
      $query->where('categories.slug', $slugCategories);
      $totalRecordsQuery->where('categories.slug', $slugCategories);
    }

    // Conditionally add the 'draft' where clause if draft is not 100
    if ($draft != 100) {
      $query->where('draft', $draft);
      $totalRecordsQuery->where('draft', $draft);
    }

    $query->where('id_user', '!=', null);
    $totalRecordsQuery->where('id_user', '!=', null);

    // Execute the query to get the total records count
    $totalRecords = $totalRecordsQuery->count();
    $totalPages = ceil($totalRecords / $limit);

    $query->skip($offset)
      ->take($limit);

    // Execute the query to retrieve data
    $data = $query->get();

    return ResultHelpers::success([
      'data' => $data,
      'total_pages' => $totalPages,
    ], 200, true);
  }
}
