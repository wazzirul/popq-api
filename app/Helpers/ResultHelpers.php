<?php
namespace App\Helpers;

class ResultHelpers {
    public static function success($data, $status_code, $success) {
        $result['data'] = $data;
        $result['success'] = $success;
        $result['status_code'] = $status_code;
        return response($result, 200);
    }

    public static function errors($data, $status_code, $success) {
        $result['errors'] = $data;
        $result['success'] = $success;
        $result['status_code'] = $status_code;
        return response($result, 200);
    }
}