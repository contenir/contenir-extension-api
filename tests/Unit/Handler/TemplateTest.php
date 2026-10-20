<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Handler;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Handler\Template;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;
use function str_repeat;

#[Group('unit')]
final class TemplateTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function badNameProvider(): array
    {
        return [
            'empty'                 => [''],
            'with its extension'    => ['fields.phtml'],
            'climbing out'          => ['../secrets'],
            'from the root'         => ['/fields'],
            'with a trailing slash' => ['fields/'],
            'upper case'            => ['Fields'],
            'starting with a digit' => ['1fields'],
            'with a newline after'  => ["fields\n"],
            'nine folders deep'     => ['a/b/c/d/e/f/g/h/i'],
            'a name too long'       => [str_repeat('a', times: 65)],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function goodNameProvider(): array
    {
        return [
            'a name'           => ['fields'],
            'with dashes'      => ['field-editor'],
            'in a folder'      => ['notification/editor'],
            'eight names deep' => ['a/b/c/d/e/f/g/h'],
            'a name of 64'     => [str_repeat('a', times: 64)],
        ];
    }

    #[Test]
    #[DataProvider('goodNameProvider')]
    public function acceptsAPathOfNames(string $name): void
    {
        static::assertSame($name, (new Template($name, 'Fields'))->name);
    }

    #[Test]
    public function keepsItsTitleAndValues(): void
    {
        $template = new Template('fields', 'Fields', ['form' => 3]);

        static::assertSame(['Fields', ['form' => 3]], [$template->title, $template->values]);
    }

    #[Test]
    #[DataProvider('badNameProvider')]
    public function rejectsAMalformedName(string $name): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage(sprintf(
            'Template name "%s" must be names of lowercase letters, digits and dashes, joined by slashes, without an extension',
            $name,
        ));

        new Template($name, 'Fields');
    }

    #[Test]
    public function rejectsAnEmptyTitle(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('A template title must not be empty');

        new Template('fields', ' ');
    }
}
