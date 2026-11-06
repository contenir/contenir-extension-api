<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Handler\HandlerInterface;
use Contenir\Extension\Api\Handler\HandlerResult;
use Contenir\Extension\Api\Record\LinkableSection;
use Contenir\Extension\Api\Record\RecordsInterface;
use Contenir\Extension\Api\Record\RecordUpdatesInterface;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;

use function array_filter;
use function array_map;
use function array_values;
use function explode;

/**
 * Uses the records the host lends, as the query's "do" says: lists the
 * linkable sections, searches, describes, checks a path, creates a record
 * or updates records, and answers with what it got as JSON. It answers 404
 * when the host lends no records, or, for an update, no record updates.
 *
 * @api
 */
final class RecordsHandler implements HandlerInterface
{
    /**
     * Updates the records the query's "ids" name, in the section it names, setting its "field" to its
     * "value", as one call to the host's record updates.
     *
     * @param array<string, string> $query
     *
     * @throws ContainerExceptionInterface When the host can't make its record updates.
     */
    private static function update(ExtensionContextInterface $context, array $query): HandlerResult
    {
        $updates = $context->services()->has(RecordUpdatesInterface::class)
            ? $context->services()->get(RecordUpdatesInterface::class)
            : null;
        if (! $updates instanceof RecordUpdatesInterface) {
            return HandlerResult::json(null, 404);
        }

        try {
            return HandlerResult::json(RecordsAnswer::updated($updates, $query));
        } catch (InvalidDeclarationException $refused) {
            return HandlerResult::json(['refused' => $refused->getMessage()]);
        }
    }

    /**
     * @throws ContainerExceptionInterface When the host can't make its records.
     */
    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): HandlerResult
    {
        /** @var array<string, string> $query */
        $query = $request->getQueryParams();
        if ('update' === ($query['do'] ?? '')) {
            return self::update($context, $query);
        }

        $records = $context->services()->has(RecordsInterface::class)
            ? $context->services()->get(RecordsInterface::class)
            : null;
        if (! $records instanceof RecordsInterface) {
            return HandlerResult::json(null, 404);
        }

        return HandlerResult::json(match ($query['do'] ?? '') {
            'sections' => array_map(
                static fn(LinkableSection $section): string => $section->section,
                $records->sections(),
            ),
            'search'   => array_map(RecordsAnswer::summary(...), $records->search(
                $query['text'] ?? '',
                array_values(array_filter(explode(',', $query['sections'] ?? ''))),
                (int) ($query['limit'] ?? 20),
            )),
            'describe' => RecordsAnswer::described($records->describe(RecordsAnswer::reference($query))),
            'free'     => $records->isPathFree($query['path'] ?? ''),
            'create'   => RecordsAnswer::created($records, $query),
            default    => null,
        });
    }
}
