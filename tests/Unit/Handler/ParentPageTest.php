<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Handler;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Handler\HandlerResult;
use Contenir\Extension\Api\Handler\ParentPage;
use Contenir\Extension\Api\Handler\Screen;
use Contenir\Extension\Api\Handler\Template;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ParentPageTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string}>
     */
    public static function badParentProvider(): array
    {
        return [
            'an empty title'      => [' ', 'form', 'A parent page title must not be empty'],
            'a malformed handler' => ['Contact', 'Form', 'Handler name "Form" must be'],
        ];
    }

    #[Test]
    public function leadsToAHandlerWithItsQuery(): void
    {
        $parent = new ParentPage('Contact', 'form', ['form' => 3]);

        static::assertSame(['Contact', 'form', ['form' => 3]], [$parent->title, $parent->handler, $parent->query]);
    }

    #[Test]
    public function leavesAScreenOrTemplateWithoutAParentByDefault(): void
    {
        static::assertSame(
            [null, null],
            [(new Screen('Settings'))->parent, HandlerResult::template('fields', 'Fields')->template?->parent],
        );
    }

    #[Test]
    #[DataProvider('badParentProvider')]
    public function refusesAParentItCannotDraw(string $title, string $handler, string $message): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage($message);

        new ParentPage($title, $handler);
    }

    #[Test]
    public function sitsUnderTheScreenOrTemplateThatNamesIt(): void
    {
        $parent = new ParentPage('Contact', 'form', ['form' => 3]);

        static::assertEquals(
            [$parent, new Template('notification', 'Team', ['form' => 3], [], $parent)],
            [
                (new Screen('Webhook', parent: $parent))->parent,
                HandlerResult::template('notification', 'Team', ['form' => 3], parent: $parent)->template,
            ],
        );
    }
}
