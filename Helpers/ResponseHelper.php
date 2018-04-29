<?php

namespace UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers;

class ResponseHelper
{
    public function json($data)
    {
        header('Content-type: application/json');
        if (!is_null($data)) {
            echo json_encode(array_merge([
                'success' => true,
            ], $data));
        } else {
            echo json_encode([
                'success' => false,
            ]);
        }
        exit;
    }

    public function string($data)
    {
        echo $data;
        exit;
    }

    public function status($status) {
        http_response_code($status);
        exit;
    }
}