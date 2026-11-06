<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Panel;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

/**
 * An image a record panel shows under its title, such as a record's share image. The host crops
 * it to the shape the panel gives images (1.91 to 1, the shape of a share image), so an add-on
 * names the picture and what it shows, not its size. The alt text may be empty for a picture that
 * only decorates.
 *
 * @api
 */
final readonly class PanelImage
{
    /**
     * @throws InvalidDeclarationException When the URL is empty.
     */
    public function __construct(
        public string $url,
        public string $alt = '',
    ) {
        Names::text('A panel image URL', $url);
    }
}
