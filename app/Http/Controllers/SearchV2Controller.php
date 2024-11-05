<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;

use App\Models\Categories;
use App\Models\Poll;
use App\Models\Quiz;
use App\Models\UserQuizAnswer;
use App\Models\Users;
use Illuminate\Support\Facades\DB;

class SearchV2Controller extends Controller
{
  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  /**
   * @OA\Post(
   *      path="/v2/search/showFilter",
   *      operationId="showfiltersearchv2",
   *      tags={"Search V2"},
   *      summary="Show filter search data",
   *      description="Returns filter search data",
   *      @OA\RequestBody(
   *          description="<b>Note:</b>
            <br> - id_categories: id of categories
            <br> - slug_categories: slug of categories
            <br> - limit as limit show data (default 10) set to -1 for disable limitation.",
   *          required=false,
   *          @OA\JsonContent(
   *              type="object",
   *              @OA\Property(property="id_categories", type="int", example="1"),
   *              @OA\Property(property="slug_categories", type="string", example=null),
   *              @OA\Property(property="limit", type="int", example="10"),
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
  public function showFilter(Request $request)
  {
    $requestData = $request->only([
      'id_categories',
      'slug_categories',
      'limit',
    ]);

    $id_categories = $requestData['id_categories'] ?? null;
    $slug_categories = $requestData['slug_categories'] ?? null;
    $limit = $requestData['limit'] ?? 10; // Default limit is 10

    if ($id_categories || $slug_categories) {
      $category = Categories::where('soft_delete', 0)
        ->where(function ($q) use ($id_categories, $slug_categories) {
          $id_categories && $q->where('id_categories', $id_categories);
          $slug_categories && $q->where('slug', $slug_categories);
        })
        ->select([
          'id_categories',
          'name_category',
          'slug',
        ])
        ->first();
    } else $category = null;

    $quizFilter = Quiz::select('subtitle', 'soft_delete')
      ->where(function ($r) use ($category) {
        $category && $r->where('id_categories', $category->id_categories);
      });
    $pollFilter = Poll::select('subtitle', 'soft_delete')
      ->where(function ($s) use ($category) {
        $category && $s->where('id_categories', $category->id_categories);
      });

    $allFilter = $quizFilter->unionAll($pollFilter);

    $data = DB::table(DB::raw("({$allFilter->toSql()}) as combined"))
      ->mergeBindings($allFilter->getQuery())
      ->select([
        'subtitle',
        DB::raw('count(*) as total')
      ])
      ->groupBy('subtitle')
      ->having('total', '!=', 0)
      ->orderBy('total', 'DESC')
      ->limit($limit)
      ->get();

    return ResultHelpers::success($data, 200, true);
  }


  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  /**
   * @OA\Post(
   *      path="/v2/search",
   *      operationId="searchv2",
   *      tags={"Search V2"},
   *      summary="Show search for global data",
   *      description="Returns search for global data",
   *      @OA\RequestBody(
   *          description="<b>Note:</b>
            <br> - search as search with references of topic, title, subtitle, description (default null)
            <br> - id_categories: id of categories
            <br> - slug_categories: slug of categories
            <br> - id_user as references quiz already submitted or not
            <br> - limit as limit show data (default 10)
            <br> - page as page show data (default 1)
            <br> - show_qwizard as condition(boolean) to show qwizard (default: false)
            <br> - sort as sort show data (default 1, 1 = most recent, 2 = least recent, 3 = alphabetical)",
   *          required=false,
   *          @OA\JsonContent(
   *              type="object",
   *              @OA\Property(property="search", type="string", example="Jimmy Conway"),
   *              @OA\Property(property="id_categories", type="int", example="1"),
   *              @OA\Property(property="slug_categories", type="string", example=null),
   *              @OA\Property(property="id_user", type="int", example="5"),
   *              @OA\Property(property="limit", type="int", example="10"),
   *              @OA\Property(property="page", type="int", example="1"),
   *              @OA\Property(property="sort", type="int", example="1"),
   *              @OA\Property(property="show_qwizard", type="boolean", example=false),
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
  public function search(Request $request)
  {
    $requestData = $request->only([
      'search',
      'id_categories',
      'slug_categories',
      'id_user',
      'limit',
      'page',
      'sort',
      'show_qwizard',
    ]);

    $search = $requestData['search'] ?? null;
    $id_categories = $requestData['id_categories'] ?? null;
    $slug_categories = $requestData['slug_categories'] ?? null;
    $id_user = $requestData['id_user'] ?? null;
    $limit = $requestData['limit'] ?? 10; // Default limit is 10
    $page = $requestData['page'] ?? 1;
    $sort = $requestData['sort'] ?? 1; // Default sort is 1 (1 = most recent, 2 = least recent, 3 = alphabetical)
    $showQwizard = $requestData['show_qwizard'] ?? false;

    if ($id_user) {
      $user = Users::where('id_user', $id_user)->first();
      if (!$user) return ResultHelpers::errors('User not found', 400, false);
    }

    // Calculate the offset based on the page and limit
    $offset = ($page - 1) * $limit;

    // Initialize the query builder for the main data retrieval
    $quiz = Quiz::leftJoin('categories', 'categories.id_categories', '=', 'quiz.id_categories')
      ->where('quiz.soft_delete', 0)
      ->where('quiz.draft', 0)
      ->with('Categories')
      ->withCount('quizQuestions'); // Load the total quiz questions count

    $poll = Poll::leftJoin('categories', 'categories.id_categories', '=', 'poll.id_categories')
      ->where('poll.soft_delete', 0)
      ->where('poll.draft', 0)
      ->with('Categories')
      ->withCount('PollListChoice'); // Load the total poll list choices count 

    if ($id_categories || $slug_categories) {
      $id_categories && $category = Categories::find($id_categories);
      $slug_categories && $category = Categories::where('slug', $slug_categories)->first();
      if (!$category) return ResultHelpers::error('Category for that ID not found.', 404);

      $quiz->where('quiz.id_categories', $category->id_categories);
      $poll->where('poll.id_categories', $category->id_categories);
    }

    if ($search) {
      $quiz->where(function ($query) use ($search) {
        $query->where('quiz.topic', 'LIKE', "%$search%")
          ->orWhere('quiz.title', 'LIKE', "%$search%")
          ->orWhere('quiz.subtitle', 'LIKE', "%$search%")
          ->orWhere('quiz.description', 'LIKE', "%$search%");
      });

      $poll->where(function ($query) use ($search) {
        $query->where('poll.topic', 'LIKE', "%$search%")
          ->orWhere('poll.title', 'LIKE', "%$search%")
          ->orWhere('poll.subtitle', 'LIKE', "%$search%")
          ->orWhere('poll.description', 'LIKE', "%$search%");
      });
    }

    if ($sort === 1) {
      $quiz->orderBy('quiz.id_quiz', 'DESC');
      $poll->orderBy('poll.id_poll', 'DESC');
    } else if ($sort === 2) {
      $quiz->orderBy('quiz.id_quiz', 'ASC');
      $poll->orderBy('poll.id_poll', 'ASC');
    } else if ($sort === 3) {
      $quiz->orderBy('quiz.title', 'ASC');
      $poll->orderBy('poll.title', 'ASC');
    } else {
      $quiz->orderBy('quiz.id_quiz', 'DESC');
      $poll->orderBy('poll.id_poll', 'DESC');
    }

    if ($showQwizard == false) {
      $quiz->where('quiz.id_user', null);
    }

    // Execute the query to get the total records count
    $dataQuizTotal = $quiz->count();
    $dataPollTotal = $poll->count();
    $quizTotalPages = ceil($dataQuizTotal / $limit);
    $pollTotalPages = ceil($dataPollTotal / $limit);

    $quiz->skip($offset)
      ->take($limit);
    $poll->skip($offset)
      ->take($limit);

    if ($id_user) {
      $quiz->leftJoin('user_quiz', function ($join) use ($id_user) {
        $join->on('quiz.id_quiz', '=', 'user_quiz.id_quiz')
          ->where('user_quiz.id_user', '=', $id_user);
      });

      $dataQuiz = $quiz->select([
        'quiz.*',
        'user_quiz.id_user_quiz',
        'user_quiz.answered',
        'user_quiz.result',
      ])->get();

      $poll->leftJoin('user_poll', function ($join) use ($id_user) {
        $join->on('poll.id_poll', '=', 'user_poll.id_poll')
          ->where('user_poll.id_user', '=', $id_user);
      });

      $dataPoll = $poll->select([
        'poll.*',
        'user_poll.id_user_poll',
        'user_poll.id_poll_list_choice',
      ])->get();
    } else {
      $dataQuiz = $quiz->get();
      $dataPoll = $poll->get();
    }

    foreach ($dataQuiz as $key => $value) {
      if (isset($value->id_user_quiz)) {
        $userQuizAnswer = UserQuizAnswer::where('soft_delete', 0)
          ->where('id_user_quiz', $value->id_user_quiz);

        $totalQuestions = $userQuizAnswer->count();
        $correctAnswers = $userQuizAnswer->where('correct', 1)->count();

        $value['total_questions'] = $totalQuestions;
        $value['correct_answers'] = $correctAnswers;
      }
    }

    $data['quiz_total_pages'] = $quizTotalPages;
    $data['poll_total_pages'] = $pollTotalPages;
    $data['data_quiz'] = $dataQuiz;
    $data['data_poll'] = $dataPoll;

    return ResultHelpers::success($data, 200, true);
  }


  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  /**
   * @OA\Post(
   *      path="/v2/search/quiz",
   *      operationId="quizsearchv2",
   *      tags={"Search V2"},
   *      summary="Show search for quiz data",
   *      description="Returns search for quiz data",
   *      @OA\RequestBody(
   *          description="<b>Note:</b>
            <br> - search as search for quiz with references of topic, title, subtitle, description (default null)
            <br> - id_categories: id of categories
            <br> - slug_categories: slug of categories
            <br> - id_user as references quiz already submitted or not
            <br> - limit as limit show data (default 10)
            <br> - page as page show data (default 1)
            <br> - show_qwizard as condition(boolean) to show qwizard (default: false)
            <br> - sort as sort show data (default 1, 1 = most recent, 2 = least recent, 3 = alphabetical)",
   *          required=false,
   *          @OA\JsonContent(
   *              type="object",
   *              @OA\Property(property="search", type="string", example="Jimmy Conway"),
   *              @OA\Property(property="id_categories", type="int", example="1"),
   *              @OA\Property(property="slug_categories", type="string", example=null),
   *              @OA\Property(property="id_user", type="int", example="5"),
   *              @OA\Property(property="limit", type="int", example="10"),
   *              @OA\Property(property="page", type="int", example="1"),
   *              @OA\Property(property="sort", type="int", example="1"),
   *              @OA\Property(property="show_qwizard", type="boolean", example=false),
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
  public function quiz(Request $request)
  {
    $requestData = $request->only([
      'search',
      'id_categories',
      'slug_categories',
      'id_user',
      'limit',
      'page',
      'sort',
      'show_qwizard',
    ]);

    $search = $requestData['search'] ?? null;
    $id_categories = $requestData['id_categories'] ?? null;
    $slug_categories = $requestData['slug_categories'] ?? null;
    $id_user = $requestData['id_user'] ?? null;
    $limit = $requestData['limit'] ?? 10; // Default limit is 10
    $page = $requestData['page'] ?? 1;
    $sort = $requestData['sort'] ?? 1; // Default sort is 1 (1 = most recent, 2 = least recent, 3 = alphabetical)
    $showQwizard = $requestData['show_qwizard'] ?? false;

    if ($id_user) {
      $user = Users::where('id_user', $id_user)->first();
      if (!$user) return ResultHelpers::errors('User not found', 400, false);
    }

    // Calculate the offset based on the page and limit
    $offset = ($page - 1) * $limit;

    // Initialize the query builder for the main data retrieval
    $quiz = Quiz::leftJoin('categories', 'categories.id_categories', '=', 'quiz.id_categories')
      ->where('quiz.soft_delete', 0)
      ->where('quiz.draft', 0)
      ->with('Categories')
      ->withCount('quizQuestions'); // Load the total quiz questions count

    if ($id_categories || $slug_categories) {
      $id_categories && $category = Categories::find($id_categories);
      $slug_categories && $category = Categories::where('slug', $slug_categories)->first();
      if (!$category) return ResultHelpers::error('Category for that ID not found.', 404);

      $quiz->where('quiz.id_categories', $category->id_categories);
    }

    if ($search) {
      $quiz->where(function ($query) use ($search) {
        $query->where('topic', 'LIKE', "%$search%")
          ->orWhere('title', 'LIKE', "%$search%")
          ->orWhere('subtitle', 'LIKE', "%$search%")
          ->orWhere('description', 'LIKE', "%$search%");
      });
    }

    if ($sort === 1) {
      $quiz->orderBy('id_quiz', 'DESC');
    } else if ($sort === 2) {
      $quiz->orderBy('id_quiz', 'ASC');
    } else if ($sort === 3) {
      $quiz->orderBy('title', 'ASC');
    } else {
      $quiz->orderBy('id_quiz', 'DESC');
    }

    if ($id_user) {
      $quiz->leftJoin('user_quiz', function ($join) use ($id_user) {
        $join->on('quiz.id_quiz', '=', 'user_quiz.id_quiz')
          ->where('user_quiz.id_user', '=', $id_user);
      });
    }

    if ($showQwizard == false) {
      $quiz->where('quiz.id_user', null);
    }

    // Execute the query to get the total records count
    $dataQuizTotal = $quiz->count();
    $quizTotalPages = ceil($dataQuizTotal / $limit);

    $quiz->skip($offset)
      ->take($limit);

    if ($id_user) {
      $dataQuiz = $quiz->select([
        'quiz.*',
        'user_quiz.id_user_quiz',
        'user_quiz.answered',
        'user_quiz.result',
      ])->get();
    } else {
      $dataQuiz = $quiz->get();
    }

    foreach ($dataQuiz as $key => $value) {
      if (isset($value->id_user_quiz)) {
        $userQuizAnswer = UserQuizAnswer::where('soft_delete', 0)
          ->where('id_user_quiz', $value->id_user_quiz);

        $totalQuestions = $userQuizAnswer->count();
        $correctAnswers = $userQuizAnswer->where('correct', 1)->count();

        $value['total_questions'] = $totalQuestions;
        $value['correct_answers'] = $correctAnswers;
      }
    }

    $data['total_pages'] = $quizTotalPages;
    $data['data'] = $dataQuiz;

    return ResultHelpers::success($data, 200, true);
  }


  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  /**
   * @OA\Post(
   *      path="/v2/search/poll",
   *      operationId="pollsearchv2",
   *      tags={"Search V2"},
   *      summary="Show search for poll data",
   *      description="Returns search for poll data",
   *      @OA\RequestBody(
   *          description="<b>Note:</b>
            <br> - search as search for poll with references of topic, title, subtitle, description (default null)
            <br> - id_categories: id of categories
            <br> - slug_categories: slug of categories
            <br> - limit as limit show data (default 10)
            <br> - page as page show data (default 1)
            <br> - sort as sort show data (default 1, 1 = most recent, 2 = least recent, 3 = alphabetical)",
   *          required=false,
   *          @OA\JsonContent(
   *              type="object",
   *              @OA\Property(property="search", type="string", example="Star Wars"),
   *              @OA\Property(property="id_categories", type="int", example="1"),
   *              @OA\Property(property="slug_categories", type="string", example=null),
   *              @OA\Property(property="id_user", type="int", example="5"),
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
  public function poll(Request $request)
  {
    $requestData = $request->only([
      'search',
      'id_categories',
      'slug_categories',
      'id_user',
      'limit',
      'page',
      'sort',
    ]);

    $search = $requestData['search'] ?? null;
    $id_categories = $requestData['id_categories'] ?? null;
    $slug_categories = $requestData['slug_categories'] ?? null;
    $id_user = $requestData['id_user'] ?? null;
    $limit = $requestData['limit'] ?? 10; // Default limit is 10
    $page = $requestData['page'] ?? 1;
    $sort = $requestData['sort'] ?? 1; // Default sort is 1 (1 = most recent, 2 = least recent, 3 = alphabetical)

    if ($id_user) {
      $user = Users::where('id_user', $id_user)->first();
      if (!$user) return ResultHelpers::errors('User not found', 400, false);
    }

    // Calculate the offset based on the page and limit
    $offset = ($page - 1) * $limit;

    // Initialize the query builder for the main data retrieval
    $poll = Poll::leftJoin('categories', 'categories.id_categories', '=', 'poll.id_categories')
      ->where('poll.soft_delete', 0)
      ->with('Categories')
      ->withCount('PollListChoice'); // Load the total poll list choices count 

    if ($id_categories || $slug_categories) {
      $id_categories && $category = Categories::find($id_categories);
      $slug_categories && $category = Categories::where('slug', $slug_categories)->first();
      if (!$category) return ResultHelpers::error('Category for that ID not found.', 404);

      $poll->where('poll.id_categories', $category->id_categories);
    }

    if ($search) {
      $poll->where(function ($query) use ($search) {
        $query->where('topic', 'LIKE', "%$search%")
          ->orWhere('title', 'LIKE', "%$search%")
          ->orWhere('subtitle', 'LIKE', "%$search%")
          ->orWhere('description', 'LIKE', "%$search%");
      });
    }

    if ($sort === 1) {
      $poll->orderBy('id_poll', 'DESC');
    } else if ($sort === 2) {
      $poll->orderBy('id_poll', 'ASC');
    } else if ($sort === 3) {
      $poll->orderBy('title', 'ASC');
    } else {
      $poll->orderBy('id_poll', 'DESC');
    }

    if ($id_user) {
      $poll->leftJoin('user_poll', function ($join) use ($id_user) {
        $join->on('poll.id_poll', '=', 'user_poll.id_poll')
          ->where('user_poll.id_user', '=', $id_user);
      });
    }

    // Execute the query to get the total records count
    $dataPollTotal = $poll->count();
    $pollTotalPages = ceil($dataPollTotal / $limit);

    $poll->skip($offset)
      ->take($limit);

    if ($id_user) {
      $dataPoll = $poll->select([
        'poll.*',
        'user_poll.id_user_poll',
        'user_poll.id_poll_list_choice',
      ])->get();
    } else {
      $dataPoll = $poll->get();
    }

    $data['total_pages'] = $pollTotalPages;
    $data['data'] = $dataPoll;

    return ResultHelpers::success($data, 200, true);
  }


  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  /**
   * @OA\Post(
   *      path="/v2/search/category",
   *      operationId="categorysearchv2",
   *      tags={"Search V2"},
   *      summary="Show search for category data",
   *      description="Returns search for category data",
   *      @OA\RequestBody(
   *          description="<b>Note:</b>
            <br> - search as search for category with references of name_category, slug (default null)",
   *          required=false,
   *          @OA\JsonContent(
   *              type="object",
   *              @OA\Property(property="search", type="string", example="movie"),
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
  public function category(Request $request)
  {
    $requestData = $request->only([
      'search',
    ]);

    $search = $requestData['search'] ?? null;

    // Initialize the query builder for the main data retrieval
    $data = Categories::where('soft_delete', 0);
    if ($search) {
      $data->where(function ($query) use ($search) {
        $query->where('name_category', 'LIKE', "%$search%")
          ->orWhere('slug', 'LIKE', "%$search%");
      });
    }
    $data = $data->get();

    return ResultHelpers::success($data, 200, true);
  }
}
