<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;

use App\Models\AdsManagement;

class AdsManagementController extends Controller
{
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/ads-management/create-edit",
     *      operationId="createeditadsmanagement",
     *      tags={"Ads Management"},
     *      summary="Create / Edit Ads Management Data",
     *      description="Returns Ads Management Data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
               <br><b>id_ads_management</b> : null / empty if want to create data, fill the id number if want to update data
               <br><b>page_location</b> : page location of ads management
               <br><b>slug</b> : slug ads management (should be unique, lowercase, and use dash instead of space or underscore, example: this-is-slug)
               <br><b>status</b> : 0 = disabled, 1 = enabled
               <br><b>soft_delete</b> : 0 = active, 1 = deleted",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_ads_management", type="int", example="1"),
     *              @OA\Property(property="page_location", type="string", example="Homepage"),
     *              @OA\Property(property="slug", type="string", example="homepage"),
     *              @OA\Property(property="status", type="int", example="1"),
     *              @OA\Property(property="soft_delete", type="int", example="0"),
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
            'id_ads_management',
            'page_location',
            'slug',
            'status',
            'soft_delete',
        ]);

        $idAdsManagement = $requestData['id_ads_management'] ?? null;
        $pageLocationAdsManagement = $requestData['page_location'] ?? null;
        $slugAdsManagement = $requestData['slug'] ?? null;
        $statusAdsManagement = $requestData['status'] ?? null;
        $softDeleteAdsManagement = $requestData['soft_delete'] ?? null;

        if ($idAdsManagement === null) {
            // Create a new user account
            $data = new AdsManagement();
        } else {
            // Check if the user already exists in the table
            $data = AdsManagement::where('idAdsManagement', $idAdsManagement)->first();
            if (!$data) {
                return ResultHelpers::errors('Ads Management Data Not Found from id_ads_management inputed', 400, false);
            }
        }

        $pageLocationAdsManagement && $data->page_location = $pageLocationAdsManagement;
        $slugAdsManagement && $data->slug = $slugAdsManagement;
        $statusAdsManagement != null && $data->status = $statusAdsManagement;
        $softDeleteAdsManagement != null && $data->soft_delete = $softDeleteAdsManagement;
        $data->save();

        return ResultHelpers::success($data, 200, true);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Get(
     *      path="/ads-management/show",
     *      operationId="showadsmanagement",
     *      tags={"Ads Management"},
     *      summary="Show ads management",
     *      description="Returns ads management data",
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
    public function show()
    {
        $data = AdsManagement::where('soft_delete', 0)
            ->orderBy('id_ads_management', 'ASC')
            ->get();

        return ResultHelpers::success($data, 200, true);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/ads-management/showByID",
     *      operationId="showadsmanagementbyid",
     *      tags={"Ads Management"},
     *      summary="Show ads management by id or slug",
     *      description="Returns ads management data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
               <br><b>id_ads_management</b> : id of ads management
               <br><b>slug</b> : slug of ads management",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_ads_management", type="int", example="1"),
     *              @OA\Property(property="slug", type="string", example=null),
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
    public function showByID(Request $request)
    {

        $requestData = $request->only([
            'id_ads_management',
            'slug',
        ]);

        $idAdsManagement = $requestData['id_ads_management'] ?? null;
        $slugAdsManagement = $requestData['slug'] ?? null;

        if ($idAdsManagement) {
            $data = AdsManagement::where('soft_delete', 0)
                ->where('id_ads_management', $idAdsManagement)
                ->first();
        } elseif ($slugAdsManagement) {
            $data = AdsManagement::where('soft_delete', 0)
                ->where('slug', $slugAdsManagement)
                ->first();
        } else {
            return ResultHelpers::errors('Ads Management Data Not Found from id_ads_management or slug inputed', 400, false);
        }

        return ResultHelpers::success($data, 200, true);
    }


    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Get(
     *      path="/ads-management/disable-all",
     *      operationId="disablealladsmanagement",
     *      tags={"Ads Management"},
     *      summary="Disable all Ads Management Data",
     *      description="Returns Ads Management Data",
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
    public function disableAll(Request $request)
    {
        AdsManagement::all()->update([
            'status' => 0
        ]);

        return ResultHelpers::success('All ads management was disabled.', 200, true);
    }


    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Get(
     *      path="/ads-management/enable-all",
     *      operationId="enablealladsmanagement",
     *      tags={"Ads Management"},
     *      summary="Enable all Ads Management Data",
     *      description="Returns Ads Management Data",
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
    public function enableAll(Request $request)
    {
        AdsManagement::all()->update([
            'status' => 1
        ]);

        return ResultHelpers::success('All ads management was enabled.', 200, true);
    }
}
