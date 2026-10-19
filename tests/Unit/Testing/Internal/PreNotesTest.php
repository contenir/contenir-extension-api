<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Testing\Internal;

use Contenir\Extension\Api\Event\Cache\CachePreClear;
use Contenir\Extension\Api\Event\Outcome;
use Contenir\Extension\Api\Testing\Internal\PreNotes;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class PreNotesTest extends TestCase
{
    #[Test]
    public function gathersWhatEveryPreEventSaid(): void
    {
        $first = new CachePreClear('all');
        $first->addError('title', 'Too long');
        $first->warn('', 'Busy');
        $first->flag('one');
        $second = new CachePreClear('all');
        $second->addError('title', 'Taken');
        $second->addError('slug', 'Empty');
        $second->flag('two');

        $notes = new PreNotes();
        $notes->add($first);
        $notes->add($second);
        $result = $notes->result(Outcome::Stopped, 1, 'Held', 'Why');

        static::assertSame(
            [['title' => ['Too long', 'Taken'], 'slug' => ['Empty']], ['' => ['Busy']], ['one', 'two'], 'Held', 'Why'],
            [$result->errors, $result->warnings, $result->flags, $result->message, $result->detail],
        );
    }
}
