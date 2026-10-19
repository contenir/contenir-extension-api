<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Handle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;
use function str_repeat;

#[Group('unit')]
final class HandleTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function invalidProvider(): array
    {
        return [
            'empty'              => [''],
            'one character'      => ['a'],
            'forty-one'          => ['a' . str_repeat('b', times: 40)],
            'uppercase'          => ['Newsletter'],
            'leading digit'      => ['2fa'],
            'leading dash'       => ['-news'],
            'underscore'         => ['news_letter'],
            'trailing newline'   => ["news\n"],
            'leading whitespace' => [' news'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validProvider(): array
    {
        return [
            'two characters'    => ['ab'],
            'forty characters'  => ['a' . str_repeat('b', times: 39)],
            'digits and dashes' => ['campaign-monitor2'],
        ];
    }

    #[Test]
    #[DataProvider('validProvider')]
    public function acceptsWellFormedHandles(string $handle): void
    {
        static::assertSame($handle, (new Handle($handle))->value);
    }

    #[Test]
    public function namesAHandlersPermission(): void
    {
        static::assertSame('extension.newsletter.connect', (new Handle('newsletter'))->permission('connect'));
    }

    #[Test]
    public function namesTheFixPermission(): void
    {
        static::assertSame('extension.newsletter.fix', (new Handle('newsletter'))->fixPermission());
    }

    #[Test]
    public function prefixesTablesWithUnderscores(): void
    {
        static::assertSame('ext_campaign_monitor_', (new Handle('campaign-monitor'))->tablePrefix());
    }

    #[Test]
    #[DataProvider('invalidProvider')]
    public function rejectsMalformedHandles(string $handle): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage(sprintf(
            '%s name "%s" must be %s',
            'Handle',
            $handle,
            '2 to 40 lowercase letters, digits and dashes, starting with a letter',
        ));

        new Handle($handle);
    }

    #[Test]
    public function reservesTheApiHandle(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Handle "api" is reserved');

        new Handle('api');
    }

    #[Test]
    public function stampsLogEntriesWithTheHandle(): void
    {
        static::assertSame('extension.newsletter', (new Handle('newsletter'))->logSource());
    }
}
