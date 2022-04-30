<?php

namespace UksusoFF\WebtreesModules\Faces\Http\Controllers;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Http\Exceptions\HttpAccessDeniedException;
use Fisharebest\Webtrees\Http\Exceptions\HttpNotFoundException;
use Fisharebest\Webtrees\Http\RequestHandlers\ControlPanel;
use Fisharebest\Webtrees\Http\ViewResponseTrait;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Media;
use Fisharebest\Webtrees\MediaFile;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\TreeService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use UksusoFF\WebtreesModules\Faces\Modules\FacesModule;

class AdminController implements RequestHandlerInterface
{
    use ViewResponseTrait;

    public const ROUTE_PREFIX = 'faces-admin';

    protected FacesModule $module;

    public function __construct(FacesModule $module)
    {
        $this->module = $module;
    }

    public function handle(ServerRequestInterface $request): Response
    {
        $this->layout = 'layouts/administration';

        if (!Auth::isAdmin()) {
            throw new HttpAccessDeniedException();
        }

        switch ($request->getAttribute('action')) {
            case 'config':
                return $this->config($request);
            case 'data':
                return $this->data($request);
            case 'destroy':
                return $this->destroy($request);
            case 'setting_exif':
                return $this->settingExif();
            case 'setting_linking':
                return $this->settingLinking();
            case 'setting_meta':
                return $this->settingMeta();
            case 'setting_tab':
                return $this->settingTab();
            case 'missed_repair':
                return $this->missedRepair();
            case 'missed_destroy':
                return $this->missedDestroy();
            default:
                throw new HttpNotFoundException();
        }
    }

    private function config(Request $request): Response
    {
        return $this->viewResponse($this->module->name() . '::admin/config', [
            'title' => $this->module->title(),
            'tree' => null,
            'breadcrumbs' => [
                route(ControlPanel::class) => I18N::translate('Control panel'),
                $this->module->getConfigLink() => $this->module->title(),
            ],
            'settings' => [
                'exif' => $this->module->settingEnabled(FacesModule::SETTING_EXIF_NAME),
                'linking' => $this->module->settingEnabled(FacesModule::SETTING_LINKING_NAME),
                'meta' => $this->module->settingEnabled(FacesModule::SETTING_META_NAME),
                'tab' => $this->module->settingEnabled(FacesModule::SETTING_TAB_NAME),
            ],
            'filters' => array_filter([
                $request->getQueryParams()['mid'] ?? null,
                $request->getQueryParams()['pid'] ?? null,
                $request->getQueryParams()['q'] ?? null,
            ]),
            'routes' => [
                'data' => route(self::ROUTE_PREFIX, [
                    'action' => 'data',
                    'mid' => $request->getQueryParams()['mid'] ?? null,
                    'pid' => $request->getQueryParams()['pid'] ?? null,
                    'q' => $request->getQueryParams()['q'] ?? null,
                ]),
                'admin' => $this->module->getConfigLink(),
                'setting_exif' => route(self::ROUTE_PREFIX, [
                    'action' => 'setting_exif',
                ]),
                'setting_linking' => route(self::ROUTE_PREFIX, [
                    'action' => 'setting_linking',
                ]),
                'setting_meta' => route(self::ROUTE_PREFIX, [
                    'action' => 'setting_meta',
                ]),
                'setting_tab' => route(self::ROUTE_PREFIX, [
                    'action' => 'setting_tab',
                ]),
                'missed_repair' => route(self::ROUTE_PREFIX, [
                    'action' => 'missed_repair',
                ]),
                'missed_destroy' => route(self::ROUTE_PREFIX, [
                    'action' => 'missed_destroy',
                ]),
            ],
            'styles' => [
                $this->module->assetUrl('build/admin.min.css'),
            ],
            'scripts' => [
                $this->module->assetUrl('build/vendor.min.js'),
                $this->module->assetUrl('build/admin.min.js'),
            ],
        ]);
    }

    private function data(Request $request): Response
    {
        [$rows, $total] = $this->module->query->getMediaList(
            null,
            $request->getQueryParams()['mid'] ?? null,
            $request->getQueryParams()['pid'] ?? null,
            $request->getQueryParams()['q'] ?? null,
            $request->getQueryParams()['start'] ?? 0,
            $request->getQueryParams()['length'] ?? 10
        );

        return response([
            'draw' => $request->getQueryParams()['draw'] ?? '1',
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $rows->map(function($row) {
                return $this->prepareRow($row);
            }),
        ]);
    }

