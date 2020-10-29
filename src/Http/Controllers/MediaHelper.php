<?php

namespace UksusoFF\WebtreesModules\Faces\Http\Controllers;

use Fisharebest\Webtrees\Media;
use Fisharebest\Webtrees\MediaFile;

class MediaHelper
{
    public function getMediaImageFileByOrder(Media $media, int $order): ?MediaFile
    {
        foreach ($media->mediaFiles() as $o => $file) {
            /** @var \Fisharebest\Webtrees\MediaFile $file */
            if ($order === $o && $file->isImage()) {
                return $file;
            }
        }

        return null;
    }

    public function getMediaImageFileByFact(Media $media, string $fact): array
    {
        $found = null;

        foreach ($media->mediaFiles() as $o => $file) {
            /** @var \Fisharebest\Webtrees\MediaFile $file */
            if ($fact === $file->factId() && $file->isImage()) {
                return [$file, $o];
            }
        }

        return [null, 0];
    }
}
