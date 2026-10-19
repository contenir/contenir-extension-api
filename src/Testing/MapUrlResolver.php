<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing;

use Contenir\Extension\Api\Url\RecordReference;
use Contenir\Extension\Api\Url\UrlResolverInterface;
use Override;

/**
 * Resolves records' URLs from a fixed map, for tests.
 *
 * @api
 */
final readonly class MapUrlResolver implements UrlResolverInterface
{
    /**
     * @param array<string, string> $urls By "<section>:<id>"
     */
    public function __construct(
        private array $urls = [],
    ) {}

    private static function key(RecordReference $record): string
    {
        return "{$record->section}:{$record->id}";
    }

    #[Override]
    public function url(RecordReference $record): ?string
    {
        return $this->urls[self::key($record)] ?? null;
    }

    /**
     * The same map with $record at $url.
     */
    public function with(RecordReference $record, string $url): self
    {
        return new self([...$this->urls, self::key($record) => $url]);
    }
}