    private function prepareRow($row): array
    {
        $pids = implode(', ', array_map(function($item) {
            return $item['pid'];
        }, json_decode($row->f_coordinates, true)));

        if (
            $row->m_file === null ||
            ($tree = app(TreeService::class)->find((int)$row->m_file)) === null ||
            ($media = Registry::mediaFactory()->make($row->f_m_id, $tree)) === null ||
            ($file = $this->module->media->getMediaImageFileByOrder($media, (int)$row->f_m_order)) === null
        ) {
            return $this->rowMissed($row, $pids);
        }

        return $media->canEdit()
            ? $this->rowDisplay($media, $file, $row->f_m_order, $pids)
            : $this->rowDenied();
    }

    private function rowMissed($row, string $pids): array
    {
        return [
            $row->f_m_filename,
            $pids,
            view($this->module->name() . '::admin/parts/media_item_status_missed'),
            view($this->module->name() . '::admin/parts/media_item_actions', [
                'destroy' => route(self::ROUTE_PREFIX, [
                    'action' => 'destroy',
                    'mid' => $row->f_m_id,
                    'tid' => $row->m_file,
                    'order' => $row->f_m_order,
                ]),
            ]),
        ];
    }

    private function rowDisplay(Media $media, MediaFile $file, int $order, string $pids): array
    {
        return [
            view($this->module->name() . '::admin/parts/media_item_thumb_valid', [
                'src' => $file->imageUrl(150, 150, 'crop'),
                'href' => $media->url(),
            ]),
            $pids,
            view($this->module->name() . '::admin/parts/media_item_status_valid'),
            view($this->module->name() . '::admin/parts/media_item_actions', [
                'destroy' => route(self::ROUTE_PREFIX, [
                    'action' => 'destroy',
                    'mid' => $media->xref(),
                    'tid' => $media->tree()->id(),
                    'order' => $order,
                ]),
                'show' => $media->url(),
            ]),
        ];
    }

    private function rowDenied(): array
    {
        return [
            view($this->module->name() . '::admin/parts/media_item_thumb_denied'),
            'Sorry, you can`t access to this data.',
            view($this->module->name() . '::admin/parts/media_item_status_denied'),
            '',
        ];
    }

    private function settingExif(): Response
    {
        $state = $this->module->settingToggle(FacesModule::SETTING_EXIF_NAME)
            ? I18N::translate('Enabled')
            : I18N::translate('Disabled');

        return response([
            'success' => true,
            'message' => "{$state}: "
                . I18N::translate('Read and show XMP data (such as Goggle Picasa face tags) from media file') . '.',
            'link' => 'https://github.com/UksusoFF/webtrees-faces#google-picasa',
        ]);
    }

    private function settingLinking(): Response
    {
        $state = $this->module->settingToggle(FacesModule::SETTING_LINKING_NAME)
            ? I18N::translate('Enabled')
            : I18N::translate('Disabled');

        return response([
            'success' => true,
            'message' => "{$state}: "
                . I18N::translate('Link individual with media when mark them on photo') . '.',
        ]);
    }

    private function settingMeta(): Response
    {
        $state = $this->module->settingToggle(FacesModule::SETTING_META_NAME)
            ? I18N::translate('Enabled')
            : I18N::translate('Disabled');

        return response([
            'success' => true,
            'message' => "{$state}: "
                . I18N::translate('Load and show information from linked fact') . '.',
        ]);
    }

    private function settingTab(): Response
    {
        $state = $this->module->settingToggle(FacesModule::SETTING_TAB_NAME)
            ? I18N::translate('Enabled')
            : I18N::translate('Disabled');

        return response([
            'success' => true,
            'message' => "{$state}: "
                . I18N::translate('Show tab on individuals page') . '.',
        ]);
    }

    private function destroy(Request $request): Response
    {
        $count = $this->module->query->setMediaMap(
            (int)$request->getQueryParams()['tid'],
            $request->getQueryParams()['mid'],
            (int)$request->getQueryParams()['order'],
            null,
            null
        );

        return response([
            'success' => true,
            'message' => I18N::plural('%s record', '%s records', $count, I18N::number($count))
                . ' ' . I18N::plural('has been deleted', 'have been deleted', $count, I18N::number($count)) . '.',
        ]);
    }

    private function missedRepair(): Response
    {
        $count = $this->module->query->missedNotesRepair();

        return response([
            'success' => true,
            'message' => I18N::plural('%s record', '%s records', $count, I18N::number($count))
                . ' ' . I18N::plural('has been repaired', 'have been repaired', $count, I18N::number($count)) . '.',
        ]);
    }

    private function missedDestroy(): Response
    {
        $count = $this->module->query->missedNotesDestroy();

        return response([
            'success' => true,
            'message' => I18N::plural('%s record', '%s records', $count, I18N::number($count))
                . ' ' . I18N::plural('has been deleted', 'have been deleted', $count, I18N::number($count)) . '.',
        ]);
    }
}
