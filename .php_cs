<?php

$config = \UksusoFF\PhpCsFixer\Factory::fromRuleSet(new \UksusoFF\PhpCsFixer\RuleSet\Laravel());

$config->getFinder()->in(__DIR__);

return $config;