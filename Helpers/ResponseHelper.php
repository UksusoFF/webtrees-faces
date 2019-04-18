<?php

namespace UksusoFF\WebtreesModules\Faces\Helpers;

use Fisharebest\Webtrees\Functions\Functions;

class ResponseHelper
{
    /**
     * @param array|null $data
     */
    public function json($data)
    {
        header('Content-type: application/json');

        echo json_encode($data);

        exit;
    }

    /**
     * @param string $data
     */
    public function string($data)
    {
        echo $data;
        exit;
    }

    /**
     * @param int $status
     */
    public function status($status)
    {
        switch ($status) {
            case 403:
                header('Location: ' . WT_LOGIN_URL . '?' . http_build_query([
                        'url' => Functions::getQueryUrl(),
                    ]));
                break;
            default:
                http_response_code($status);
        }
        exit;
    }
}