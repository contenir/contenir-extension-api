<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Handler;

use Closure;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

use function preg_match;

/**
 * A file a handler or public handler sends, such as an export or an upload:
 * its name, its media type, and what writes it. The host sends it as an
 * attachment with that name, not cached, and streams what the writer writes
 * to the stream it is given, so a large file is never held whole.
 *
 * @api
 */
final readonly class Download
{
    /**
     * @param Closure(resource): void $writer Writes the file to the stream it is given
     *
     * @throws InvalidDeclarationException When the name is empty or could break a header, or the media type is empty.
     */
    public function __construct(
        public string $filename,
        public string $mediaType,
        public Closure $writer,
    ) {
        Names::text('A download name', $filename);
        Names::text('A download media type', $mediaType);
        if (1 === preg_match('/["\\\\\/\x00-\x1f\x7f]/', $filename)) {
            throw InvalidDeclarationException::because(
                "Download name \"{$filename}\" must not hold quotes, slashes or control characters",
            );
        }
    }
}
