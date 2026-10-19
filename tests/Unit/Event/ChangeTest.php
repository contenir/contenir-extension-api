<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Event;

use Contenir\Extension\Api\Event\Change;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ChangeTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>, array<string, mixed>, list<string>}>
     */
    public static function changeProvider(): array
    {
        return [
            'nothing changed'      => [['title' => 'A'], ['title' => 'A'], []],
            'a value changed'      => [['title' => 'A', 'body' => 'x'], ['title' => 'B', 'body' => 'x'], ['title']],
            'a field added'        => [[], ['title' => 'A'], ['title']],
            'a field removed'      => [['title' => 'A', 'image' => 'a.jpg'], ['title' => 'A'], ['image']],
            'loosely equal values' => [['count' => 1], ['count' => '1'], ['count']],
            'null becomes empty'   => [['title' => null], ['title' => ''], ['title']],
            'added before removed' => [['old' => 1, 'kept' => 1], ['new' => 1, 'kept' => 2], ['new', 'kept', 'old']],
        ];
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @param list<string>         $changed
     */
    #[Test]
    #[DataProvider('changeProvider')]
    public function findsTheFieldsThatChanged(array $before, array $after, array $changed): void
    {
        static::assertSame($changed, (new Change($before, $after))->changed);
    }

    #[Test]
    public function saysWhetherAnyOfSomeFieldsChanged(): void
    {
        $change = new Change(['title' => 'A', 'email' => 'a@example.com'], [
            'title' => 'B',
            'email' => 'a@example.com',
        ]);

        static::assertSame(
            [true, true, false, false],
            [
                $change->touches('title'),
                $change->touches('email', 'title'),
                $change->touches('email'),
                $change->touches(),
            ],
        );
    }

    #[Test]
    public function setsAValueAfterwards(): void
    {
        $change = (new Change(['title' => 'A'], ['title' => 'A']))->with('slug', 'a');

        static::assertSame(
            [['title' => 'A'], ['title' => 'A', 'slug' => 'a'], ['slug']],
            [$change->before, $change->after, $change->changed],
        );
    }
}
