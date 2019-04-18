<?php

namespace UksusoFF\WebtreesModules\Faces\Helpers;

use Fisharebest\Webtrees\Database;
use Fisharebest\Webtrees\I18N;

class DatabaseHelper
{
    /**
     * @param string $tree
     * @param $pids
     *
     * @return \stdClass[]|\string[][]
     * @throws \Exception
     */
    public function getIndividualsDataByTreeAndPids($tree, $pids)
    {
        return Database::prepare(
            'SELECT i_id AS xref, i_gedcom AS gedcom, n_full' .
            ' FROM `##individuals`' .
            ' JOIN `##name` ON i_id = n_id AND i_file = n_file' .
            ' WHERE i_id IN (' . implode(',', array_fill(0, count($pids), '?')) . ') AND i_file = ?' .
            ' AND n_type=\'NAME\'' .
            ' ORDER BY n_full COLLATE ?'
        )->execute(array_merge($pids, [
            $tree,
            I18N::collation(),
        ]))->fetchAll();
    }

    /**
     * @param string $media
     *
     * @return string|null
     * @throws \Exception
     */
    public function getMediaMap($media)
    {
        return Database::prepare(
            'SELECT f_coordinates' .
            ' FROM `##media_faces`' .
            ' WHERE f_m_id = :m_id'
        )->execute([
            'm_id' => $media,
        ])->fetchOne();
    }

    /**
     * @param $media
     *
     * @return \object|null
     * @throws \Exception
     */
    public function getNote($media)
    {
        $note = Database::prepare(
            'SELECT *' .
            ' FROM `##media_faces`' .
            ' WHERE f_m_id = :m_id'
        )->execute([
            'm_id' => $media,
        ])->fetchOneRow();

        return !empty($note) ? $note : null;
    }

    /**
     * @param string $media
     * @param string|null $filename
     * @param string|null $map
     *
     * @throws \Exception
     */
    public function setMediaMap($media, $filename = null, $map = null)
    {
        if ($map === null) {
            Database::prepare(
                'DELETE' .
                ' FROM `##media_faces`' .
                ' WHERE f_m_id = :m_id'
            )->execute([
                'm_id' => $media,
            ]);
        } else if ($this->getMediaMap($media) !== null) {
            Database::prepare(
                'UPDATE `##media_faces`' .
                ' SET f_coordinates = :coordinates, f_m_filename = :m_filename' .
                ' WHERE f_m_id = :m_id'
            )->execute([
                'coordinates' => $map,
                'm_id' => $media,
                'm_filename' => $filename,
            ]);
        } else {
            Database::prepare(
                'INSERT' .
                ' INTO `##media_faces` (f_coordinates, f_m_id, f_m_filename)' .
                ' VALUES (:coordinates, :m_id, :m_filename)'
            )->execute([
                'coordinates' => $map,
                'm_id' => $media,
                'm_filename' => $filename,
            ]);
        }
    }

    /**
     * @param int $start
     * @param int $length
     *
     * @return array
     * @throws \Exception
     */
    public function getMediaList($start, $length)
    {
        $limit = $length > 0 ? "LIMIT {$start},{$length}" : '';

        $rows = Database::prepare(
            'SELECT SQL_CALC_FOUND_ROWS f_coordinates, f_m_id, f_m_filename, m_file as tree_id' .
            ' FROM `##media_faces`' .
            ' LEFT JOIN `##media` ON f_m_id = m_id' .
            " {$limit}"
        )->execute([
            //
        ])->fetchAll();

        $total = Database::prepare('SELECT FOUND_ROWS()')->fetchOne();

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
        return Database::prepare(
            'UPDATE `##media_faces`' .
            ' LEFT JOIN `##media` AS media_id_check ON f_m_id = media_id_check.m_id' .
            ' SET f_m_id = (' .
            '  SELECT media_filename_check.m_id' .
            '  FROM ##media AS media_filename_check' .
            '  WHERE media_filename_check.m_filename = f_m_filename' .
            //'  LIMIT 1' .
            ' )' .
            ' WHERE media_id_check.m_id IS NULL'
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
        return Database::prepare(
            'DELETE `##media_faces`' .
            ' FROM `##media_faces`' .
            ' LEFT JOIN `##media` AS media_id_check ON f_m_id = media_id_check.m_id' .
            ' WHERE media_id_check.m_id IS NULL'
        )->execute([
            //
        ])->rowCount();
    }
}