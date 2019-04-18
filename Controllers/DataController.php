<?php

namespace UksusoFF\WebtreesModules\Faces\Controllers;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Filter;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Media;
use Fisharebest\Webtrees\Tree;
use UksusoFF\WebtreesModules\Faces\Helpers\DatabaseHelper as DB;

class DataController
{
    private $query;

    public function __construct(DB $query)
    {
        $this->query = $query;
    }

    /**
     * @param string $action
     *
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
        if (($media = $this->getMediaFromInput()) && $media->canShow()) {
            return [
                'title' => $this->getMediaTitle($media),
                'map' => $this->getMediaMapForTree($media, $this->getTree()),
                'edit' => $media->canEdit(),
            ];
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

            if ($pid !== null && $coords !== null && $media->canEdit()) {
                $map = $this->getMediaMap($media);

                $map[] = (object)[
                    'pid' => $pid,
                    'coords' => $coords,
                ];

                $this->setMediaMap($media, $media->getFilename(), $map);

                return [
                    'title' => $this->getMediaTitle($media),
                    'map' => $this->getMediaMapForTree($media, $this->getTree()),
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

            if ($pid !== null && $media->canEdit()) {
                $map = array_filter($this->getMediaMap($media), function($area) use ($pid) {
                    return !empty($area['pid']) && $area['pid'] !== $pid;
                });

                $this->setMediaMap($media, $media->getFilename(), $map);

                return [
                    'title' => $this->getMediaTitle($media),
                    'map' => $this->getMediaMapForTree($media, $this->getTree()),
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
                $this->setMediaMap($media);

                return [
                    'title' => $this->getMediaTitle($media),
                    'map' => $this->getMediaMapForTree($media, $this->getTree()),
                    'edit' => $media->canEdit(),
                ];
            }
        } elseif ($note = $this->getNoteFromInput()) {
            if (Auth::isAdmin()) {
                $this->setMediaMap($note->f_m_id);

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

    /**
     * @return object|null
     * @throws \Exception
     */
    private function getNoteFromInput()
    {
        $mid = Filter::get('mid') ?: Filter::post('mid');

        return $this->query->getNote($mid);
    }

    /**
     * @param \Fisharebest\Webtrees\Media $media
     * @param \Fisharebest\Webtrees\Tree $tree
     *
     * @return array
     * @throws \Exception
     */
    private function getMediaMapForTree(Media $media, Tree $tree)
    {
        $result = [];
        $pids = [];
        $areas = $this->getMediaMap($media);

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
                if ($person !== null && $person->canShowName()) {
                    $result[$row->xref] = array_merge($result[$row->xref], [
                        'found' => true,
                        'name' => strip_tags($person->getFullName()),
                        'life' => strip_tags($person->getLifeSpan()),
                    ]);
                }
            }
            usort($result, function($compa, $compb) {
                return $compa['coords'][0] - $compb['coords'][0];
            });
        }

        return $result;
    }

    /**
     * @param \Fisharebest\Webtrees\Media $media
     *
     * @return string
     */
    private function getMediaTitle(Media $media)
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

    /**
     * @param \Fisharebest\Webtrees\Media $media
     *
     * @return array|mixed
     * @throws \Exception
     */
    private function getMediaMap(Media $media)
    {
        $map = $this->query->getMediaMap($media->getXref());

        if ($map !== null) {
            return json_decode($map, true);
        } else {
            return [];
        }
    }

    /**
     * @param \Fisharebest\Webtrees\Media $media
     * @param string|null $filename
     * @param array $map
     *
     * @throws \Exception
     */
    private function setMediaMap(Media $media, $filename = null, $map = [])
    {
        $this->query->setMediaMap($media->getXref(), $filename, empty($map) ? null : json_encode($map));
    }
}