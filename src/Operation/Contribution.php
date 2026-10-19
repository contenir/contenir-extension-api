<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Operation;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Values;

/**
 * A declarative, non-button UI fragment an extension places into a named region
 * of the settings page: free `text`/markup, or a `link`. Actionable buttons are
 * modelled as Operations (which already carry a banner|aside region), so a
 * Contribution is never a form-posting action.
 *
 * Regions: `aside`, `pre`, `post`, `layout-nav`. Round-trips through a host's
 * configuration like Operation, hence the array (de)serialisers.
 *
 * @api
 */
final readonly class Contribution
{
    /**
     * @mago-expect lint:excessive-parameter-list One parameter per key of the array form hosts round-trip.
     */
    public function __construct(
        public string $region,
        public string $type,
        public string $privilege = 'update',
        public string $label = '',
        public string $icon = '',
        public string $url = '',
        public string $content = '',
    ) {}

    /**
     * @param array<string, mixed> $data
     *
     * @throws InvalidDeclarationException When the region or type is missing.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            region: Values::required($data, 'region'),
            type: Values::required($data, 'type'),
            privilege: Values::string($data, 'privilege', 'update'),
            label: Values::string($data, 'label'),
            icon: Values::string($data, 'icon'),
            url: Values::string($data, 'url'),
            content: Values::string($data, 'content'),
        );
    }

    /**
     * @return array<string, scalar>
     */
    public function toArray(): array
    {
        return [
            'region'    => $this->region,
            'type'      => $this->type,
            'privilege' => $this->privilege,
            'label'     => $this->label,
            'icon'      => $this->icon,
            'url'       => $this->url,
            'content'   => $this->content,
        ];
    }
}
