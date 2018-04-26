<?php

namespace UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Modules;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Controller\PageController;
use UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers\DatabaseHelper as DB;
use UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers\JsonResponseHelper as Response;

class AdminModule
{
    private $response;
    private $query;

    public function __construct(Response $response, DB $query)
    {
        $this->response = $response;
        $this->query = $query;
    }

    /**
     * @param string $action
     */
    public function settings($action)
    {
        $controller = new PageController();
        $controller
            ->restrictAccess(Auth::isAdmin())
            ->setPageTitle('Photos')
            ->pageHeader();
    }
}