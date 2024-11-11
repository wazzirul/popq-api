<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResultHelpers; 
use Illuminate\Support\Facades\Storage;

use App\Models\Poll;
use App\Models\Quiz;

class ImportController extends Controller
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

    //
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @OA\Post(
     *      path="/import/importImages",
     *      operationId="importImages",
     *      tags={"Import"},
     *      summary="Import Images to Trivia and Poll Data",
     *      description="Returns Images to Trivia and Poll Data",
     *      @OA\RequestBody(
     *          description="<b>Note:</b>
                 <br><b>zip_file</b> : file zip of images with names of images_id references for images that will be imported",
    *          required=true,
    *          @OA\MediaType(
    *              mediaType="multipart/form-data",
    *              @OA\Schema(
    *                  required={"zip_file"},
    *                  @OA\Property(
    *                      property="zip_file",
    *                      type="string",
    *                      format="binary",
    *                  ),
    *              )
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
    public function importImages(Request $request)
    {
        try {
            $requestData = $request->only(['zip_file']);

            $this->validate($request, [
                'zip_file' => 'required|mimes:zip,rar',
            ]);

            $zipFile = $request->file('zip_file');
            $extractPath = storage_path('app/temp_zip_extract');

            $zipFile->move($extractPath, $zipFile->getClientOriginalName());

            // Extract images from any folder inside the zip file
            $zip = new \ZipArchive();
            $zip->open($extractPath . '/' . $zipFile->getClientOriginalName());

            $images = [];
            $errors = [];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $imageId = pathinfo($filename, PATHINFO_FILENAME);
                $extension = pathinfo($filename, PATHINFO_EXTENSION);

                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $path = "images/{$imageId}.{$extension}";
                    $imageData = $zip->getFromIndex($i);

                    // Compress the image before storing
                    $compressedImage = $this->compressImage($imageData, $extension, 70);

                    // Move the compressed image to your desired storage directory
                    Storage::disk('public')->put($path, $compressedImage);
                    // Generate the URL of the uploaded file
                    $imageUrl = Storage::disk('public')->url($path);

                    Quiz::where('images_id', $imageId)->update(['thumbnail' => $imageUrl]);
                    Poll::where('images_id', $imageId)->update(['thumbnail' => $imageUrl]);

                    Quiz::where('images_id', $imageId . ';')->update(['thumbnail' => $imageUrl]);
                    Poll::where('images_id', $imageId . ';')->update(['thumbnail' => $imageUrl]);

                    $images[] = [
                        'image_id' => $imageId,
                        'file_path' => $filename,
                        'extension' => $extension,
                        'image_url' => $imageUrl,
                    ];
                } else {
                    $errors[] = "File {$filename} is not an image";
                }
            }

            $zip->close();

            // Clean up the extracted files
            unlink($extractPath . '/' . $zipFile->getClientOriginalName());

            $results = [
                'message' => "Complete Importing Images Data",
                'images' => $images,
                'errors' => $errors,
            ];

            return ResultHelpers::success($results, 200, true);
        } catch (ValidationException $e) {
            return ResultHelpers::errors($e->validator->errors()->first(), 400, false);
        } catch (\Exception $e) {
            return ResultHelpers::errors($e->getMessage(), 500, false);
        }
    }
}
