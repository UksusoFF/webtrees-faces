<?php

namespace UksusoFF\WebtreesModules\Faces\Modules;

use Aura\Router\RouterContainer;
use Fig\Http\Message\RequestMethodInterface;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleConfigTrait;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Module\ModuleGlobalInterface;
use Fisharebest\Webtrees\Module\ModuleGlobalTrait;
use Fisharebest\Webtrees\Services\MigrationService;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use UksusoFF\WebtreesModules\Faces\Helpers\DatabaseHelper;
use UksusoFF\WebtreesModules\Faces\Http\Controllers\AdminController;
use UksusoFF\WebtreesModules\Faces\Http\Controllers\DataController;
use UksusoFF\WebtreesModules\Faces\Http\Controllers\MediaHelper;

class FacesModule extends AbstractModule implements ModuleCustomInterface, ModuleGlobalInterface, ModuleConfigInterface, MiddlewareInterface
{
    use ModuleCustomTrait;
    use ModuleGlobalTrait;
    use ModuleConfigTrait;

    public const SCHEMA_VERSION = '6';

    public const CUSTOM_VERSION = '2.6.4';

    public const CUSTOM_WEBSITE = 'https://github.com/UksusoFF/webtrees-faces';

    public const SETTING_SCHEMA_NAME = 'FACES_SCHEMA_VERSION';

    public const SETTING_EXIF_NAME = 'FACES_EXIF_ENABLED';

    public const SETTING_LINKING_NAME = 'FACES_LINKING_ENABLED';

    public const SETTING_META_NAME = 'FACES_META_ENABLED';

    public $query;

    public $media;

    public function __construct()
    {
        $this->query = new DatabaseHelper();
        $this->media = new MediaHelper();
    }

    public function boot(): void
    {
        View::registerNamespace($this->name(), $this->resourcesFolder() . 'views/');

        $router = app(RouterContainer::class);
        assert($router instanceof RouterContainer);

        $map = $router->getMap();

        $map
            ->get(
                AdminController::ROUTE_PREFIX,
                '/admin/' . AdminController::ROUTE_PREFIX . '/{action}',
                new AdminController($this)
            )
            ->allows(RequestMethodInterface::METHOD_POST);

        $map
            ->get(
                DataController::ROUTE_PREFIX,
                '/tree/{tree}/' . DataController::ROUTE_PREFIX . '/{action}',
                new DataController($this)
            )
            ->allows(RequestMethodInterface::METHOD_POST);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        app(MigrationService::class)->updateSchema('\UksusoFF\WebtreesModules\Faces\Migrations', self::SETTING_SCHEMA_NAME, self::SCHEMA_VERSION);

        return $handler->handle($request);
    }

    public function title(): string
    {
        return 'Faces';
    }

    public function description(): string
    {
        return 'This module provide easy way to mark people on group photo.';
    }

    public function customModuleAuthorName(): string
    {
        return 'UksusoFF';
    }

    public function customModuleVersion(): string
    {
        return self::CUSTOM_VERSION;
    }

    public function customModuleSupportUrl(): string
    {
        return self::CUSTOM_WEBSITE;
    }

    public function customTranslations(string $language): array
    {
        $file = $this->resourcesFolder() . "langs/{$language}.php";

        return file_exists($file)
            ? require $file
            : require $this->resourcesFolder() . 'langs/en.php';
    }

    public function resourcesFolder(): string
    {
        return __DIR__ . '/../../resources/';
    }

    public function headContent(): string
    {
        return view("{$this->name()}::style", [
            'styles' => [
                $this->assetUrl('build/module.min.css'),
            ],
        ]);
    }

    public function bodyContent(): string
    {
        /** @var \Psr\Http\Message\ServerRequestInterface $request */
        $request = app(ServerRequestInterface::class);

        $tree = $request->getAttribute('tree');

        return $tree instanceof Tree
            ? view("{$this->name()}::script", [
                'module' => $this->name(),
                'tree' => $tree,
                'settings' => [
                    'exif' => $this->settingEnabled(FacesModule::SETTING_EXIF_NAME),
                    'linking' => $this->settingEnabled(FacesModule::SETTING_LINKING_NAME),
                    'meta' => $this->settingEnabled(FacesModule::SETTING_META_NAME),
                ],
                'routes' => [
                    'data' => e(route(DataController::ROUTE_PREFIX, [
                        'tree' => $tree->name(),
                        'action' => 'FACES_ACTION',
                    ])),
                    'admin' => e(route(AdminController::ROUTE_PREFIX, [
                        'action' => 'FACES_ACTION',
                    ])),
                ],
                'scripts' => [
                    $this->assetUrl('build/vendor.min.js'),
                    $this->assetUrl('build/module.min.js'),
                ],
            ])
            : '';
    }

    public function settingToggle(string $key): bool
    {
        $state = !$this->settingEnabled($key);

        $this->setPreference($key, $state);

        return $state;
    }

    public function settingEnabled(string $key): bool
    {
        return (bool)$this->getPreference($key, false);
    }

    public function getConfigLink(): string
    {
        return route(AdminController::ROUTE_PREFIX, [
            'action' => 'config',
        ]);
    }
}
