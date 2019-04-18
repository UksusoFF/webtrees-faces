<?php

namespace UksusoFF\WebtreesModules\Faces\Schema;

use Fisharebest\Webtrees\Database;
use Fisharebest\Webtrees\Schema\MigrationInterface;

/**
 * Migrate old records from module settings.
 */
class Migration1 implements MigrationInterface
{
    /** {@inheritDoc} */
    public function upgrade()
    {
        $settings = Database::prepare(
            'SELECT setting_name, setting_value FROM `##module_setting` WHERE module_name = ?'
        )->execute([
            'photo_note_with_image_map',
        ])->fetchAssoc();

        foreach ($settings as $key => $coordinates) {
            $id = str_replace('PNWIM_', '', $key);

            $filename = Database::prepare(
                'SELECT m_filename FROM `##media` WHERE m_id = ?'
            )->execute([
                $id,
            ])->fetchOne();

            if (!empty($filename) && !empty($coordinates)) {
                Database::prepare(
                    'INSERT INTO `##photo_notes` (pnwim_coordinates, pnwim_m_id, pnwim_m_filename) VALUES (?, ?, ?)'
                )->execute([
                    $coordinates,
                    $id,
                    $filename,
                ]);
            }
        }
    }
}