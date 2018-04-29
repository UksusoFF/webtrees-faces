<?php

namespace UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers;

class TemplateHelper
{
    protected $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * @param string $file
     * @param array $values
     * @return string
     */
    public function output($file, $values = [])
    {
        $file = $this->path . $file;

        if (!file_exists($file)) {
            return "Error loading template file ($file).";
        }

        $output = file_get_contents($file);

        foreach ($values as $key => $value) {
            $tag = "[@$key]";
            $output = str_replace($tag, $value, $output);
        }

        return $output;
    }
}