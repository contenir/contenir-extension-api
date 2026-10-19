<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Diagnostic;

/**
 * Text a guided fix asks someone to copy elsewhere, such as a cron line or
 * a server configuration snippet. The host shows it with a Copy button.
 *
 * @api
 */
final readonly class CopyText
{
    public function __construct(
        public string $label,
        public string $text,
    ) {}
}
