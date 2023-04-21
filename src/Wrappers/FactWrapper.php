<?php

namespace UksusoFF\WebtreesModules\Faces\Wrappers;

use Fisharebest\Webtrees\Fact;
use Fisharebest\Webtrees\Gedcom;
use Fisharebest\Webtrees\Registry;

class FactWrapper
{
    /**
     * Media objects linked to this fact
     *
     * @return array<\Fisharebest\Webtrees\Media>
     */
    public static function getMedia(Fact $fact): array
    {
        $media = [];

        preg_match_all('/\n2 OBJE @(' . Gedcom::REGEX_XREF . ')@/', $fact->gedcom(), $matches);

        foreach ($matches[1] as $match) {
            $obje = Registry::mediaFactory()->make($match, $fact->record()->tree());
            if ($obje && $obje->canShow()) {
                $media[] = $obje;
            }
        }

        return $media;
    }
}
