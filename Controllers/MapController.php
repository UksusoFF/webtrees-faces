<?php

namespace UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Controllers;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Filter;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Media;
use Fisharebest\Webtrees\Tree;
use UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers\DatabaseHelper as DB;

class MapController
{
    private $query;

    public function __construct(DB $query)
    {
        $this->query = $query;
    }

    /**
     * @param $action
     * @return array|int|null
     * @throws \Exception
     */
    public function action($action)
    {
        if ($tree = $this->getTree()) {
            switch ($action) {
                case 'note_get':
                    return $this->noteGet();
                case 'note_add':
                    return $this->noteAdd();
                case 'note_delete':
                    return $this->noteDelete();
                case 'note_destroy':
                    return $this->noteDestroy();
                default:
                    return null;
            }
        } else {
            return 404;
        }
    }

    /**
     * @return array|null
     * @throws \Exception
     */
    private function noteGet()
    {
        if ($media = $this->getMediaFromInput()) {
            if ($media->canShow()) {

                return [
                    'title' => $this->presentMediaTitle($media),
                    'map' => $this->presentMediaMapForTree($media, $this->getTree()),
                    'edit' => $media->canEdit(),
                ];
            }
        }

        return null;
    }

    /**
     * @return array|null
     * @throws \Exception
     */
    private function noteAdd()
    {
        if ($media = $this->getMediaFromInput()) {
            $pid = Filter::post('pid');
            $coords = Filter::post('coords');

            if ($media->canEdit() && $pid !== null && $coords !== null) {
                $map = $this->query->getMediaMap($media->getXref());

                $map[] = (object)[
                    'pid' => $pid,
                    'coords' => $coords,
                ];

                $this->query->setMediaMap($media->getXref(), $media->getFilename(), $map);

                return [
                    'title' => $this->presentMediaTitle($media),
                    'map' => $this->presentMediaMapForTree($media, $this->getTree()),
                    'edit' => $media->canEdit(),
                ];
            }
        }

        return null;
    }

    /**
     * @return array|null
     * @throws \Exception
     */
    private function noteDelete()
    {
        if ($media = $this->getMediaFromInput()) {
            $pid = Filter::post('pid');

            if ($media->canEdit() && $pid !== null) {
                $map = array_filter($this->query->getMediaMap($media->getXref()), function ($area) use ($pid) {
                    return !empty($area['pid']) && $area['pid'] != $pid;
                });

                $this->query->setMediaMap($media->getXref(), $media->getFilename(), $map);

                return [
                    'title' => $this->presentMediaTitle($media),
                    'map' => $this->presentMediaMapForTree($media, $this->getTree()),
                    'edit' => $media->canEdit(),
                ];
            }
        }

        return null;
    }

    /**
     * @return array|null
     * @throws \Exception
     */
    private function noteDestroy()
    {
        if ($media = $this->getMediaFromInput()) {
            if ($media->canEdit()) {

                $this->query->setMediaMap($media->getXref());

                return [
                    'title' => $this->presentMediaTitle($media),
                    'map' => $this->presentMediaMapForTree($media, $this->getTree()),
                    'edit' => $media->canEdit(),
                ];
            }
        } elseif ($note = $this->getNoteFromInput()) {
            if (Auth::isAdmin()) {

                $this->query->setMediaMap($note->pnwim_m_id);

                return [];
            }
        }

        return null;
    }

    /**
     * @return \Fisharebest\Webtrees\Tree
     */
    private function getTree()
    {
        global $WT_TREE;

        return $WT_TREE;
    }

    /**
     * @return \Fisharebest\Webtrees\Media|null
     * @throws \Exception
     */
    private function getMediaFromInput()
    {
        $mid = Filter::get('mid') ?: Filter::post('mid');

        return !empty($mid) ? Media::getInstance($mid, $this->getTree()) : null;
    }

    private function getNoteFromInput()
    {
        $mid = Filter::get('mid') ?: Filter::post('mid');

        return $this->query->getNote($mid);
    }

    /**
     * @param \Fisharebest\Webtrees\Media $media
     * @param \Fisharebest\Webtrees\Tree $tree
     * @return array
     * @throws \Exception
     */
    private function presentMediaMapForTree(Media $media, Tree $tree)
    {
        $result = [];
        $pids = [];
        $areas = $this->query->getMediaMap($media->getXref());

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
            foreach ($this->query->getIndividualsDataByTreeAndPids($tree->getTreeId(), $pids) as $row) {
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
     * @param \Fisharebest\Webtrees\Media $media
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