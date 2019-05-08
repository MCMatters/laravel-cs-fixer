<?php

declare(strict_types = 1);

$basePath = isset($app) ? $app->basePath() : base_path();

return [
    'class_php_docs' => [
        'paths' => [
            "{$basePath}/app",
            "{$basePath}/database",
        ],
        'replacing' => [
            'pattern' => '/<\?php\n*/',
            'replacement' => "<?php\n\ndeclare(strict_types = 1);\n\n",
        ],
    ],
    'declare_strict_types' => [
        'paths' => [
            "{$basePath}/app",
            "{$basePath}/bootstrap",
            "{$basePath}/config",
            "{$basePath}/database",
            "{$basePath}/public",
            "{$basePath}/routes",
        ],
    ],
];
