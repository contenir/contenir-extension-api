<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Event;

use Contenir\Extension\Api\Event\Cache\CachePreClear;
use Contenir\Extension\Api\Event\Change;
use Contenir\Extension\Api\Event\Record\RecordPreSave;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class PreEventTest extends TestCase
{
    #[Test]
    public function flagsWithoutHoldingTheAction(): void
    {
        $event = new CachePreClear('all');
        $event->flag('spam');
        $event->flag('rate limit');

        static::assertSame([true, ['spam', 'rate limit']], [$event->proceeds(), $event->flags()]);
    }

    #[Test]
    public function holdsTheActionOnAFieldErrorButLetsTheChainGoOn(): void
    {
        $event = new CachePreClear('all');
        $event->addError('slug', 'Already taken');
        $event->addError('slug', 'Too long');
        $event->addError('title', 'Required');

        static::assertSame(
            [false, false, ['slug' => ['Already taken', 'Too long'], 'title' => ['Required']]],
            [$event->proceeds(), $event->isPropagationStopped(), $event->errors()],
        );
    }

    #[Test]
    public function letsListenersChangeValues(): void
    {
        $event = new RecordPreSave(
            'article',
            5,
            new Change(['title' => 'Old', 'slug' => 'old'], ['title' => 'New', 'slug' => 'old']),
        );
        $event->setValue('slug', 'new');

        static::assertSame(
            [['title' => 'New', 'slug' => 'new'], ['title', 'slug']],
            [$event->values(), $event->change()->changed],
        );
    }

    #[Test]
    public function proceedsWhenNothingStopsIt(): void
    {
        $event = new CachePreClear('all');

        static::assertSame(
            [true, false, null, [], [], []],
            [
                $event->proceeds(),
                $event->isPropagationStopped(),
                $event->stopMessage(),
                $event->errors(),
                $event->warnings(),
                $event->flags(),
            ],
        );
    }

    #[Test]
    public function stopsTheActionAndTheChain(): void
    {
        $event = new CachePreClear('all');
        $event->stop('A deploy is running');

        static::assertSame(
            [false, true, 'A deploy is running'],
            [$event->proceeds(), $event->isPropagationStopped(), $event->stopMessage()],
        );
    }

    #[Test]
    public function warnsWithoutHoldingTheAction(): void
    {
        $event = new CachePreClear('all');
        $event->warn('description', 'Longer than 160 characters');
        $event->warn('', 'No image for sharing');
        $event->warn('description', 'Repeats the title');

        static::assertSame(
            [
                true,
                ['description' => ['Longer than 160 characters', 'Repeats the title'], '' => ['No image for sharing']],
            ],
            [$event->proceeds(), $event->warnings()],
        );
    }
}
