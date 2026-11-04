<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Exception\RowsRefusedException;
use Contenir\Extension\Api\Handler\HandlerInterface;
use Contenir\Extension\Api\Handler\HandlerResult;
use Contenir\Extension\Api\Record\RecordRow;
use Contenir\Extension\Api\Section\Element;
use Contenir\Extension\Api\Section\SectionBlock;
use Override;
use Psr\Http\Message\ServerRequestInterface;

use function array_filter;
use function array_map;
use function array_values;
use function explode;

/**
 * Uses the probe's section blocks, as the query's "do" says: lists them,
 * gives one, or reads rows, and answers with what it got as JSON. A refused
 * read answers {"refused": reason}.
 *
 * @api
 */
final class SectionsHandler implements HandlerInterface
{
    /**
     * @return array{section: string, linkable: bool, fields: list<string>, element: array<string, mixed>}
     */
    private static function block(SectionBlock $block): array
    {
        return [
            'section'  => $block->section,
            'linkable' => $block->linkable,
            'fields'   => $block->fields,
            'element'  => self::element($block->element),
        ];
    }

    /**
     * @return array{name: string, attributes: array<string, string>, text: string, children: list<array<string, mixed>>}
     */
    private static function element(Element $element): array
    {
        return [
            'name'       => $element->name,
            'attributes' => $element->attributes,
            'text'       => $element->text,
            'children'   => array_map(self::element(...), $element->children),
        ];
    }

    /**
     * @return list<string>
     */
    private static function list(string $value): array
    {
        return array_values(array_filter(explode(',', $value), static fn(string $item): bool => '' !== $item));
    }

    /**
     * @return array{record: string, status: string, values: array<string, scalar|null>}
     */
    private static function row(RecordRow $row): array
    {
        return [
            'record' => "{$row->record->section}:{$row->record->id}",
            'status' => $row->status->value,
            'values' => $row->values,
        ];
    }

    /**
     * @param array<string, string> $query
     *
     * @return list<array{record: string, status: string, values: array<string, scalar|null>}>|array{refused: string}
     */
    private static function rows(ExtensionContextInterface $context, array $query): array
    {
        try {
            $rows = $context->sections()->rows(
                $query['section'] ?? '',
                self::list($query['columns'] ?? ''),
                self::list($query['ids'] ?? ''),
                (int) ($query['limit'] ?? 100),
                (int) ($query['offset'] ?? 0),
            );
        } catch (RowsRefusedException $refused) {
            return ['refused' => $refused->getMessage()];
        }

        return array_map(self::row(...), $rows);
    }

    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): HandlerResult
    {
        /** @var array<string, string> $query */
        $query    = $request->getQueryParams();
        $sections = $context->sections();

        return HandlerResult::json(match ($query['do'] ?? '') {
            'blocks' => array_map(self::block(...), $sections->blocks()),
            'block' => ($found = $sections->block($query['section'] ?? '')) instanceof SectionBlock
                ? self::block($found)
                : null,
            'rows'   => self::rows($context, $query),
            default  => null,
        });
    }
}
