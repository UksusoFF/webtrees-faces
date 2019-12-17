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

class FacesModule extends AbstractModule implements ModuleCustomInterface, ModuleGlobalInterface, ModuleConfigInterface, MiddlewareInterface
{
    use ModuleCustomTrait;
    use ModuleGlobalTrait;
    use ModuleConfigTrait;

    public const SCHEMA_VERSION = '4';

    public const CUSTOM_VERSION = '2.5.0';

    public const CUSTOM_WEBSITE = 'https://github.com/UksusoFF/webtrees-faces';

    public const SETTING_EXIF_NAME = 'FACES_EXIF_ENABLED';

    public const SETTING_SCHEMA_NAME = 'FACES_SCHEMA_VERSION';

    public $query;

    public function __construct()
    {
        $this->query = new DatabaseHelper();
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

    public function resourcesFolder(): string
    {
        return __DIR__ . '/../../resources/';
    }

    public function headContent(): string
    {
        return view("{$this->name()}::style", [
            'styles' => [
                $this->assetUrl('styles/module.css'),
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
                'routes' => [
                    'admin' => e(route(AdminController::ROUTE_PREFIX, [
                        'tree' => $tree->name(),
                        'action' => '%action',
                    ])),
                    'data' => e(route(DataController::ROUTE_PREFIX, [
                        'tree' => $tree->name(),
                        'action' => '%action',
                    ])),
                ],
                'scripts' => [
                    $this->assetUrl('scripts/lib/mobile-detect.min.js'),
                    $this->assetUrl('scripts/lib/jquery.imagemapster.min.js'),
                    $this->assetUrl('scripts/lib/jquery.imgareaselect.min.js'),
                    $this->assetUrl('scripts/lib/jquery.naturalprops.js'),
                    $this->assetUrl('scripts/module.js'),
                ],
            ])
            : '';
    }

    public function exifToggle()
    {
        $state = !$this->exifEnabled();

        $this->setPreference(self::SETTING_EXIF_NAME, $state);

        return $state;
    }

    public function exifEnabled(): bool
    {
        return (bool)$this->getPreference(self::SETTING_EXIF_NAME, false);
    }

    public function getConfigLink(): string
    {
        return route(AdminController::ROUTE_PREFIX, [
            'action' => 'config',
        ]);
    }
}
