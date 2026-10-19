<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Operation;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;
use Contenir\Extension\Api\Internal\Values;

/**
 * A declarative, framework-agnostic extension operation — an actionable button
 * (e.g. "Clear cache", "Regenerate favicons") backed by an
 * OperationHandlerInterface.
 *
 * Operations round-trip through a host's configuration (Zend_Config in the
 * legacy CMS), so the value object is array-serialisable: toArray() emits
 * scalars only and fromArray() reads every field back. The host renders the
 * button from this metadata and dispatches to $handler when clicked.
 *
 * @api
 */
final readonly class Operation
{
    /**
     * @param class-string<OperationHandlerInterface> $handler
     *
     * @mago-expect lint:excessive-parameter-list One parameter per key of the array form hosts round-trip.
     */
    public function __construct(
        public string $name,
        public string $label,
        public string $icon,
        public string $privilege,
        public string $handler,
        public string $region = 'banner',
        public string $surface = 'update',
        public string $httpMethod = 'POST',
        public bool $confirm = false,
        public ?string $confirmMessage = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     *
     * @throws InvalidDeclarationException When a required key is missing or the handler is not an operation handler.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: Values::required($data, 'name'),
            label: Values::required($data, 'label'),
            icon: Values::required($data, 'icon'),
            privilege: Values::required($data, 'privilege'),
            handler: Names::implementing(
                'Operation handler',
                Values::required($data, 'handler'),
                OperationHandlerInterface::class,
            ),
            region: Values::string($data, 'region', 'banner'),
            surface: Values::string($data, 'surface', 'update'),
            httpMethod: Values::string($data, 'httpMethod', 'POST'),
            confirm: Values::bool($data, 'confirm'),
            confirmMessage: Values::nullableString($data, 'confirmMessage'),
        );
    }

    /**
     * @return array<string, scalar|null>
     */
    public function toArray(): array
    {
        return [
            'name'           => $this->name,
            'label'          => $this->label,
            'icon'           => $this->icon,
            'privilege'      => $this->privilege,
            'handler'        => $this->handler,
            'region'         => $this->region,
            'surface'        => $this->surface,
            'httpMethod'     => $this->httpMethod,
            'confirm'        => $this->confirm,
            'confirmMessage' => $this->confirmMessage,
        ];
    }
}
