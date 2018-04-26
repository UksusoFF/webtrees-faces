<?php

namespace UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Schema;

use Fisharebest\Webtrees\Database;
use Fisharebest\Webtrees\Schema\MigrationInterface;

/**
 * Upgrade the database schema from version 0 (empty database) to version 1.
 */
class Migration0 implements MigrationInterface
{
    /** {@inheritDoc} */
    public function upgrade()
    {
        Database::exec(
            "CREATE TABLE IF NOT EXISTS `##photo_notes` (" .
            " pnwim_id            INTEGER        AUTO_INCREMENT   NOT NULL," .
            " pnwim_coordinates   MEDIUMTEXT                      NOT NULL," .
            " pnwim_m_id          VARCHAR(20)                     NOT NULL," .
            " pnwim_m_filename    VARCHAR(512)                    NOT NULL," .
            " PRIMARY KEY         (pnwim_id)" .
            ") COLLATE utf8_unicode_ci ENGINE=InnoDB"
        );
    }
}