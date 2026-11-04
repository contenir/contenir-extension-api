<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Event\Catalogue;
use Contenir\Extension\Api\Event\EventInterface;
use Contenir\Extension\Api\Section\Element;
use Contenir\Extension\Api\Section\ImpliedListener;
use Contenir\Extension\Api\Section\SectionBlock;
use Contenir\Extension\Api\Section\SectionBlockInterface;
use LogicException;
use Override;

use function array_filter;
use function array_map;
use function array_values;
use function explode;

/**
 * Reads the probe's section blocks: each <column> is a column it reads, and
 * each <listen event="…" fields="…">name</listen> a listener it binds. A
 * <broken/> inside a block makes both answers fail, as a faulty reader would.
 *
 * @api
 */
final class ProbeSections implements SectionBlockInterface
{
    /**
     * @throws LogicException When the block asks the probe to fail.
     */
    private static function refuseBroken(SectionBlock $block): void
    {
        if (null !== $block->element->child('broken')) {
            throw new LogicException('The probe was asked to fail reading its block');
        }
    }

    #[Override]
    public function columns(SectionBlock $block): array
    {
        self::refuseBroken($block);

        return array_map(static fn(Element $column): string => $column->text, $block->element->named('column'));
    }

    #[Override]
    public function listeners(SectionBlock $block): array
    {
        self::refuseBroken($block);

        return array_map(
            static function (Element $listen): ImpliedListener {
                $event = $listen->attribute('event', '') ?? '';
                /** @var class-string<EventInterface> $class */
                $class = Catalogue::find($event) ?? $event;

                return new ImpliedListener(
                    $listen->text,
                    $class,
                    array_values(array_filter(explode(',', $listen->attribute('fields', '') ?? ''))),
                );
            },
            $block->element->named('listen'),
        );
    }
}
