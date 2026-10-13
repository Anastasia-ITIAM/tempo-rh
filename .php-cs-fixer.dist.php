<?php


$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('config')
    ->exclude('var')
    ->exclude('public/bundles')
    ->exclude('public/build')
    ->notPath('bin/console')
    ->notPath('public/index.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@PSR12' => true,
        'array_indentation' => true,
        'array_syntax' => ['syntax' => 'short'],
        // '@Symfony:risky' => true,
        '@PHP82Migration'   => true,
        'linebreak_after_opening_tag' => true,
        'mb_str_functions' => true,
        'no_php4_constructor' => true,
        'no_unreachable_default_argument_value' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'php_unit_strict' => true,
        'phpdoc_order' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'concat_space' => ['spacing' => 'one'],
        'phpdoc_single_line_var_spacing' => false,
        'single_quote' => false,
        'return_type_declaration' => false,
        'binary_operator_spaces' => false,
        'phpdoc_summary' => false,
        'yoda_style' => false,
        'no_extra_blank_lines' => false,
        'ordered_imports' => false,
        'no_whitespace_in_blank_line' => false,
        'phpdoc_to_comment' => false,
        'phpdoc_align' => false,
        'phpdoc_to_param_type' => true,
        'phpdoc_to_property_type' => true,
        'phpdoc_to_return_type' => true,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/var/.php_cs.cache');
