<?php
/**
 * hiAPI NameSuggestion.com plugin
 *
 * @link      https://github.com/hiqdev/hiapi-namesuggestion
 * @package   hiapi-namesuggestion
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

$definitions = [
    'namesuggestionTool' => [
        '__class' => \hiapi\namesuggestion\NameSuggestionTool::class,
    ],
];

return class_exists(Yiisoft\Factory\Definitions\Reference::class) ? $definitions : ['container' => ['definitions' => $definitions]];
