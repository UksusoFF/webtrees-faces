<?php

namespace UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers;

class JsonResponseHelper
{
    public function success($data)
    {
        header('Content-type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function fail($data)
    {
        header('Content-type: application/json');
        echo json_encode([
            'success' => false,
            'data' => $data,
        ]);
    }
}