<?php

namespace UksusoFF\WebtreesModules\Faces\Migrations;

use Fisharebest\Webtrees\Schema\MigrationInterface;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

/**
 * Upgrade the database schema from version 0 (empty database) to version 1.
 */
class Migration1 implements MigrationInterface
{
    public function upgrade(): void
    {
        if (DB::schema()->hasTable('photo_notes')) {
            DB::schema()->dropIfExists('media_faces');

            DB::schema()->table('photo_notes', static function(Blueprint $table): void {
                $table->renameColumn('pnwim_id', 'f_id');
                $table->renameColumn('pnwim_coordinates', 'f_coordinates');
                $table->renameColumn('pnwim_m_id', 'f_m_id');
                $table->renameColumn('pnwim_m_filename', 'f_m_filename');
            });

            DB::schema()->rename('photo_notes', 'media_faces');
        }

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
