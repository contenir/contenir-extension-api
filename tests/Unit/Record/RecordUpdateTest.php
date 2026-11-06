<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Record;

use Contenir\Extension\Api\Event\ActionResult;
use Contenir\Extension\Api\Event\Outcome;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Record\RecordUpdate;
use Contenir\Extension\Api\Record\RecordUpdateResult;
use Contenir\Extension\Api\Url\RecordReference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class RecordUpdateTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidProvider(): array
    {
        return [
            'no field at all'        => [[], 'A record update must set at least one field'],
            'a field with a capital' => [
                ['Title' => 'x'],
                'Field name "Title" must be 1 to 64 lowercase letters, digits and underscores',
            ],
            'a field with a dash'    => [
                ['meta-title' => 'x'],
                'Field name "meta-title" must be 1 to 64 lowercase letters, digits and underscores',
            ],
        ];
    }

    #[Test]
    public function aResultKeepsWhatTheChangedFieldsHeldBefore(): void
    {
        $result = new RecordUpdateResult(
            new RecordReference('site.page', 12),
            new ActionResult(Outcome::Done, 12),
            ['seo' => null],
        );

        static::assertSame([true, ['seo' => null]], [$result->succeeded(), $result->previous]);
    }

    #[Test]
    public function aStoppedResultDoesNotSucceed(): void
    {
        $result = new RecordUpdateResult(
            new RecordReference('site.page', 12),
            new ActionResult(Outcome::Stopped, 12, 'Some fields need attention'),
        );

        static::assertSame([false, []], [$result->succeeded(), $result->previous]);
    }

    #[Test]
    public function describesAChangeToOneRecord(): void
    {
        $record = new RecordReference('site.page', 12);
        $update = new RecordUpdate($record, ['seo' => '{"description":"About us"}']);

        static::assertSame([$record, ['seo' => '{"description":"About us"}']], [$update->record, $update->values]);
    }

    /**
     * @param array<string, mixed> $values
     */
    #[Test]
    #[DataProvider('invalidProvider')]
    public function refusesAChangeThatSetsNoValidField(array $values, string $message): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage($message);

        new RecordUpdate(new RecordReference('site.page', 12), $values);
    }
}
