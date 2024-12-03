<?php

$config = \UksusoFF\PhpCsFixer\Factory::fromRuleSet(new \UksusoFF\PhpCsFixer\RuleSet\Laravel(), [
    'declare_strict_types' => false,
]);

$config->getFinder()->in(__DIR__);

return $config;