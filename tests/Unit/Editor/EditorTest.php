<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Editor;

use Contenir\Extension\Api\Editor\Editor;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class EditorTest extends TestCase
{
    #[Test]
    public function namesAnEditorByTheirIdAndName(): void
    {
        $editor = new Editor('u-7', 'Ann Editor');

        static::assertSame(['u-7', 'Ann Editor'], [$editor->id, $editor->name]);
    }

    #[Test]
    public function needsAName(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('An editor name must not be empty');

        new Editor(7, '');
    }
}
