<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->in(__DIR__);

return PhpCsFixer\Config::create()
    ->setUsingCache(true)
    ->setRules(array(
        '@Symfony' => true,
        'concat_space' => array(
            'spacing' => 'one',
        ),
        'yoda_style' => array(
            'equal' => null,
            'identical' => null,
            'less_and_greater' => null,
        ),
        'ordered_imports' => true,
    ))
    ->setFinder($finder);
