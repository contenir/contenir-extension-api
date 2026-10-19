<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Internal;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Handler\HandlerInterface;
use Contenir\Extension\Api\Internal\Names;
use Contenir\Extension\Api\Tests\TestAsset\Extension\ConnectHandler;
use Contenir\Extension\Api\Tests\TestAsset\Extension\ResyncTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;
use function str_repeat;

#[Group('unit')]
final class NamesTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function invalidFieldProvider(): array
    {
        return [
            'empty'            => [''],
            'sixty-five'       => ['a' . str_repeat('b', times: 64)],
            'uppercase'        => ['apiKey'],
            'leading digit'    => ['2key'],
            'dash'             => ['api-key'],
            'trailing newline' => ["key\n"],
            'leading space'    => [' key'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidNameProvider(): array
    {
        return [
            'empty'            => [''],
            'sixty-five'       => ['a' . str_repeat('b', times: 64)],
            'uppercase'        => ['Sync'],
            'leading digit'    => ['2sync'],
            'underscore'       => ['re_sync'],
            'trailing newline' => ["sync\n"],
            'leading space'    => [' sync'],
        ];
    }

    /**
     * @return array<string, array{string}>
     *
     * @mago-expect lint:no-literal-namespace-string The cases are namespaces under test, not class references.
     */
    public static function invalidNamespaceProvider(): array
    {
        return [
            'empty'              => [''],
            'leading backslash'  => ['\\Contenir\\Migration'],
            'trailing backslash' => ['Contenir\\Migration\\'],
            'double backslash'   => ['Contenir\\\\Migration'],
            'leading digit'      => ['2Contenir'],
            'segment digit'      => ['Contenir\\2Migration'],
            'dash'               => ['Contenir\\Migra-tion'],
            'trailing newline'   => ["Contenir\n"],
            'leading space'      => [' Contenir'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validFieldProvider(): array
    {
        return [
            'one letter'  => ['a'],
            'sixty-four'  => ['a' . str_repeat('b', times: 63)],
            'underscores' => ['api_key_2'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validNameProvider(): array
    {
        return [
            'one letter'        => ['a'],
            'sixty-four'        => ['a' . str_repeat('b', times: 63)],
            'digits and dashes' => ['sync-2'],
        ];
    }

    /**
     * @return array<string, array{string}>
     *
     * @mago-expect lint:no-literal-namespace-string The cases are namespaces under test, not class references.
     */
    public static function validNamespaceProvider(): array
    {
        return [
            'one segment' => ['Migrations'],
            'nested'      => ['Contenir\\Extension\\Newsletter\\Migration'],
            'underscored' => ['_Legacy\\V2_Migrations'],
        ];
    }

    #[Test]
    public function acceptsAClassImplementingTheInterface(): void
    {
        static::assertSame(
            ConnectHandler::class,
            Names::implementing('Handler', ConnectHandler::class, HandlerInterface::class),
        );
    }

    #[Test]
    #[DataProvider('validNamespaceProvider')]
    public function acceptsNamespaces(string $namespace): void
    {
        static::assertSame($namespace, Names::namespace($namespace));
    }

    #[Test]
    public function acceptsText(): void
    {
        static::assertSame(' Title ', Names::text('A title', ' Title '));
    }

    #[Test]
    #[DataProvider('validFieldProvider')]
    public function acceptsWellFormedFieldNames(string $name): void
    {
        static::assertSame($name, Names::field($name));
    }

    #[Test]
    #[DataProvider('validNameProvider')]
    public function acceptsWellFormedNames(string $name): void
    {
        static::assertSame($name, Names::name('Schedule', $name));
    }

    #[Test]
    public function rejectsAClassNotImplementingTheInterface(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage(
            'Handler class "' . ResyncTask::class . '" must implement ' . HandlerInterface::class,
        );

        Names::implementing('Handler', ResyncTask::class, HandlerInterface::class);
    }

    #[Test]
    public function rejectsAMissingClass(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        Names::implementing('Handler', 'MissingHandler', HandlerInterface::class);
    }

    #[Test]
    public function rejectsBlankText(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('A title must not be empty');

        Names::text('A title', " \t");
    }

    #[Test]
    #[DataProvider('invalidFieldProvider')]
    public function rejectsMalformedFieldNames(string $name): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage(sprintf(
            '%s name "%s" must be %s',
            'Field',
            $name,
            '1 to 64 lowercase letters, digits and underscores, starting with a letter',
        ));

        Names::field($name);
    }

    #[Test]
    #[DataProvider('invalidNameProvider')]
    public function rejectsMalformedNames(string $name): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage(sprintf(
            '%s name "%s" must be %s',
            'Schedule',
            $name,
            '1 to 64 lowercase letters, digits and dashes, starting with a letter',
        ));

        Names::name('Schedule', $name);
    }

    #[Test]
    #[DataProvider('invalidNamespaceProvider')]
    public function rejectsMalformedNamespaces(string $namespace): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage("Namespace name \"{$namespace}\" must be a PHP namespace");

        Names::namespace($namespace);
    }
}
