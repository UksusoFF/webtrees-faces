<?php

namespace UksusoFF\WebtreesModules\PhotoNoteWithImageMap;

use Composer\Autoload\ClassLoader;
use Fisharebest\Webtrees\Controller\BaseController;
use Fisharebest\Webtrees\Database;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleMenuInterface;
use Fisharebest\Webtrees\Theme;
use UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers\DatabaseHelper as DB;
use UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers\JsonResponseHelper as Response;
use UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Modules\AdminModule;
use UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Modules\MapModule;

class PhotoNoteWithImageMap extends AbstractModule implements ModuleMenuInterface, ModuleConfigInterface
{
    const CUSTOM_VERSION = '2.2.0';
    const CUSTOM_WEBSITE = 'https://github.com/UksusoFF/webtrees-photo_note_with_image_map';

    const SCHEMA_TARGET_VERSION = 2;
    const SCHEMA_SETTING_NAME = 'PNWIM_SCHEMA_VERSION';
    const SCHEMA_MIGRATION_PREFIX = '\UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Schema';

    var $directory;
    var $path;

    protected $response;
    protected $query;

    protected $map;
    protected $admin;

    public function __construct()
    {
        parent::__construct('photo_note_with_image_map');

        $this->directory = WT_MODULES_DIR . $this->getName();
        $this->path = WT_STATIC_URL . WT_MODULES_DIR . $this->getName();

        $loader = new ClassLoader();
        $loader->addPsr4('UksusoFF\\WebtreesModules\\PhotoNoteWithImageMap\\', $this->directory);
        $loader->register();

        Database::updateSchema(self::SCHEMA_MIGRATION_PREFIX, self::SCHEMA_SETTING_NAME, self::SCHEMA_TARGET_VERSION);

        $this->response = new Response;
        $this->query = new DB;

        $this->map = new MapModule($this->response, $this->query);
        $this->admin = new AdminModule($this->response, $this->query);
    }

    /* ****************************
     * Module configuration
     * ****************************/

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
        global $WT_TREE;
        $tree = $WT_TREE;

        if (empty($tree)) {
            return http_response_code(404);
        }

        switch ($modAction) {
            case 'map_delete':
            case 'map_add':
            case 'map_get':
                $this->map->action($modAction);
                break;
            case 'admin':
                $this->admin->settings($modAction);
                require 'templates/admin.php';
                break;
            default:
                return http_response_code(404);
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
                ->addExternalJavascript($this->path . '/_js/lib/jquery.imagemapster.min.js')
                ->addExternalJavascript($this->path . '/_js/lib/jquery.imgareaselect.min.js')
                ->addExternalJavascript($this->path . '/_js/lib/jquery.naturalprops.js')
                ->addExternalJavascript($this->path . '/_js/lib/wheelzoom.js')
                ->addExternalJavascript($this->path . '/_js/module.js?v=' . self::CUSTOM_VERSION);
            $css = $this->path . '/_css/module.css?v=' . self::CUSTOM_VERSION;
        } else {
            $controller->addExternalJavascript($this->path . '/_js/admin.js?v=' . self::CUSTOM_VERSION);
            $css = $this->path . '/_css/admin.css?v=' . self::CUSTOM_VERSION;
        }

        $header = 'if (document.createStyleSheet) {
				document.createStyleSheet("' . $css . '"); // For Internet Explorer
			} else {
				jQuery("head").append(\'<link rel="stylesheet" href="' . $css . '" type="text/css">\');
			}';

        $controller->addInlineJavascript($header, BaseController::JS_PRIORITY_LOW);

        return null;
    }

    /** {@inheritdoc} */
    public function getConfigLink()
    {
        return 'module.php?mod=' . $this->getName() . '&amp;mod_action=admin';
    }
}

return new PhotoNoteWithImageMap();