<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Form;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Form\Field;
use Contenir\Extension\Api\Form\Form;
use Contenir\Extension\Api\Form\Group as FormGroup;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class FormTest extends TestCase
{
    #[Test]
    public function findsAFieldByName(): void
    {
        $key  = Field::password('api_key', 'API key');
        $form = new Form([new Field('list_id', 'List'), $key]);

        static::assertSame($key, $form->field('api_key'));
    }

    #[Test]
    public function findsNothingForAnUnknownName(): void
    {
        static::assertNull((new Form([new Field('list_id', 'List')]))->field('api_key'));
    }

    #[Test]
    public function isEmptyByDefault(): void
    {
        $form = new Form();

        static::assertSame([[], []], [$form->fields, $form->groups]);
    }

    #[Test]
    public function keepsGroupsThatLayOutItsFields(): void
    {
        $groups = [new FormGroup('main', 'Main', ['list_id']), new FormGroup('more', 'More', ['tag'])];

        static::assertSame(
            $groups,
            (new Form([new Field('list_id', 'List'), new Field('tag', 'Tag')], $groups))->groups,
        );
    }

    #[Test]
    public function rejectsAGroupLayingOutAnUnknownField(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Group "main" lays out "api_key", which is not a field of the form');

        new Form([new Field('list_id', 'List')], [new FormGroup('main', 'Main', ['list_id', 'api_key'])]);
    }

    #[Test]
    public function rejectsFieldsSharingAName(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Field "list_id" is already declared');

        new Form([new Field('list_id', 'List'), new Field('list_id', 'Again')]);
    }

    #[Test]
    public function rejectsGroupsSharingAnId(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Group "main" is already declared');

        new Form([new Field('list_id', 'List')], [
            new FormGroup('main', 'Main', ['list_id']),
            new FormGroup('main', 'Again', []),
        ]);
    }
}
