<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('config')
    ->exclude('var')
    ->exclude('public/bundles')
    ->exclude('public/build')
    ->notPath('bin/console')
    ->notPath('public/index.php')
    ->notPath('importmap.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@PSR1' => true,
        '@PSR2' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => [
            'default' => 'align_single_space_minimal',
        ],
        'concat_space' => ['spacing' => 'one'],
        'declare_strict_types' => true,
        'function_typehint_space' => true,
        'include' => true,
        'new_with_braces' => true,
        'no_unused_imports' => true,
        'not_operator_with_successor_space' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'phpdoc_align' => true,
        'phpdoc_annotation_without_dot' => true,
        'phpdoc_indent' => true,
        'phpdoc_inline_tag_normalizer' => true,
        'phpdoc_no_access' => true,
        'phpdoc_no_package' => true,
        'phpdoc_no_useless_inheritdoc' => true,
        'phpdoc_scalar' => true,
        'phpdoc_summary' => false,
        'phpdoc_trim' => true,
        'phpdoc_types' => true,
        'phpdoc_var_without_name' => true,
        'single_trait_insert_per_statement' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters', 'match']],
        'visibility_required' => ['elements' => ['property', 'method', 'const']],
        'void_return' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'lowercase_cast' => true,
        'no_blank_lines_after_class_opening' => true,
        'no_leading_import_slash' => true,
        'no_whitespace_in_blank_line' => true,
        'return_type_declaration' => true,
        'short_scalar_cast' => true,
        'blank_lines_before_namespace' => true,
        'ternary_operator_spaces' => true,
        'native_function_invocation' => ['include' => ['@compiler_optimized']],
        'method_argument_space' => ['on_multiline' => 'ignore'],
        'ordered_class_elements' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'class_attributes_separation' => [
            'elements' => ['method' => 'one'],
        ],
    ])
    ->setFinder($finder);
