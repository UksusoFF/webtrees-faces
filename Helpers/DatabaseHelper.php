<?php

namespace UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers;

use Fisharebest\Webtrees\Database;
use Fisharebest\Webtrees\I18N;

class DatabaseHelper
{
    /**
     * @param string $tree
     * @param $pids
     * @return \stdClass[]|\string[][]
     * @throws \Exception
     */
    public function getIndividualsDataByTreeAndPids($tree, $pids)
    {
        return Database::prepare(
            "SELECT i_id AS xref, i_gedcom AS gedcom, n_full" .
            " FROM `##individuals`" .
            " JOIN `##name` ON i_id = n_id AND i_file = n_file" .
            " WHERE i_id IN (" . implode(',', array_fill(0, count($pids), '?')) . ") AND i_file = ?" .
            " AND n_type='NAME'" .
            " ORDER BY n_full COLLATE ?"
        )->execute(array_merge($pids, [
            $tree,
            I18N::collation(),
        ]))->fetchAll();
    }

    /**
     * @param string $media
     * @return array
     * @throws \Exception
     */
    public function getMediaMap($media)
    {
        $map = Database::prepare(
            "SELECT pnwim_coordinates" .
            " FROM `##photo_notes`" .
            " WHERE pnwim_m_id = :m_id"
        )->execute([
            'm_id' => $media,
        ])->fetchOne();

        if (!empty($map)) {
            return json_decode($map, true);
        } else {
            return [];
        }
    }

    /**
     * @param $media
     * @return \object|null
     * @throws \Exception
     */
    public function getNote($media)
    {
        $note = Database::prepare(
            "SELECT *" .
            " FROM `##photo_notes`" .
            " WHERE pnwim_m_id = :m_id"
        )->execute([
            'm_id' => $media,
        ])->fetchOneRow();

        return !empty($note) ? $note : null;
    }

    /**
     * @param string $media
     * @param string $filename
     * @param array $map
     * @throws \Exception
     */
    public function setMediaMap($media, $filename = null, $map = [])
    {
        if (empty($map)) {
            Database::prepare(
                "DELETE FROM `##photo_notes`" .
                " WHERE pnwim_m_id = :m_id"
            )->execute([
                'm_id' => $media,
            ]);
        } else {
            if (!empty($this->getMediaMap($media))) {
                Database::prepare(
                    "UPDATE `##photo_notes`" .
                    " SET pnwim_coordinates = :coordinates, pnwim_m_filename = :m_filename" .
                    " WHERE pnwim_m_id = :m_id"
                )->execute([
                    'coordinates' => json_encode($map),
                    'm_id' => $media,
                    'm_filename' => $filename,
                ]);
            } else {
                Database::prepare(
                    "INSERT INTO `##photo_notes` (pnwim_coordinates, pnwim_m_id, pnwim_m_filename)" .
                    " VALUES (:coordinates, :m_id, :m_filename)"
                )->execute([
                    'coordinates' => json_encode($map),
                    'm_id' => $media,
                    'm_filename' => $filename,
                ]);
            }
        }
    }

    /**
     * @param int $start
     * @param int $length
     * @return array
     * @throws \Exception
     */
    public function getMediaList($start, $length)
    {
        if ($length > 0) {
            $limit = " LIMIT " . $start . ',' . $length;
        } else {
            $limit = "";
        }

        $rows = Database::prepare("SELECT SQL_CALC_FOUND_ROWS pnwim_coordinates, pnwim_m_id, pnwim_m_filename, m_file as tree_id" .
            " FROM `##photo_notes`" .
            " LEFT JOIN `##media` ON pnwim_m_id = m_id" .
            " {$limit}"
        )->execute([
            //
        ])->fetchAll();

        $total = Database::prepare("SELECT FOUND_ROWS()")->fetchOne();

        return [
            $rows,
            $total,
        ];
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function missedNotesRepair()
    {
        return Database::prepare("UPDATE `##photo_notes`" .
            " LEFT JOIN `##media` AS media_id_check ON pnwim_m_id = media_id_check.m_id" .
            " SET pnwim_m_id = (" .
            "  SELECT media_filename_check.m_id" .
            "  FROM ##media AS media_filename_check" .
            "  WHERE media_filename_check.m_filename = pnwim_m_filename" .
            " )" .
            " WHERE media_id_check.m_id IS NULL"
        )->execute([
            //
        ])->rowCount();
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function missedNotesDelete()
    {
        return Database::prepare("DELETE `##photo_notes`" .
            " FROM `##photo_notes`" .
            " LEFT JOIN `##media` AS media_id_check ON pnwim_m_id = media_id_check.m_id" .
            " WHERE media_id_check.m_id IS NULL"
        )->execute([
            //
        ])->rowCount();
    }
}