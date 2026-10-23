<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Record;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Record\LinkableSection;
use Contenir\Extension\Api\Record\Workflow;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class LinkableSectionTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string}>
     */
    public static function emptyProvider(): array
    {
        return [
            'no section' => ['', 'Page', 'A linkable section must not be empty'],
            'no title'   => ['page', '', 'A linkable section title must not be empty'],
        ];
    }

    #[Test]
    public function findsAWorkflowByItsId(): void
    {
        $landing = new Workflow('landing', 'Landing page');
        $section = new LinkableSection('page', 'Page', workflows: [
            new Workflow('standard', 'Standard page'),
            $landing,
        ]);

        static::assertSame([$landing, null], [$section->workflow('landing'), $section->workflow('gallery')]);
    }

    #[Test]
    public function isNotCreatableAndHasNoWorkflowsUnlessItSays(): void
    {
        $section = new LinkableSection('news', 'News article');

        static::assertSame([false, []], [$section->creatable, $section->workflows]);
    }

    #[Test]
    #[DataProvider('emptyProvider')]
    public function needsASectionAndATitle(string $section, string $title, string $message): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage($message);

        new LinkableSection($section, $title);
    }
}
