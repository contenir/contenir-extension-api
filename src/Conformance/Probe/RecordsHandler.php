<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Handler\HandlerInterface;
use Contenir\Extension\Api\Handler\HandlerResult;
use Contenir\Extension\Api\Record\LinkableSection;
use Contenir\Extension\Api\Record\RecordsInterface;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;

use function array_filter;
use function array_map;
use function array_values;
use function explode;

/**
 * Uses the records the host lends, as the query's "do" says: lists the
 * linkable sections, searches, describes, checks a path or creates a
 * record, and answers with what it got as JSON. It answers 404 when the
 * host lends no records.
 *
 * @api
 */
final class RecordsHandler implements HandlerInterface
{
    /**
     * @throws ContainerExceptionInterface When the host can't make its records.
     */
    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): HandlerResult
    {
        $records = $context->services()->has(RecordsInterface::class)
            ? $context->services()->get(RecordsInterface::class)
            : null;
        if (! $records instanceof RecordsInterface) {
            return HandlerResult::json(null, 404);
        }

        /** @var array<string, string> $query */
        $query = $request->getQueryParams();

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
