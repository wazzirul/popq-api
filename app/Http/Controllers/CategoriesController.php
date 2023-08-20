<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

use App\Models\Categories;

class CategoriesController extends Controller
{
    /**
     * Convert base64 icon to file and upload it to public folder.
     *
     * @param string $base64Icon
     * @return string|null
     */
    private function uploadIconFromBase64($base64Icon)
    {
        // Extract the MIME type and base64 data from the input
        list($mime, $base64Data) = explode(';', $base64Icon);
        list(, $base64Data) = explode(',', $base64Data);

        // Determine the file extension based on the MIME type
        $mimeParts = explode('/', $mime);
        $extension = end($mimeParts);

        // Generate a unique filename
        $filename = 'icon_' . time() . '.' . $extension;

        // Convert base64 to binary data and save it to a file
        $decodedData = base64_decode($base64Data);
        $path = 'icons/' . $filename; // Set the desired directory within the public storage folder

        // Save the file to the storage disk
        Storage::disk('public')->put($path, $decodedData);

        // Return the path to the uploaded file
        return $path;
    }


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
               <br><b>id_categories</b> : null / empty if want to create data, fill the id number if want to update data
               <br><b>name_category</b> : name of category
               <br><b>icon</b> : must be base64 string
               <br><b>soft_delete</b> : 0 = active, 1 = deleted
               <br><b>Example Icon Base64:</b>
               data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAFoAAABaCAYAAAA4qEECAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAASRSURBVHgB7Z3NThNRFMf/RSPBSDTxK7g14QHsA1j24B72yl7dG/b6APAC7qV75wV4gSa4hEAgQSUQiATvn95bLpf5KG3vmWnn/JLjZDpThF9Pz5w7t81toE8uLy/vm81jE7MmZkxMm7iHenFh4szEqYm/Jn43Go1//TyxUXSCEUyhL0w8Rf3E9sOhiV0j/CzvpEzRNoPn0JWsFLNvYscIv0g7mCraZvG8iQdQ7sK5iU5adt8SbSQ/NJvXUMmDQtnbRvaJ/+CUv2MzWSUPB929ti579ETbmqzlYjTQ4bxx2mse/Iyeg0oeJXT5yu1cifZaOGW0vHAlxGX0HJRYPOc/U7aOPIUSi2d0zIx+AiUmTOQnFD0LJTazFD0DJTYzFD0NJTbTFK135OJzbwqKCCpaCBUthIoWoo6iOW6YhzD3UQ8otmmiZbecWH1ntyJMumhK/Yrbo1/uL5r4DiEmqXQ0bfh0cs5vQZBxzmhmZcvEG7vlflgSuKXsZsrzm95zojNuojljsYyupLQLGsW9NdH2HmsjXTQRKx9VLx1hbWX2UXRe17AU7CfIztoWhKiaaIpl9n0ysWniR3CcwrYKfoYrCf5zOn2eG40qiOYfyixdR1fsut2fw7V4nwTFLAf7GznnLkKAKohmGWAGZ2VXK9hvo5hQNDPaLx+76Nbm1T5/3tBIXQybuNl+rXrHWAr4h2dNEDPjmJF+J7GF7Ascce+ELe85LqvbEByoOGKKZofwHtetl48vgbAef8j4OWmDiwT5osl88H+IDU7SiFk6mKHsAPopB0UXuPD8tLc7s5Qv2JqJBZQsNqRhpsKLMmMYfiJdNKUsBI/xIpj3uyzg5lt+3W63vKgssS+GWReatG6iSFR4gVu1sYGKSyaxRSc5x8KanPWiUOJYyMwjdtdBOXy7p5WP8F7Djj2fF7E2rsuBeIcQA4n2jtKWM47xcX8w8RkTIjZEYsCSpDzmBgzhsYmUTCQy2g1IWBoSG7uoGbHbO0dR61YVNpB/X2RgdBZcCBUthIoWooypLF4U11Ad1iFAGaLZcYz1KG8Qyp6cHfRLSscYs567TNEcfm9icFaQ/7mNSlGmaGYka/UgWZ034VpJyi4dw2T0WKHtnRBlZjTnFD8izucq0m5YlUrZNZqya/E9x7JFr6AmaI0WooyM5lTVF9SMMkSzJi+hZmjpEEJFC6GihVDRQqhoIVS0ECpaCCnR4/KBmWizNlKiE4wHCSIhKTrKJ4BGyDdEfOdJfSTMkfZ97bJhuUgQubxJi64t2nUIoaKFUNFCqGghVLQQKloIir6AEpsLij6DEpszij6FEpsTip7Y7/ZViGOKPoLW6dgcTdnVyg6gxOKAjl17tw8lFld3Ba9EG+NcsWwPyqjZs25vDFhoXlu90UGXvXvcPdG2VnegskcBHXb81TpvDMFtmv+Cyh4Gutt2JcORtRTq1Xp80DVa7orL5PPwQN7ivlyfhV9NewmlH9hM7N5pcV8fm90U/gxKCNcEPzSxn5bFPoWiHTbDuVLcIxNcAJgvQF3WCHBQLIXy/tAfdBdg72tU/R8v2ARhjZN8fwAAAABJRU5ErkJggg==",
     *          required=true,
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="id_categories", type="int", example="1"),
     *              @OA\Property(property="name_category", type="string", example="Movies"),
     *              @OA\Property(property="icon", type="string", example="base_64string"),
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
            'id_categories', // ID Categories
            'name_category', // Name Categories
            'icon', // Icon Categories using base64
            'soft_delete', // Soft Delete
        ]);

        $idCategories = $requestData['id_categories'] ?? null;
        $nameCategories = $requestData['name_category'] ?? null;
        $iconCategories = $requestData['icon'] ?? null;
        $softDeleteCategories = $requestData['soft_delete'] ?? 0;

        if ($iconCategories) {
            // Check if icon input data is a valid base64 string
            if (!preg_match('/data:image\/(.*?);base64,/', $iconCategories)) {
                return ResultHelpers::errors('Icon must be a valid base64 string', 400, false);
            }
            
            // Upload the base64 icon to the public folder and get the path
            $iconPath = $this->uploadIconFromBase64($iconCategories);

            // Generate the URL of the uploaded file
            $iconUrl = Storage::disk('public')->url($iconPath);
        } else {
            $iconPath = null;
        }

        if($idCategories === null){
            // Create a new user account
            $data = new Categories();
            
            $data->name_category = $nameCategories;
            $data->icon = $iconUrl;
            $data->soft_delete = $softDeleteCategories;
            $data->save();
        }else{
            // Check if the user already exists in the table
            $data = Categories::where('id_categories', $idCategories)->first();
            if ($data) {
                $data->name_category = $nameCategories;
                $data->icon = $iconUrl;
                $data->soft_delete = $softDeleteCategories;
                $data->save();
            }else{
                return ResultHelpers::errors('Categories Data Not Found from id_categories inputed', 400, false);
            }
        }

        return ResultHelpers::success($data, 200, true);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Get(
     *      path="/categories/show",
     *      operationId="showCategories",
     *      tags={"Categories"},
     *      summary="Show categories",
     *      description="Returns categories data",
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
        $data = Categories::where('soft_delete', 0)
            ->orderBy('id_categories', 'DESC')
            ->select([
                'id_categories',
                'name_category',
                'icon',
            ])
            ->get();

        return ResultHelpers::success($data, 200, true);
    }
}
