<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Event\ActionResult;
use Contenir\Extension\Api\Event\Outcome;
use Contenir\Extension\Api\Handler\Privilege;
use Contenir\Extension\Api\Record\LinkableSection;
use Contenir\Extension\Api\Record\NewRecord;
use Contenir\Extension\Api\Record\RecordsInterface;
use Contenir\Extension\Api\Record\RecordStatus;
use Contenir\Extension\Api\Record\RecordSummary;
use Contenir\Extension\Api\Record\Workflow;
use Contenir\Extension\Api\Url\RecordReference;
use Override;

use function array_key_exists;
use function array_values;
use function sprintf;

/**
 * The in-memory host's records, lent to add-ons as RecordsInterface. A
 * record's title, path and status are its "title", "path" and "status"
 * values; one without a status is published. Each linkable section is its
 * own ACL resource, and a new record is saved as an editor's would be.
 *
 * @internal
 */
final class HostRecords implements RecordsInterface
{
    public const string PATH_TAKEN = 'That path is already in use';

    public const string NO_WORKFLOW = 'There is no such template';

    /**
     * @var array<string, LinkableSection> By section, in the site's order
     */
    private array $linkable = [];

    public function __construct(
        private readonly Records $records,
        private readonly Dispatcher $dispatcher,
        private readonly SignedIn $signedIn,
    ) {}

    #[Override]
    public function create(NewRecord $record): ActionResult
    {
        $section = $this->linkable[$record->section] ?? null;
        if (null === $section || ! $section->creatable) {
            return new ActionResult(
                Outcome::Stopped,
                null,
                sprintf('New records can\'t be made in %s', $record->section),
            );
        }

        if (! $this->signedIn->may($section->section, Privilege::Add)) {
            return new ActionResult(Outcome::Stopped, null, sprintf('You may not add to %s', $section->section));
        }

        $workflow = null === $record->workflow ? $section->workflows[0] ?? null : $section->workflow($record->workflow);
        $errors   = $this->errors($record, $workflow);
        if ([] !== $errors) {
            return new ActionResult(Outcome::Stopped, null, Saver::HELD, errors: $errors);
        }

        return (new Saver($this->records, $this->dispatcher))->save($section->section, null, [
            'title'    => $record->title,
            'path'     => $record->path,
            'status'   => RecordStatus::Draft->value,
            'workflow' => $workflow?->id,
            'parent'   => $record->parent?->id,
        ]);
    }

    #[Override]
    public function describe(RecordReference $record): ?RecordSummary
    {
        if (! array_key_exists($record->section, $this->linkable)) {
            return null;
        }

        $values = $this->records->get($record->section, $record->id);

        return null === $values ? null : RecordValues::summary($record, $values);
    }

    #[Override]
    public function isPathFree(string $path): bool
    {
        return RecordSearch::isPathFree($this->records, $path);
    }

    /**
     * Marks $section as linkable, after those already marked.
     */
    public function link(LinkableSection $section): void
    {
        $this->linkable[$section->section] = $section;
    }

    #[Override]
    public function search(string $text, array $sections = [], int $limit = 20): array
    {
        return RecordSearch::found($this->records, $this->linkable, $text, $sections, $limit);
    }

    #[Override]
    public function sections(): array
    {
        return array_values($this->linkable);
    }

    /**
     * The fields that hold a new record: a workflow the section doesn't
     * have, which $workflow is null for, or a path already in use.
     *
     * @return array<string, list<string>>
     */
    private function errors(NewRecord $record, ?Workflow $workflow): array
    {
        $errors = [];
        if (null !== $record->workflow && null === $workflow) {
            $errors['workflow'] = [self::NO_WORKFLOW];
        }

        if (! $this->isPathFree($record->path)) {
            $errors['path'] = [self::PATH_TAKEN];
        }

        return $errors;
    }
}
