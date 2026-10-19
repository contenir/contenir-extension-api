<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Operation;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Operation\Contribution;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ContributionTest extends TestCase
{
    #[Test]
    public function failsWithoutARegion(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('The declaration has no "region" string');

        Contribution::fromArray(['type' => 'text']);
    }

    #[Test]
    public function failsWithoutAType(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('The declaration has no "type" string');

        Contribution::fromArray(['region' => 'pre']);
    }

    #[Test]
    public function fillsDefaultsWhenReadingAnArray(): void
    {
        static::assertEquals(
            new Contribution('pre', 'text'),
            Contribution::fromArray(['region' => 'pre', 'type' => 'text']),
        );
    }

    #[Test]
    public function roundTripsThroughAnArray(): void
    {
        $contribution = new Contribution('aside', 'link', 'view', 'Docs', 'book', 'https://example.com', 'Read');

        static::assertEquals($contribution, Contribution::fromArray($contribution->toArray()));
    }

    #[Test]
    public function writesEveryKey(): void
    {
        static::assertSame(
            [
                'region'    => 'pre',
                'type'      => 'text',
                'privilege' => 'update',
                'label'     => '',
                'icon'      => '',
                'url'       => '',
                'content'   => '',
            ],
            (new Contribution('pre', 'text'))->toArray(),
        );
    }
}
