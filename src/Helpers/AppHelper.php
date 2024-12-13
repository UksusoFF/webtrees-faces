<?php

namespace UksusoFF\WebtreesModules\Faces\Helpers;

use Exception;
use Fisharebest\Webtrees\Webtrees;

class AppHelper
{
    /**
     * @param string $class
     * @return mixed
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Exception
     */
    public static function get(string $class)
    {
        if (version_compare(Webtrees::VERSION, '2.2.0', '>=')) {// @phpstan-ignore-line
            return \Fisharebest\Webtrees\Registry::container()->get($class);
        }

        if (function_exists('app')) {// @phpstan-ignore-line
            return app($class);
        }

        throw new Exception('Can not find container resolver');
    }
}
