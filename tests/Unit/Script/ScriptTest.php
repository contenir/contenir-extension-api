<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Script;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Script\ConsentCategory;
use Contenir\Extension\Api\Script\Placement;
use Contenir\Extension\Api\Script\Script;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ScriptTest extends TestCase
{
    #[Test]
    public function hasNoAttributesByDefault(): void
    {
        static::assertSame([], Script::src('/a.js', Placement::Head, ConsentCategory::Marketing)->attributes);
    }

    #[Test]
    public function keepsInlineAttributes(): void
    {
        static::assertSame(
            ['id' => 'tag'],
            Script::inline('x()', Placement::Head, ConsentCategory::Marketing, ['id' => 'tag'])->attributes,
        );
    }

    #[Test]
    public function loadsFromAUrl(): void
    {
        $script = Script::src('https://example.com/a.js', Placement::Head, ConsentCategory::Analytics, [
            'async' => true,
        ]);

        static::assertSame(
            [Placement::Head, ConsentCategory::Analytics, 'https://example.com/a.js', null, ['async' => true]],
            [$script->placement, $script->consent, $script->src, $script->inline, $script->attributes],
        );
    }

    #[Test]
    public function rejectsAnEmptySrc(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('A script needs a src');

        Script::src('', Placement::Head, ConsentCategory::Analytics);
    }

    #[Test]
    public function rejectsEmptyCode(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('An inline script needs code');

        Script::inline('', Placement::Head, ConsentCategory::Analytics);
    }

    #[Test]
    public function writesCodeInline(): void
    {
        $script = Script::inline('window.dataLayer = [];', Placement::BodyStart, ConsentCategory::Necessary);

        static::assertSame(
            [Placement::BodyStart, ConsentCategory::Necessary, null, 'window.dataLayer = [];', []],
            [$script->placement, $script->consent, $script->src, $script->inline, $script->attributes],
        );
    }
}
