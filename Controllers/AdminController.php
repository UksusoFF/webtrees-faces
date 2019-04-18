<?php

namespace UksusoFF\WebtreesModules\Faces\Controllers;

use DomainException;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Controller\BaseController;
use Fisharebest\Webtrees\Controller\PageController;
use Fisharebest\Webtrees\Filter;
use Fisharebest\Webtrees\Media;
use Fisharebest\Webtrees\Tree;
use UksusoFF\WebtreesModules\Faces\Helpers\DatabaseHelper as DB;
use UksusoFF\WebtreesModules\Faces\Helpers\RouteHelper as Route;
use UksusoFF\WebtreesModules\Faces\Helpers\TemplateHelper as Template;

class AdminController
{
    private $query;
    private $template;
    private $route;

    public function __construct(DB $query, Route $route, Template $template)
    {
        $this->query = $query;
        $this->route = $route;
        $this->template = $template;
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
            case 'admin_missed_repair':
                return [
                    'records' => $this->query->missedNotesRepair(),
                ];
            case 'admin_missed_delete':
                return [
                    'records' => $this->query->missedNotesDelete(),
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
            ->addExternalJavascript($this->route->getScriptPath('admin.js'))
            ->addInlineJavascript($this->template->output('css_include.js', [
                'cssPath' => $this->route->getStylePath('admin.css'),
            ]), BaseController::JS_PRIORITY_LOW);

        return $this->template->output('admin_page/config.tpl', [
            'pageTitle' => $controller->getPageTitle(),
            'dataActionUrl' => $this->route->getActionPath('admin_media'),
            'missedRepairUrl' => $this->route->getActionPath('admin_missed_repair'),
            'missedDeleteUrl' => $this->route->getActionPath('admin_missed_delete'),
        ]);
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getMediaJson()
    {
        list($rows, $total) = $this->query->getMediaList(Filter::getInteger('start'), Filter::getInteger('length'));

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
                    $this->template->output('admin_page/media_item_thumb_valid.tpl', [
                        'src' => $media->getHtmlUrlDirect('thumb'),
                        'showActionUrl' => $media->getRawUrl(),
                    ]),
                    $pids,
                    $this->template->output('admin_page/media_item_status_valid.tpl'),
                    $this->template->output([
                        'admin_page/media_item_button_show.tpl',
                        'admin_page/media_item_button_delete.tpl',
                    ], [
                        'destroyActionUrl' => $this->route->getActionPath('note_destroy', [
                            'mid' => $media->getXref(),
                        ]),
                        'showActionUrl' => $media->getRawUrl(),
                    ]),
                ]
                : [
                    $this->template->output('admin_page/media_item_thumb_denied.tpl'),
                    'Sorry, you can`t access to this data.',
                    $this->template->output('admin_page/media_item_status_denied.tpl'),
                    '',
                ];
        }

        return [
            $row->f_m_filename,
            $pids,
            $this->template->output('admin_page/media_item_status_missed.tpl'),
            $this->template->output('admin_page/media_item_button_delete.tpl', [
                'destroyActionUrl' => $this->route->getActionPath('note_destroy', [
                    'mid' => $row->f_m_id,
                ]),
            ]),
        ];
    }
}