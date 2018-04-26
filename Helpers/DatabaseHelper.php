<?php

namespace UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers;

use Fisharebest\Webtrees\Database;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Media;
use Fisharebest\Webtrees\Tree;

class DatabaseHelper
{
    /**
     * @param Tree $tree
     * @param $pids
     * @return \stdClass[]|\string[][]
     * @throws \Exception
     */
    public function getIndividualsDataByTreeAndPids(Tree $tree, $pids)
    {
        return Database::prepare(
            "SELECT i_id AS xref, i_gedcom AS gedcom, n_full" .
            " FROM `##individuals`" .
            " JOIN `##name` ON i_id = n_id AND i_file = n_file" .
            " WHERE i_id IN (" . implode(',', array_fill(0, count($pids), '?')) . ") AND i_file = ?" .
            " AND n_type='NAME'" .
            " ORDER BY n_full COLLATE ?"
        )->execute(array_merge($pids, [
            $tree->getTreeId(),
            I18N::collation(),
        ]))->fetchAll();
    }

    /**
     * @param Media $media
     * @return array
     * @throws \Exception
     */
    public function getMediaMap(Media $media)
    {
        $map = Database::prepare(
            "SELECT pnwim_coordinates AS pnwim_coordinates FROM `##photo_notes` WHERE pnwim_m_id = ?"
        )->execute([
            $media->getXref(),
        ])->fetchOne();

        if (!empty($map)) {
            return json_decode($map, true);
        } else {
            return [];
        }
    }

    /**
     * @param Media $media
     * @param $map
     * @throws \Exception
     */
    public function setMediaMap(Media $media, $map)
    {
        if (empty($map)) {
            Database::prepare(
                "DELETE FROM `##photo_notes` WHERE pnwim_m_id = ?"
            )->execute([
                $media->getXref(),
            ]);
        } else {
            if (!empty($this->getMediaMap($media))) {
                Database::prepare(
                    "UPDATE `##photo_notes` SET pnwim_coordinates = ?, pnwim_m_filename = ? WHERE pnwim_m_id = ?"
                )->execute([
                    json_encode($map),
                    $media->getFilename(),
                    $media->getXref(),
                ]);
            } else {
                Database::prepare(
                    "INSERT INTO `##photo_notes` (pnwim_coordinates, pnwim_m_id, pnwim_m_filename) VALUES (?, ?, ?)"
                )->execute([
                    json_encode($map),
                    $media->getXref(),
                    $media->getFilename(),
                ]);
            }
        }
    }
}