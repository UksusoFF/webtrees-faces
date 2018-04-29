<?php

namespace UksusoFF\WebtreesModules\PhotoNoteWithImageMap;

use Composer\Autoload\ClassLoader;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Controller\BaseController;
use Fisharebest\Webtrees\Database;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleMenuInterface;
use Fisharebest\Webtrees\Theme;
use UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Controllers\AdminController;
use UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Controllers\MapController;
use UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers\DatabaseHelper as DB;
use UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers\ResponseHelper as Response;
use UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers\RouteHelper as Route;
use UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers\TemplateHelper as Template;

class PhotoNoteWithImageMap extends AbstractModule implements ModuleMenuInterface, ModuleConfigInterface
{
    const CUSTOM_VERSION = '2.2.0';
    const CUSTOM_WEBSITE = 'https://github.com/UksusoFF/webtrees-photo_note_with_image_map';

    const SCHEMA_TARGET_VERSION = 2;
    const SCHEMA_SETTING_NAME = 'PNWIM_SCHEMA_VERSION';
    const SCHEMA_MIGRATION_PREFIX = '\UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Schema';

    protected $directory;

    protected $response;
    protected $query;
    protected $route;
    protected $template;

    protected $map;
    protected $admin;

    public function __construct()
    {
        parent::__construct('photo_note_with_image_map');

        $this->directory = WT_MODULES_DIR . $this->getName();

        $loader = new ClassLoader();
        $loader->addPsr4('UksusoFF\\WebtreesModules\\PhotoNoteWithImageMap\\', $this->directory);
        $loader->register();

        Database::updateSchema(self::SCHEMA_MIGRATION_PREFIX, self::SCHEMA_SETTING_NAME, self::SCHEMA_TARGET_VERSION);

        $this->response = new Response;
        $this->query = new DB;
        $this->route = new Route(WT_MODULES_DIR, $this->getName(), self::CUSTOM_VERSION);
        $this->template = new Template($this->directory . '/templates/');

        $this->map = new MapController($this->query);
        $this->admin = new AdminController($this->query, $this->route, $this->template);
    }

    /** {@inheritdoc} */
    public function getName()
    {
        // warning: Must match (case-sensitive!) the directory name!
        return 'photo_note_with_image_map';
    }

    /** {@inheritdoc} */
    public function getTitle()
    {
        return 'Photo Note With Image Map';
    }

    /** {@inheritdoc} */
    public function getDescription()
    {
        return 'This module integrate ImageMapster and imgAreaSelect libraries with webtrees. ' .
            'And provide easy way to mark people on group photo.';
    }

    /** {@inheritdoc} */
    public function modAction($modAction)
    {
        switch ($modAction) {
            case 'note_get':
            case 'note_add':
            case 'note_delete':
            case 'note_destroy':
                $response = $this->map->action($modAction);
                break;
            case 'admin_config':
            case 'admin_media':
            case 'admin_missed_repair':
            case 'admin_missed_delete':
                if (Auth::isAdmin()) {
                    $response = $this->admin->action($modAction);
                } else {
                    $response = 403;
                }
                break;
            default:
                $response = 404;
        }

        if (is_array($response) || is_null($response)) {
            $this->response->json($response);
        } elseif (is_string($response)) {
            $this->response->string($response);
        } elseif (is_int($response)) {
            $this->response->status($response);
        } else {
            throw new \Exception('Unknown response type');
        }
    }

    /** {@inheritdoc} */
    public function defaultMenuOrder()
    {
        return 9999;
    }

    /** {@inheritdoc} */
    public function getMenu()
    {
        // We don't actually have a menu - this is just a convenient "hook" to execute
        // code at the right time during page execution
        global $controller;

        if (Theme::theme()->themeId() !== '_administration') {
            $controller->addExternalJavascript('https://cdnjs.cloudflare.com/ajax/libs/mobile-detect/1.3.5/mobile-detect.min.js')
                ->addExternalJavascript($this->route->getResourcePath('/_js/lib/jquery.imagemapster.min.js'))
                ->addExternalJavascript($this->route->getResourcePath('/_js/lib/jquery.imgareaselect.min.js'))
                ->addExternalJavascript($this->route->getResourcePath('/_js/lib/jquery.naturalprops.js'))
                ->addExternalJavascript($this->route->getResourcePath('/_js/lib/wheelzoom.js'))
                ->addExternalJavascript($this->route->getResourcePath('/_js/module.js'))
                ->addInlineJavascript($this->template->output('css_include.tpl', [
                    'cssPath' => $this->route->getResourcePath('/_css/module.css'),
                ]), BaseController::JS_PRIORITY_LOW);
        }

        return null;
    }

    /** {@inheritdoc} */
    public function getConfigLink()
    {
        return $this->route->getActionPath('admin_config');
    }
}

return new PhotoNoteWithImageMap();