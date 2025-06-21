<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__.'/src', __DIR__.'/tests'])
    ->exclude(['bin', 'var', 'vendor'])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setIndent('    ')
    ->setLineEnding("\n")
    ->setCacheFile('var/cache/.php-cs-fixer.cache')
    ->setRules([
        '@PSR12' => true,
        'declare_strict_types' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'no_extra_blank_lines' => true,
        'single_quote' => true,
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,
        'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],

        'global_namespace_import' => [
            'import_classes' => true,
            'import_functions' => true,
            'import_constants' => true,
        ],
        'concat_space' => ['spacing' => 'one'],

        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
        'no_superfluous_phpdoc_tags' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
;
