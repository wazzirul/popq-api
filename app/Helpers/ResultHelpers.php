<?php

namespace App\Helpers;

class ResultHelpers
{
    public static function success($data, $status_code, $success)
    {
        $result['success'] = $success;
        $result['status_code'] = $status_code;
        $result['data'] = $data;
        return response($result, 200);
    }

    public static function errors($data, $status_code, $success)
    {
        $result['success'] = $success;
        $result['status_code'] = $status_code;
        $result['errors'] = $data;
        return response($result, 200);
    }
}
