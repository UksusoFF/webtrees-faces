<?php

namespace UksusoFF\WebtreesModules\Faces\Controllers;

use DomainException;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Controller\BaseController;
use Fisharebest\Webtrees\Controller\PageController;
use Fisharebest\Webtrees\Filter;
use Fisharebest\Webtrees\Media;
use Fisharebest\Webtrees\Tree;
use UksusoFF\WebtreesModules\Faces\FacesModule;

class AdminController
{
    private $module;

    public function __construct(FacesModule $module)
    {
        $this->module = $module;
    }

    /**
     * @param string $action
     *
     * @return array|int|string|null
     * @throws \Exception
     */
    public function action($action)
    {
        if (!Auth::isAdmin()) {
            return 403;
        }

        switch ($action) {
            case 'admin_config':
                return $this->getConfigPage();
            case 'admin_media':
                return $this->getMediaJson();
            case 'admin_exif_toggle':
                return [
                    'state' => $this->module->exifToggle(),
                ];
            case 'admin_missed_repair':
                return [
                    'records' => $this->module->query->missedNotesRepair(),
                ];
            case 'admin_missed_delete':
                return [
                    'records' => $this->module->query->missedNotesDelete(),
                ];
            default:
                return 404;
        }
    }

    /**
     * @return string
     */
    private function getConfigPage()
    {
        $controller = new PageController();
        $controller
            ->setPageTitle('Faces')
            ->pageHeader()
            ->addExternalJavascript(WT_JQUERY_DATATABLES_JS_URL)
            ->addExternalJavascript(WT_DATATABLES_BOOTSTRAP_JS_URL)
            ->addExternalJavascript($this->module->route->getScriptPath('admin.js'))
            ->addInlineJavascript($this->module->template->output('css_include.js', [
                'cssPath' => $this->module->route->getStylePath('admin.css'),
            ]), BaseController::JS_PRIORITY_LOW);

        return $this->module->template->output('admin_page/config.tpl', [
            'pageTitle' => $controller->getPageTitle(),
            'exifState' => $this->module->exifEnabled() ? 'fa-check-square-o' : 'fa-square-o',
            'dataActionUrl' => $this->module->route->getActionPath('admin_media'),
            'exifToggleUrl' => $this->module->route->getActionPath('admin_exif_toggle'),
            'missedRepairUrl' => $this->module->route->getActionPath('admin_missed_repair'),
            'missedDeleteUrl' => $this->module->route->getActionPath('admin_missed_delete'),
        ]);
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getMediaJson()
    {
        list($rows, $total) = $this->module->query->getMediaList(Filter::getInteger('start'), Filter::getInteger('length'));

        return [
            'draw' => Filter::getInteger('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => array_map(function($row) {
                return $this->getMediaData($row);
            }, $rows),
        ];
    }

    /**
     * @param $row
     *
     * @return array
     * @throws \Exception
     */
    private function getMediaData($row)
    {
        $pids = implode(', ', array_map(function($item) {
            return $item['pid'];
        }, json_decode($row->f_coordinates, true)));

        try {
            $tree = Tree::findById($row->tree_id);
            $media = Media::getInstance($row->f_m_id, $tree);
        } catch (DomainException $exception) {
            $tree = null;
            $media = null;
        }

        if ($tree !== null & $media !== null) {
            return $media->canEdit()
                ? [
                    $this->module->template->output('admin_page/media_item_thumb_valid.tpl', [
                        'src' => $media->getHtmlUrlDirect('thumb'),
                        'showActionUrl' => $media->getRawUrl(),
                    ]),
                    $pids,
                    $this->module->template->output('admin_page/media_item_status_valid.tpl'),
                    $this->module->template->output([
                        'admin_page/media_item_button_show.tpl',
                        'admin_page/media_item_button_delete.tpl',
                    ], [
                        'destroyActionUrl' => $this->module->route->getActionPath('note_destroy', [
                            'mid' => $media->getXref(),
                        ]),
                        'showActionUrl' => $media->getRawUrl(),
                    ]),
                ]
                : [
                    $this->module->template->output('admin_page/media_item_thumb_denied.tpl'),
                    'Sorry, you can`t access to this data.',
                    $this->module->template->output('admin_page/media_item_status_denied.tpl'),
                    '',
                ];
        }

        return [
            $row->f_m_filename,
            $pids,
            $this->module->template->output('admin_page/media_item_status_missed.tpl'),
            $this->module->template->output('admin_page/media_item_button_delete.tpl', [
                'destroyActionUrl' => $this->module->route->getActionPath('note_destroy', [
                    'mid' => $row->f_m_id,
                ]),
            ]),
        ];
    }
}