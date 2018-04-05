<?php
/**
 * hiAPI NameSuggestion.com plugin
 *
 * @link      https://github.com/hiqdev/hiapi-namesuggestion
 * @package   hiapi-namesuggestion
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

return [
    'container' => [
        'definitions' => [
            'namesuggestionTool' => [
                'class' => \hiapi\namesuggestion\NameSuggestionTool::class,
            ],
        ],
    ],
];
