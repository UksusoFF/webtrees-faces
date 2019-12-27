<?php

namespace UksusoFF\WebtreesModules\Faces\Helpers;

class ExifHelper
{
    public function getMediaMap(string $file): ?array
    {
        if (
            ($xmp = $this->getMeta($file))
            && ($faces = $this->getFaces($xmp))
        ) {
            $result = [];

            [$width, $height] = getimagesize($file);

            foreach ($faces as $name => $face) {
                $result[] = [
                    'pid' => $name,
                    'coords' => $this->getCoordinates($face, $width, $height),
                ];
            }

            return $result;
        }

        return null;
    }

    private function getCoordinates($face, $width, $height): ?array
    {
        $x = (int)round($width * ($face['stArea:x'] - $face['stArea:w'] / 2));
        $y = (int)round($height * ($face['stArea:y'] - $face['stArea:h'] / 2));
        $w = (int)round($width * $face['stArea:w']);
        $h = (int)round($height * $face['stArea:h']);

        return $face['stArea:unit'] === 'normalized' ? [
            $x,
            $y,
            $x + $w,
            $y + $h,
        ] : null;
    }

    private function getMeta($file)
    {
        $content = file_get_contents($file);
        $xmp_data_start = strpos($content, '<x:xmpmeta');
        $xmp_data_end = strpos($content, '</x:xmpmeta>');
        $xmp_length = $xmp_data_end - $xmp_data_start;

        return substr($content, $xmp_data_start, $xmp_length + 12);
    }

    private function getFaces($xmp): array
    {
        $faces = [];
        $name = '';

        $xml_parser = xml_parser_create('UTF-8');
        xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, 0);
        xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parse_into_struct($xml_parser, $xmp, $vals, $index);
        xml_parser_free($xml_parser);

        foreach ($vals as $val) {
            switch ($val['tag']) {
                case 'rdf:Description':
                    if (
                        $val['type'] === 'open'
                        && !empty($val['attributes']['mwg-rs:Name'])
                        && $val['attributes']['mwg-rs:Type'] === 'Face'
                    ) {
                        $name = trim($val['attributes']['mwg-rs:Name']);
                    }
                    if ($val['type'] === 'close') {
                        $name = '';
                    }
                    break;
                case 'mwg-rs:Area':
                    if (($val['type'] === 'complete') && $name) {
                        $faces[$name] = $val['attributes'];
                    }
                    break;
            }
        }

        return $faces;
    }
}
