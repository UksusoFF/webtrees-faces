<?php

namespace UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Modules;

use Fisharebest\Webtrees\Filter;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Media;
use Fisharebest\Webtrees\Tree;
use UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers\DatabaseHelper as DB;
use UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers\JsonResponseHelper as Response;

class MapModule
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
     * @throws \Exception
     */
    public function action($action)
    {
        global $WT_TREE;
        $tree = $WT_TREE;

        $mid = Filter::get('mid');
        if (!$mid) {
            $mid = Filter::post('mid');
        }
        $media = Media::getInstance($mid, $tree);

        switch ($action) {
            case 'map_delete':
                $pid = Filter::post('pid');
                if ($media && $media->canEdit() && $pid !== null) {
                    $map = array_filter($this->query->getMediaMap($media), function ($area) use ($pid) {
                        return !empty($area['pid']) && $area['pid'] != $pid;
                    });
                    $this->query->setMediaMap($media, $map);
                    $this->response->success([
                        'title' => $this->presentMediaTitle($media),
                        'map' => $this->presentMediaMapForTree($media, $tree),
                        'edit' => $media->canEdit(),
                    ]);
                }
                break;
            case 'map_add':
                $pid = Filter::post('pid');
                $coords = Filter::post('coords');
                if ($media && $media->canEdit() && $pid !== null && $coords !== null) {
                    $map = $this->query->getMediaMap($media);
                    $map[] = (object)[
                        'pid' => $pid,
                        'coords' => $coords,
                    ];
                    $this->query->setMediaMap($media, $map);
                    $this->response->success([
                        'title' => $this->presentMediaTitle($media),
                        'map' => $this->presentMediaMapForTree($media, $tree),
                        'edit' => $media->canEdit(),
                    ]);
                }
                break;
            case 'map_get':
                if ($media && $media->canShow()) {
                    $this->response->success([
                        'title' => $this->presentMediaTitle($media),
                        'map' => $this->presentMediaMapForTree($media, $tree),
                        'edit' => $media->canEdit(),
                    ]);
                }
                break;
        }
    }

    /**
     * @param Media $media
     * @param Tree $tree
     * @return array
     * @throws \Exception
     */
    private function presentMediaMapForTree(Media $media, Tree $tree)
    {
        $result = [];
        $pids = [];
        $areas = $this->query->getMediaMap($media);

        foreach ($areas as $area) {
            $pid = (string)$area['pid'];
            $result[$pid] = [
                'found' => false,
                'pid' => $pid,
                'name' => $pid,
                'life' => '',
                'coords' => $area['coords'],
            ];
            $pids[] = $pid;
        }

        if (!empty($result)) {
            foreach ($this->query->getIndividualsDataByTreeAndPids($tree, $pids) as $row) {
                $person = Individual::getInstance($row->xref, $tree, $row->gedcom);
                if ($person->canShowName()) {
                    $result[$row->xref] = array_merge($result[$row->xref], [
                        'found' => true,
                        'name' => strip_tags($person->getFullName()),
                        'life' => strip_tags($person->getLifeSpan()),
                    ]);
                }
            }
            usort($result, function ($compa, $compb) {
                return $compa['coords'][0] - $compb['coords'][0];
            });
        }

        return $result;
    }

    /**
     * @param Media $media
     * @return string
     */
    private function presentMediaTitle(Media $media)
    {
        if ($title = $media->getTitle()) {
            return $title;
        }

        $parsedFileName = pathinfo($media->getFilename());

        if (!empty($parsedFileName['filename'])) {
            return $parsedFileName['filename'];
        }

        return $media->getFilename();
    }

}