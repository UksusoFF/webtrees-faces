<?php

namespace UksusoFF\WebtreesModules\Faces\Modules;

use Aura\Router\RouterContainer;
use Fig\Http\Message\RequestMethodInterface;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleConfigTrait;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Module\ModuleGlobalInterface;
use Fisharebest\Webtrees\Module\ModuleGlobalTrait;
use Fisharebest\Webtrees\Module\ModuleTabInterface;
use Fisharebest\Webtrees\Module\ModuleTabTrait;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\MigrationService;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use UksusoFF\WebtreesModules\Faces\Helpers\AppHelper;
use UksusoFF\WebtreesModules\Faces\Helpers\DatabaseHelper;
use UksusoFF\WebtreesModules\Faces\Http\Controllers\AdminController;
use UksusoFF\WebtreesModules\Faces\Http\Controllers\DataController;
use UksusoFF\WebtreesModules\Faces\Http\Controllers\MediaHelper;

class FacesModule extends AbstractModule implements ModuleCustomInterface, ModuleGlobalInterface, ModuleConfigInterface, ModuleTabInterface, MiddlewareInterface
{
    use ModuleCustomTrait;
    use ModuleGlobalTrait;
    use ModuleConfigTrait;
    use ModuleTabTrait;

    public const SCHEMA_VERSION = 7;

    public const CUSTOM_VERSION = '2.7.3';

    public const CUSTOM_WEBSITE = 'https://github.com/UksusoFF/webtrees-faces';

    public const SETTING_SCHEMA_NAME = 'FACES_SCHEMA_VERSION';

    public const SETTING_EXIF_NAME = 'FACES_EXIF_ENABLED';

    public const SETTING_LINKING_NAME = 'FACES_LINKING_ENABLED';

    public const SETTING_META_NAME = 'FACES_META_ENABLED';

    public const SETTING_TAB_NAME = 'FACES_TAB_ENABLED';

    public DatabaseHelper $query;

    public MediaHelper $media;

    public function __construct()
    {
        $this->query = new DatabaseHelper();
        $this->media = new MediaHelper();
    }

    public function boot(): void
    {
        View::registerNamespace($this->name(), $this->resourcesFolder() . 'views/');

        $router = AppHelper::get(RouterContainer::class);
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
        $migrations = AppHelper::get(MigrationService::class);
        assert($migrations instanceof MigrationService);

        $migrations->updateSchema('\UksusoFF\WebtreesModules\Faces\Migrations', self::SETTING_SCHEMA_NAME, self::SCHEMA_VERSION);

        return $handler->handle($request);
    }

    public function title(): string
    {
        return I18N::translate('Faces');
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
        $request = AppHelper::get(ServerRequestInterface::class);
        assert($request instanceof ServerRequestInterface);

        $tree = $request->getAttribute('tree');

        return $tree instanceof Tree
            ? view("{$this->name()}::script", [
                'module' => $this->name(),
                'tree' => $tree,
                'settings' => [
                    'exif' => $this->settingEnabled(self::SETTING_EXIF_NAME),
                    'linking' => $this->settingEnabled(self::SETTING_LINKING_NAME),
                    'meta' => $this->settingEnabled(self::SETTING_META_NAME),
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

        $this->setPreference($key, (string) $state);

        return $state;
    }

    public function settingEnabled(string $key): bool
    {
        return (bool) $this->getPreference($key, (string) false);
    }

    public function getConfigLink(): string
    {
        return route(AdminController::ROUTE_PREFIX, [
            'action' => 'config',
        ]);
    }

    public function getTabContent(Individual $individual): string
    {
        if (!$this->settingEnabled(self::SETTING_TAB_NAME)) {
            return '';
        }

        [$rows, $total] = $this->query->getMediaList(
            $individual->tree()->id(),
            null,
            $individual->xref(),
            null,
            0,
            1000
        );

        return view("{$this->name()}::tab", [
            'list' => $rows->map(function($row) use ($individual) {
                return Registry::mediaFactory()->make($row->f_m_id, $individual->tree());
            }),
        ]);
    }

    public function hasTabContent(Individual $individual): bool
    {
        if (!$this->settingEnabled(self::SETTING_TAB_NAME)) {
            return false;
        }

        [$rows, $total] = $this->query->getMediaList(
            $individual->tree()->id(),
            null,
            $individual->xref(),
            null,
            0,
            1
        );

        return $total > 0;
    }

    public function canLoadAjax(): bool
    {
        return true;
    }

    public function isGrayedOut(Individual $individual): bool
    {
        return false;
    }
}
