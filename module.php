<?php

namespace UksusoFF\WebtreesModules\Faces;

use Composer\Autoload\ClassLoader;
use Exception;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Controller\BaseController;
use Fisharebest\Webtrees\Database;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleMenuInterface;
use Fisharebest\Webtrees\Theme;
use UksusoFF\WebtreesModules\Faces\Controllers\AdminController;
use UksusoFF\WebtreesModules\Faces\Controllers\DataController;
use UksusoFF\WebtreesModules\Faces\Helpers\DatabaseHelper as DB;
use UksusoFF\WebtreesModules\Faces\Helpers\ResponseHelper as Response;
use UksusoFF\WebtreesModules\Faces\Helpers\RouteHelper as Route;
use UksusoFF\WebtreesModules\Faces\Helpers\TemplateHelper as Template;

class FacesModule extends AbstractModule implements ModuleMenuInterface, ModuleConfigInterface
{
    const CUSTOM_VERSION = '2.3.0';
    const CUSTOM_NAME = 'faces';
    const CUSTOM_WEBSITE = 'https://github.com/UksusoFF/webtrees-faces';

    const SCHEMA_TARGET_VERSION = 4;
    const SCHEMA_SETTING_NAME = 'FACES_SCHEMA_VERSION';
    const SCHEMA_MIGRATION_PREFIX = '\UksusoFF\WebtreesModules\Faces\Schema';

    protected $directory;

    protected $response;
    protected $query;
    protected $route;
    protected $template;

    protected $data;
    protected $admin;

    public function __construct()
    {
        parent::__construct(self::CUSTOM_NAME);

        $this->directory = WT_MODULES_DIR . $this->getName();

        $loader = new ClassLoader();
        $loader->addPsr4('UksusoFF\\WebtreesModules\\Faces\\', $this->directory);
        $loader->register();

        Database::updateSchema(self::SCHEMA_MIGRATION_PREFIX, self::SCHEMA_SETTING_NAME, self::SCHEMA_TARGET_VERSION);

        $this->response = new Response;
        $this->query = new DB;
        $this->route = new Route($this->directory, self::CUSTOM_NAME, self::CUSTOM_VERSION);
        $this->template = new Template($this->directory);

        $this->data = new DataController($this->query);
        $this->admin = new AdminController($this->query, $this->route, $this->template);
    }

    /** {@inheritdoc} */
    public function getName()
    {
        // warning: Must match (case-sensitive!) the directory name!
        return self::CUSTOM_NAME;
    }

    /** {@inheritdoc} */
    public function getTitle()
    {
        return 'Faces';
    }

    /** {@inheritdoc} */
    public function getDescription()
    {
        return 'This module provide easy way to mark people on group photo.';
    }

    /** {@inheritdoc} */
    public function modAction($modAction)
    {
        try {
        switch ($modAction) {
            case 'note_get':
            case 'note_add':
            case 'note_delete':
            case 'note_destroy':
                $response = $this->data->action($modAction);
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

        if (is_array($response)) {
            $this->response->json(array_merge([
                'success' => true,
            ], $response));
        } elseif (is_string($response)) {
            $this->response->string($response);
        } elseif (is_int($response)) {
            $this->response->status($response);
        } elseif ($response === null) {
            $this->response->json(array_merge([
                'success' => false,
            ], $response));
        } else {
            throw new Exception('Unknown response type');
        }
        } catch (Exception $e) {
            $this->response->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
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
                ->addExternalJavascript($this->route->getScriptPath('lib/jquery.imagemapster.min.js'))
                ->addExternalJavascript($this->route->getScriptPath('lib/jquery.imgareaselect.min.js'))
                ->addExternalJavascript($this->route->getScriptPath('lib/jquery.naturalprops.js'))
                ->addExternalJavascript($this->route->getScriptPath('lib/wheelzoom.js'))
                ->addExternalJavascript($this->route->getScriptPath('module.js'))
                ->addInlineJavascript($this->template->output('css_include.js', [
                    'cssPath' => $this->route->getStylePath('module.css'),
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

return new FacesModule();