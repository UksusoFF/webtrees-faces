<?php

namespace UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers;

class JsonResponseHelper
{
    public static function success($data)
    {
        header('Content-type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $data,
        ]);
    }

    public static function fail($data)
    {
        header('Content-type: application/json');
        echo json_encode([
            'success' => false,
            'data' => $data,
        ]);
    }
}