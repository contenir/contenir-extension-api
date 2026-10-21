<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Handler;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * The page a screen belongs under, leading to one of the add-on's handlers:
 * the host draws it in the breadcrumb between the area and the screen, so a
 * form's notification reads Forms › the form › the notification.
 *
 * @api
 */
final readonly class ParentPage
{
    /**
     * @param array<string, scalar> $query
     *
     * @throws InvalidDeclarationException When the title is empty or the handler name malformed.
     */
    public function __construct(
        public string $title,
        public string $handler,
        public array $query = [],
    ) {
        Names::text('A parent page title', $title);
        Names::name('Handler', $handler);
    }
}
