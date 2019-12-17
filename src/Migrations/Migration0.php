<?php

namespace UksusoFF\WebtreesModules\Faces\Migrations;

use Fisharebest\Webtrees\Schema\MigrationInterface;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

/**
 * Upgrade the database schema from version 0 (empty database) to version 1.
 */
class Migration0 implements MigrationInterface
{
    public function upgrade(): void
    {
        if (!DB::schema()->hasTable('media_faces')) {
            DB::schema()->create('media_faces', static function(Blueprint $table): void {
                $table->unsignedInteger('f_id', true)->autoIncrement();
                $table->mediumText('f_coordinates');
                $table->string('f_m_id', 20)->nullable();
                $table->string('f_m_filename', 248);
            });
        }

        if (DB::schema()->hasColumn('media_faces', 'f_exif')) {
            DB::schema()->table('media_faces', static function(Blueprint $table): void {
                $table->dropColumn('f_exif');
            });
        }
    }
}
