<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

use App\Models\Categories;
use App\Models\Poll;
use App\Models\PollListChoice;
use Illuminate\Support\Facades\Storage;


class PollsImport implements ToModel, WithStartRow, WithCustomCsvSettings
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
        }

        $gamesCategory->soft_delete = 0;
        $gamesCategory->save();

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
        }

        $categories->soft_delete = 0;
        $categories->save();

        $idCategories = $categories->id_categories;
      }
    }

    $thumbnailPoll = $row[9] ?? null;
    $PollID = $row[9] ?? null;
    $thumbnailUrl = null;
    $imagesIdPoll = null;
    if ($thumbnailPoll) {
      if (filter_var($thumbnailPoll, FILTER_VALIDATE_URL)) {
        // Thumbnail is a URL
        $urlParts = parse_url($thumbnailPoll);
        $pathInfo = pathinfo($urlParts['path']);
        $extension = isset($pathInfo['extension']) ? $pathInfo['extension'] : 'png'; // Default to jpeg if no extension is found

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
          $downloadedData = file_get_contents($thumbnailPoll);
          if ($downloadedData !== false) {
            // Upload the downloaded image
            $thumbnailPath = $this->uploadImageFromBase64('data:image/' . $extension . ';base64,' . base64_encode($downloadedData));
            $thumbnailUrl = Storage::disk('public')->url($thumbnailPath);
          }
        } else {
          // Handle unsupported file format
          return ResultHelpers::errors('Unsupported file format for thumbnail', 400, false);
        }
      } else {
        $imagesIdPoll = rtrim($row[9], ';');
      }
    } else {
      $thumbnailPath = null;
    }

    if (!empty($row[4]) && !empty($row[3]) && !empty($row[0]) && !empty($row[2]) && !empty($row[1])) {
      $slugPoll = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $row[4])) . '_' . preg_replace('/[^a-zA-Z0-9]+/', '', $PollID);
      $titlePoll = $row[4];
      $topicPoll = $row[0];
      $subtitlePoll = $row[2];
      $descriptionPoll = $row[1];
      $poll = Poll::where('slug', $slugPoll)->first();
      if (!$poll) {
        $poll = new Poll;
      }

      $poll->id_categories = $idCategories;
      $poll->topic = $topicPoll;
      $poll->title = $titlePoll;
      $poll->slug = $slugPoll;
      $poll->subtitle = $subtitlePoll;
      $imagesIdPoll && $poll->images_id = $imagesIdPoll;
      $poll->description = $descriptionPoll;
      $poll->today_status = 0;
      $poll->draft = 0;
      $poll->soft_delete = 0;
      $poll->save();

      if (!empty($row[5])) {
        $existingChoice = PollListChoice::where('poll_choice', $row[5])
          ->where('id_poll', $poll->id_poll)
          ->first();

        if (!$existingChoice) {
          PollListChoice::create([
            'id_poll' => $poll->id_poll,
            'poll_choice' => $row[5],
          ]);
        }
      }

      if (!empty($row[6])) {
        $existingChoice = PollListChoice::where('poll_choice', $row[6])
          ->where('id_poll', $poll->id_poll)
          ->first();

        if (!$existingChoice) {
          PollListChoice::create([
            'id_poll' => $poll->id_poll,
            'poll_choice' => $row[6],
          ]);
        }
      }

      if (!empty($row[7])) {
        $existingChoice = PollListChoice::where('poll_choice', $row[7])
          ->where('id_poll', $poll->id_poll)
          ->first();

        if (!$existingChoice) {
          PollListChoice::create([
            'id_poll' => $poll->id_poll,
            'poll_choice' => $row[7],
          ]);
        }
      }

      if (!empty($row[8])) {
        $existingChoice = PollListChoice::where('poll_choice', $row[8])
          ->where('id_poll', $poll->id_poll)
          ->first();

        if (!$existingChoice) {
          PollListChoice::create([
            'id_poll' => $poll->id_poll,
            'poll_choice' => $row[8],
          ]);
        }
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
