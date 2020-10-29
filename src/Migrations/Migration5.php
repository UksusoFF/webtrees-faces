<?php

namespace UksusoFF\WebtreesModules\Faces\Migrations;

use Fisharebest\Webtrees\Schema\MigrationInterface;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

/**
 * Migrate data from oldest versions.
 */
class Migration5 implements MigrationInterface
{
    public function upgrade(): void
    {
        DB::schema()->table('media_faces', static function(Blueprint $table): void {
            $table->unsignedInteger('f_m_tree')->nullable();
        });

        DB::table('media_faces')
            ->leftJoin('media', 'f_m_id', '=', 'm_id')
            ->update([
                'f_m_tree' => DB::raw('m_file'),
            ]);
    }
}
