<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/bin',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->append([__FILE__]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setCacheFile('build/php-cs-fixer.cache')
    ->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        '@PHP82Migration' => true,
        'declare_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'native_function_invocation' => [
            'include' => ['@compiler_optimized'],
            'scope' => 'namespaced',
            'strict' => true,
        ],
        'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => true,
            'remove_inheritdoc' => true,
        ],
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_separation' => true,
        'single_line_throw' => false,
        'trailing_comma_in_multiline' => [
            'elements' => ['arguments', 'array_destructuring', 'arrays', 'match', 'parameters'],
        ],
    ]);
