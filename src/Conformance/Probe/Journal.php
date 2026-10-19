<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use LogicException;
use Psr\Container\ContainerExceptionInterface;

use function in_array;

/**
 * What the probe add-on did, in order, for the conformance suite to read back.
 * Hosts make it available to the probe through their services.
 *
 * @api
 */
final class Journal
{
    /**
     * @var list<string>
     */
    private array $entries = [];

    private int $registrations = 0;

    /**
     * The journal a host made available to the probe.
     *
     * @throws LogicException When the host's services hold no journal.
     */
    public static function from(ExtensionContextInterface $context): self
    {
        try {
            return $context->services()->get(self::class);
        } catch (ContainerExceptionInterface $exception) {
            throw new LogicException('The host gave the probe no journal', previous: $exception);
        }
    }

    /**
     * @return list<string>
     */
    public function entries(): array
    {
        return $this->entries;
    }

    public function has(string $entry): bool
    {
        return in_array($entry, $this->entries, strict: true);
    }

    /**
     * Counts a call to the probe's register().
     */
    public function registered(): void
    {
        ++$this->registrations;
    }

    /**
     * How many times the probe has been registered.
     */
    public function registrations(): int
    {
        return $this->registrations;
    }

    public function write(string $entry): void
    {
        $this->entries[] = $entry;
    }
}
