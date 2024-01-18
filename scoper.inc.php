<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

$excludedFiles = [];

return [
    'prefix' => 'BitPayVendor',
    'finders' => [
        Finder::create()->files()->in('BitPayLib'),
        Finder::create()->files()->in('vendor')
            ->ignoreVCS(true)
            ->notName('/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.json|composer\\.lock/')
            ->exclude([
                'humbug'
            ]),
        Finder::create()->append([
            'composer.json',
        ]),
    ],
    'exclude-files' => [
        ...$excludedFiles,
    ],
    'patchers' => [
        static function (string $filePath, string $prefix, string $contents): string {
            return $contents;
        },
    ],
    'exclude-namespaces' => [
        'Humbug\PhpScoper',
        'PHP_CodeSniffer',
        'PHPCSUtils'
    ],
    'exclude-classes' => [
        'WC',
        'WC_Payment_Gateway',
        'WP_User',
        'WC_Order',
        'WP_REST_Request',
        'WC_Admin_Settings',
        'Automattic\WooCommerce\Blocks\Package',
        'Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry',
        'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType',
        'wpdb'
    ],
    'exclude-functions' => [],
    'exclude-constants' => [],
    'expose-global-constants' => true,
    'expose-global-classes' => true,
    'expose-global-functions' => true,
    'expose-namespaces' => [],
    'expose-classes' => [],
    'expose-functions' => [],
    'expose-constants' => [],
];
