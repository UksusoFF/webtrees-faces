<?php

namespace UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers;

use Fisharebest\Webtrees\Database;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Tree;

class DatabaseHelper
{
    public static function getIndividualsDataByTreeAndPids(Tree $tree, $pids)
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

    public static function getIndividualsIdByTreeAndTerm(Tree $tree, $term)
    {
        return Database::prepare(
            "SELECT i_id AS xref, i_gedcom AS gedcom, n_full" .
            " FROM `##individuals`" .
            " JOIN `##name` ON i_id = n_id AND i_file = n_file" .
            " WHERE (n_full LIKE CONCAT('%', REPLACE(:term_1, ' ', '%'), '%') OR n_surn LIKE CONCAT('%', REPLACE(:term_2, ' ', '%'), '%')) AND i_file = :tree_id" .
            " ORDER BY n_full COLLATE :collation"
        )->execute([
            'term_1' => $term,
            'term_2' => $term,
            'tree_id' => $tree->getTreeId(),
            'collation' => I18N::collation(),
        ])->fetchAll();
    }
}