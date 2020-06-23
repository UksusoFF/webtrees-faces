<?php

namespace UksusoFF\WebtreesModules\Faces\Helpers;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

class DatabaseHelper
{
    public function getIndividualsDataByTreeAndPids(string $tree, array $pids): Collection
    {
        return DB::table('name')
            ->join('individuals', static function(JoinClause $join): void {
                $join
                    ->on('i_file', '=', 'n_file')
                    ->on('i_id', '=', 'n_id');
            })
            ->where('n_type', '=', 'NAME')
            ->where('i_file', '=', $tree)
            ->whereIn('i_id', $pids)
            ->orderBy('n_full')
            ->select([
                'i_id AS xref',
                'i_gedcom AS gedcom',
                'n_full',
            ])
            ->get();
    }

    public function getMediaMap(
        int $tree,
        string $media,
        int $order
    ): ?string {
        return DB::table('media_faces')
            ->where('f_m_id', '=', $media)
            ->where('f_m_order', '=', $order)
            ->where('f_m_tree', '=', $tree)
            ->value('f_coordinates');
    }

    public function setMediaMap(
        int $tree,
        string $media,
        int $order,
        ?string $filename = null,
        ?string $map = null
    ): ?int {
        if ($map === null) {
            return DB::table('media_faces')
                ->where('f_m_id', '=', $media)
                ->where('f_m_order', '=', $order)
                ->where('f_m_tree', '=', $tree)
                ->delete();
        }

        DB::table('media_faces')->updateOrInsert([
            'f_m_id' => $media,
            'f_m_order' => $order,
            'f_m_tree' => $tree,
        ], [
            'f_coordinates' => $map,
            'f_m_filename' => $filename,
        ]);

        return null;
    }

    public function getMediaList(?string $media, ?string $person, ?string $search, int $start, int $length): array
    {
        $query = DB::table('media_faces');

        if ($media !== null) {
            $query->where('f_m_id', '=', $media);
        }

        if ($person !== null) {
            $query->where('f_coordinates', 'LIKE', "%\"pid\":\"{$person}\"%");
        }

        if ($search !== null) {
            $query->where('f_coordinates', 'LIKE', "%{$search}%");
        }

        return [
            $query
                ->leftJoin('media', function(JoinClause $join) {
                    $join
                        ->on('f_m_id', '=', 'm_id')
                        ->on('f_m_tree', '=', 'm_file');
                })
                ->skip($start)
                ->take($length)
                ->get([
                    'f_coordinates',
                    'f_m_id',
                    'f_m_filename',
                    'f_m_order',
                    'm_file',
                ]),
            $query->count(),
        ];
    }

    public function missedNotesRepair(): int
    {
        $count = 0;

        DB::table('media_faces')
            ->leftJoin('media', function(JoinClause $join) {
                $join
                    ->on('f_m_id', '=', 'm_id')
                    ->on('f_m_tree', '=', 'm_file');
            })
            ->whereNull('media.m_id')
            ->chunkById(20, function($chunks) use (&$count) {
                foreach ($chunks as $chunk) {
                    if (($file = DB::table('media_file')
                            ->where('multimedia_file_refn', $chunk->f_m_filename)
                            ->first()) !== null) {
                        DB::table('media_faces')
                            ->where('f_id', $chunk->f_id)
                            ->update([
                                'media_faces.f_m_id' => $file->m_id,
                            ]);

                        $count++;
                    }
                }
            }, 'f_id');

        return $count;
    }

    public function missedNotesDestroy(): int
    {
        return DB::table('media_faces')
            ->leftJoin('media', function(JoinClause $join) {
                $join
                    ->on('f_m_id', '=', 'm_id')
                    ->on('f_m_tree', '=', 'm_file');
            })
            ->whereNull('media.m_id')
            ->delete();
    }
}
