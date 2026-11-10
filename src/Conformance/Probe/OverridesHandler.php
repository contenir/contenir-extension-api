<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Exception\BlockNotSavedException;
use Contenir\Extension\Api\Handler\HandlerInterface;
use Contenir\Extension\Api\Handler\HandlerResult;
use Contenir\Extension\Api\Section\Element;
use Contenir\Extension\Api\Section\SectionsInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Overrides the probe's block on the query's "section", as the query's "do"
 * says, and answers as JSON: "override" keeps the query's "xml" in the
 * block's place and "restore" drops it, each against the query's "version"
 * or the overrides as they are now, answering {"version": the version after
 * it} or {"refused": reason}; "state" answers whether the section is
 * overridden, the overrides' version, and the attributes of the block as it
 * stands and as admin.xml declares it.
 *
 * @api
 */
final class OverridesHandler implements HandlerInterface
{
    /**
     * @param array<string, string> $query
     *
     * @return array{version: string}|array{refused: string}
     */
    private static function override(SectionsInterface $sections, array $query): array
    {
        try {
            $sections->override(
                $query['section'] ?? '',
                Element::parse($query['xml'] ?? ''),
                $query['version'] ?? $sections->overridesVersion(),
            );
        } catch (BlockNotSavedException $refused) {
            return ['refused' => $refused->getMessage()];
        }

        return ['version' => $sections->overridesVersion()];
    }

    /**
     * @param array<string, string> $query
     *
     * @return array{version: string}|array{refused: string}
     */
    private static function restore(SectionsInterface $sections, array $query): array
    {
        try {
            $sections->restore($query['section'] ?? '', $query['version'] ?? $sections->overridesVersion());
        } catch (BlockNotSavedException $refused) {
            return ['refused' => $refused->getMessage()];
        }

        return ['version' => $sections->overridesVersion()];
    }

    /**
     * @return array{overridden: bool, version: string, block: array<string, string>|null, declared: array<string, string>|null}
     */
    private static function state(SectionsInterface $sections, string $section): array
    {
        return [
            'overridden' => $sections->overridden($section),
            'version'    => $sections->overridesVersion(),
            'block'      => $sections->block($section)?->element->attributes,
            'declared'   => $sections->declared($section)?->element->attributes,
        ];
    }

    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): HandlerResult
    {
        /** @var array<string, string> $query */
        $query    = $request->getQueryParams();
        $sections = $context->sections();

        return HandlerResult::json(match ($query['do'] ?? '') {
            'override' => self::override($sections, $query),
            'restore'  => self::restore($sections, $query),
            'state'    => self::state($sections, $query['section'] ?? ''),
            default    => null,
        });
    }
}
