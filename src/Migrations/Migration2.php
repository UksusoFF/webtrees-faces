<?php

namespace UksusoFF\WebtreesModules\Faces\Migrations;

use Fisharebest\Webtrees\Schema\MigrationInterface;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

/**
 * Normalize the database schema from inconsistent previous versions (before 2.0 release).
 */
class Migration2 implements MigrationInterface
{
    public function upgrade(): void
    {
        DB::schema()->table('media_faces', static function(Blueprint $table): void {
            $table->unsignedInteger('f_id', true)->autoIncrement()->change();
            $table->mediumText('f_coordinates')->change();
            $table->string('f_m_id', 20)->nullable()->change();
            $table->string('f_m_filename', 248)->nullable()->change();
        });
    }
}
