<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;
use App\Models\Poll;
use App\Models\Quiz;

class OthersController extends Controller
{
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/others/sitemap-quiz",
     *      operationId="othersitemapquiz",
     *      tags={"Others"},
     *      summary="Show list of sitemap quiz",
     *      description="Returns list of sitemap quiz",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
                <br> - limit as limit show data (default 50000)",
     *          required=false,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="limit", type="int", example="50000"),
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
    public function sitemapQuiz(Request $request)
    {
        $requestData = $request->only([
            'limit',
        ]);

        $limit = $requestData['limit'] ?? 50000;

        $data = Quiz::where('quiz.soft_delete', 0)
            ->rightJoin('categories', 'categories.id_categories', '=', 'quiz.id_categories')
            ->select([
                'quiz.id_quiz',
                'quiz.slug',
                'categories.slug as slug_category',
            ])
            ->limit($limit)
            ->get();

        return ResultHelpers::success($data, 200, true);
    }


    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/others/sitemap-poll",
     *      operationId="othersitemappoll",
     *      tags={"Others"},
     *      summary="Show list of sitemap poll",
     *      description="Returns list of sitemap poll",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
                <br> - limit as limit show data (default 50000)",
     *          required=false,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="limit", type="int", example="50000"),
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
    public function sitemapPoll(Request $request)
    {
        $requestData = $request->only([
            'limit',
        ]);

        $limit = $requestData['limit'] ?? 50000;

        $data = Poll::where('poll.soft_delete', 0)
            ->rightJoin('categories', 'categories.id_categories', '=', 'poll.id_categories')
            ->select([
                'poll.id_poll',
                'poll.slug',
                'categories.slug as slug_category',
            ])
            ->limit($limit)
            ->get();

        return ResultHelpers::success($data, 200, true);
    }
}
