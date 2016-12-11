<?php

$finder = PhpCsFixer\Finder::create()->in(__DIR__);

return PhpCsFixer\Config::create()
    ->setUsingCache(true)
    ->setRules(array(
        '@Symfony' => true,
        'concat_space' => array('spacing' => 'one'),
    ))
    ->setFinder($finder);
