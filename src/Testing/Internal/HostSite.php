<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Site\SiteAnswer;
use Contenir\Extension\Api\Site\SitePath;
use Contenir\Extension\Api\Site\SiteRequestsInterface;
use Contenir\Extension\Api\Url\RecordReference;
use Override;

/**
 * A site that answers each path as a test said it should, and nothing for a
 * path no test mentioned or a record that isn't stored.
 *
 * @internal
 */
final class HostSite implements SiteRequestsInterface
{
    /** @var array<string, SiteAnswer> */
    private array $answers = [];

    /** @var list<array{record: RecordReference, path: string, fields: array<string, string>}> */
    private array $posts = [];

    public function __construct(
        private readonly Records $records,
    ) {}

    public function answer(string $path, SiteAnswer $answer): void
    {
        $this->answers[SitePath::check($path)] = $answer;
    }

    #[Override]
    public function post(RecordReference $record, string $path, array $fields = []): ?SiteAnswer
    {
        SitePath::check($path);
        if (null === $this->records->get($record->section, $record->id)) {
            return null;
        }

        $this->posts[] = ['record' => $record, 'path' => $path, 'fields' => $fields];

        return $this->answers[$path] ?? null;
    }

    /**
     * @return list<array{record: RecordReference, path: string, fields: array<string, string>}>
     */
    public function posts(): array
    {
        return $this->posts;
    }
}
