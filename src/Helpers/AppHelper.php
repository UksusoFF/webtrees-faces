<?php

namespace UksusoFF\WebtreesModules\Faces\Helpers;

use Exception;
use Fisharebest\Webtrees\Webtrees;

class AppHelper
{
    public static function get(string $class)
    {
        if (version_compare(Webtrees::VERSION, '2.2.0', '>=')) {
            return \Fisharebest\Webtrees\Registry::container()->get($class);
        }

        if (function_exists('app')) {
            return app($class);
        }

        throw new Exception('Can not find container resolver');
    }
}
