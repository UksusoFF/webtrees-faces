<?php

namespace UksusoFF\WebtreesModules\Faces\Migrations;

use Fisharebest\Webtrees\Schema\MigrationInterface;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Migrate data from oldest versions.
 */
class Migration3 implements MigrationInterface
{
    public function upgrade(): void
    {
        $settings = DB::table('module_setting')->where('module_name', 'LIKE', '%photo_note_with_image_map%')->get();

        foreach ($settings as $setting) {
            $id = str_replace('PNWIM_', '', $setting->setting_name);

            $media = DB::table('media')->where('m_id', $id)->first();

            if (!empty($media) && !empty($setting->setting_value)) {
                DB::table('media_faces')->insert([
                    'f_coordinates' => $setting->setting_value,
                    'f_m_id' => $id,
                    'f_m_filename' => null,
                ]);
                DB::table('module_setting')
                    ->where('module_name', 'LIKE', '%photo_note_with_image_map%')
                    ->where('setting_name', $setting->setting_name)
                    ->delete();
            }
        }
    }
}
