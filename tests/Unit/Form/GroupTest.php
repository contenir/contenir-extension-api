<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Form;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Form\Group as FormGroup;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class GroupTest extends TestCase
{
    #[Test]
    public function listsItsFieldsInOrder(): void
    {
        $group = new FormGroup('connection', 'Connection', ['api_key', ['list_id', 'tag'], 'region']);

        static::assertSame(['api_key', 'list_id', 'tag', 'region'], $group->fieldNames());
    }

    #[Test]
    public function listsNoFieldsWhenEmpty(): void
    {
        static::assertSame([], (new FormGroup('empty', 'Empty', []))->fieldNames());
    }

    #[Test]
    public function rejectsAMalformedId(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        new FormGroup('Connection', 'Connection', []);
    }

    #[Test]
    public function writesSingleFieldRowsAsOneColumn(): void
    {
        $group = new FormGroup('connection', 'Connection', ['api_key', ['list_id', 'tag']]);

        static::assertSame([['api_key'], ['list_id', 'tag']], $group->rows);
    }
}
