<?php

namespace UksusoFF\WebtreesModules\Faces\Helpers;

class TemplateHelper
{
    protected $path;

    public function __construct($path)
    {
        $this->path = "$path/_resources/templates/";
    }

    /**
     * @param string|array $files
     * @param array $values
     *
     * @return string
     */
    public function output($files, $values = [])
    {
        $output = '';

        if (is_string($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            $file = $this->path . $file;

            if (!file_exists($file)) {
                $template = "Error loading template file ($file).";
            } else {
                $template = file_get_contents($file);

                foreach ($values as $key => $value) {
                    $tag = "[@$key]";
                    $template = str_replace($tag, $value, $template);
                }
            }

            $output .= PHP_EOL . $template;
        }

        return $output;
    }
}