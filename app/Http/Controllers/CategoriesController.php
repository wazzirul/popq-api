<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\Users;
use App\Models\AuthenticationToken;
use App\Models\Categories;

class CategoriesController extends Controller
{
    //
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/categories/create-edit",
     *      operationId="createEditCategories",
     *      tags={"Categories"},
     *      summary="Create / Edit Categories Data",
     *      description="Returns Categories Data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
     *          <br><b>id_categories</b> : null / empty if want to create data, fill the id number if want to update data
     *          <br><b>name_category</b> : name of category
     *          <br><b>icon</b> : must be base64 string",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_categories", type="number", example="1"),
     *              @OA\Property(property="name_category", type="string", example="Movies"),
     *              @OA\Property(property="icon", type="string", example="base_64string"),
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
    public function createEdit(Request $request)
    {
        $requestData = $request->only([
            'id_categories', // ID Categories
            'name_category', // Name Categories
            'icon', // Icon Categories
        ]);

        $idCategories = $requestData['id_categories'] ?? null;
        $nameCategories = $requestData['name_category'] ?? null;
        $iconCategories = $requestData['icon'] ?? null;

        if($idCategories === null){
            // Create a new user account
            $data = new Categories();
            
            $data->name_category = $nameCategories;
            $data->icon = $iconCategories;
            $data->save();
        }else{
            // Check if the user already exists in the table
            $data = Categories::where('id_categories', $idCategories)->first();
            if ($data) {
                $data->name_category = $nameCategories;
                $data->icon = $iconCategories;
                $data->save();
            }else{
                return ResultHelpers::errors('Categories Data Not From Based on id_categories inputed', 400, false);
            }
    

        }
        return ResultHelpers::success($data, 200, true);
    }
}
