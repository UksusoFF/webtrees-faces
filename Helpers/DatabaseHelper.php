<?php

namespace UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers;

use Fisharebest\Webtrees\Database;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Tree;

class DatabaseHelper
{
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
}