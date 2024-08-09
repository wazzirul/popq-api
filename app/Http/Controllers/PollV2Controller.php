<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;

use App\Imports\PollsImport;

use App\Models\Categories;
use App\Models\Poll;
use App\Models\PollListChoice;
use App\Models\Users;
use App\Models\UserPoll;

class PollV2Controller extends Controller
{
  /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  /**
   * @OA\Post(
   *      path="/v2/poll/showById",
   *      operationId="showbyidpollv2",
   *      tags={"Poll V2"},
   *      summary="Show BY Id Poll with related poll Data",
   *      description="Returns Poll with related poll Data based on id_poll",
   *      @OA\RequestBody(
   *          description="<b>Note:</b>
                <br> - id_user (optional) as a reference to the user data that will be returned with prioritize on recent
                <br> - id_poll as a reference to the poll data that will be returned
                <br> - slug as a reference to the poll data that will be returned
                <br> - id_user as a reference to the poll data that will be returned (if the user already logged in)
                <br> - subtitle: subtitle poll (Media in new feature)
                <br> - description: description poll (Franchise in new feature)
                <br> - limit_related as limit show data related (default 10)",
   *          required=true,
   *          @OA\JsonContent(
   *              type="object",
   *              @OA\Property(property="id_user", type="int", example="5"),
   *              @OA\Property(property="id_poll", type="string", example="1"),
   *              @OA\Property(property="slug", type="string", example=null),
   *              @OA\Property(property="limit_related", type="int", example="10"),
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
  public function showById(Request $request)
  {
    $requestData = $request->only([
      'id_user',
      'id_poll',
      'slug',
      'limit_related',
    ]);

    $idUser = $requestData['id_user'] ?? null;
    $idPoll = $requestData['id_poll'] ?? null;
    $slugPoll = $requestData['slug'] ?? null;
    $limitRelated = $requestData['limit_related'] ?? 10;

    if (!$idPoll && !$slugPoll) {
      return ResultHelpers::errors('id_poll or slug poll must be provided.', 400, false);
    }

    $query = Poll::where('poll.soft_delete', 0);
    $poll = null;

    if ($idPoll) {
      // Check by idPoll if provided
      $query->where('id_poll', $idPoll);
    } elseif ($slugPoll) {
      // Check by slugPoll if provided
      $query->where('slug', $slugPoll);
    }

    $poll = $query->with('Categories', 'PollListChoice')->first();

    if ($idUser) {
      $user = Users::find($idUser);
      if (!$user) return ResultHelpers::errors('User not found.', 404, false);

      $relatedPollUser = UserPoll::where('user_poll.id_user', $idUser)
        ->where('user_poll.soft_delete', 0)
        ->rightJoin('poll', 'poll.id_poll', '=', 'user_poll.id_poll')
        ->where(function ($q) use ($poll) {
          $q->orwhere('poll.topic', 'LIKE', "%$poll->topic%");
          $q->orwhere('poll.title', 'LIKE', "%$poll->title%");
          $q->orwhere('poll.subtitle', 'LIKE', "%$poll->subtitle%");
        })
        ->where('user_poll.id_poll', '!=', $idPoll)
        ->orderBy('user_poll.updated_at', 'DESC')
        ->limit($limitRelated)
        ->get();

      $newLimit = $limitRelated - $relatedPollUser->count();
      if ($newLimit > 0) {
        $relatedPoll = Poll::where('soft_delete', 0)
          ->where(function ($q) use ($poll) {
            $q->orwhere('topic', 'LIKE', "%$poll->topic%");
            $q->orwhere('title', 'LIKE', "%$poll->title%");
            $q->orwhere('subtitle', 'LIKE', "%$poll->subtitle%");
          })
          ->orderBy('created_at', 'DESC')
          ->where('id_poll', '!=', $idPoll)
          ->whereNotIn('id_poll', $relatedPollUser->pluck('id_poll'))
          ->limit($newLimit)
          ->get();

        $relatedPoll = [...$relatedPollUser, ...$relatedPoll];
      }
    } else {
      $relatedPoll = Poll::where('soft_delete', 0)
        ->where(function ($q) use ($poll) {
          $q->orwhere('topic', 'LIKE', "%$poll->topic%");
          $q->orwhere('title', 'LIKE', "%$poll->title%");
          $q->orwhere('subtitle', 'LIKE', "%$poll->subtitle%");
        })
        ->orderBy('created_at', 'DESC')
        ->limit($limitRelated)
        ->get();
    }
    $poll['related_poll'] = $relatedPoll;

    if (!$poll) {
      return ResultHelpers::errors('Poll not found.', 404, false);
    }

    // Calculate the total number of votes for all choices for this poll
    $totalVotes = UserPoll::whereIn('id_poll', [$poll->id_poll])->count();

    // Load the associated PollListChoice records
    $poll->load(['PollListChoice']);

    // Calculate and set the percentage for each choice
    foreach ($poll->PollListChoice as $choice) {
      $pollCount = UserPoll::where('id_poll_list_choice', $choice->id_poll_list_choice)
        ->count();

      // Calculate the percentage (rounded to 2 decimal places)
      $percentage = $totalVotes > 0 ? round(($pollCount / $totalVotes) * 100, 2) : 0;

      $choice->poll_count = $pollCount;
      $choice->percentage = $percentage;
    }

    return ResultHelpers::success($poll, 200, true);
  }
}
