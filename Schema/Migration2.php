<?php

namespace UksusoFF\WebtreesModules\Faces\Schema;

use Fisharebest\Webtrees\Database;
use Fisharebest\Webtrees\Schema\MigrationInterface;

/**
 * Rename table and columns names.
 */
class Migration2 implements MigrationInterface
{
    /** {@inheritDoc} */
    public function upgrade()
    {
        Database::exec('ALTER TABLE `##photo_notes` RENAME `##media_faces`');

        Database::exec('ALTER TABLE `##media_faces` CHANGE COLUMN pnwim_id f_id INTEGER AUTO_INCREMENT NOT NULL');
        Database::exec('ALTER TABLE `##media_faces` CHANGE COLUMN pnwim_coordinates f_coordinates MEDIUMTEXT NOT NULL');
        Database::exec('ALTER TABLE `##media_faces` CHANGE COLUMN pnwim_m_id f_m_id VARCHAR(20) NULL');
        Database::exec('ALTER TABLE `##media_faces` CHANGE COLUMN pnwim_m_filename f_m_filename VARCHAR(512) NOT NULL');
    }
}