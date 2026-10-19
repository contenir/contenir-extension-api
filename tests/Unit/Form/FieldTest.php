<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Form;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Form\Field;
use Contenir\Extension\Api\Form\FieldType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class FieldTest extends TestCase
{
    #[Test]
    public function declaresAnOptionalPasswordByDefault(): void
    {
        $field = Field::password('api_key', 'API key');

        static::assertSame(['', null, false], [$field->description, $field->setting, $field->required]);
    }

    #[Test]
    public function declaresAPasswordStoredLocallyAndMasked(): void
    {
        $field = Field::password('api_key', 'API key', 'From your account page', 'newsletter.api_key', required: true);

        static::assertSame(
            ['api_key', 'API key', 'password', 'From your account page', 'newsletter.api_key', true, true, true],
            [
                $field->name,
                $field->label,
                $field->type,
                $field->description,
                $field->setting,
                $field->required,
                $field->local,
                $field->isMasked(),
            ],
        );
    }

    #[Test]
    public function isTextByDefault(): void
    {
        $field = new Field('list_id', 'List');

        static::assertSame(
            ['text', '', null, false, null, [], [], false, false, []],
            [
                $field->type,
                $field->description,
                $field->setting,
                $field->required,
                $field->default,
                $field->options,
                $field->fields,
                $field->transient,
                $field->local,
                $field->attributes,
            ],
        );
    }

    #[Test]
    public function keepsDistinctSubFields(): void
    {
        $fields = [new Field('path', 'Path'), new Field('cache', 'Cache', FieldType::Checkbox)];

        static::assertSame($fields, (new Field('routes', 'Routes', FieldType::Multilink, fields: $fields))->fields);
    }

    #[Test]
    public function masksOnlyPasswords(): void
    {
        static::assertFalse((new Field('api_key', 'API key'))->isMasked());
    }

    #[Test]
    public function rejectsAMalformedName(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        new Field('List', 'List');
    }

    #[Test]
    public function rejectsAnEmptyTypeName(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('A field type must not be empty');

        new Field('list_id', 'List', '');
    }

    #[Test]
    public function rejectsSubFieldsSharingAName(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Field "routes" has two sub-fields named "path"');

        new Field('routes', 'Routes', FieldType::Multilink, fields: [
            new Field('path', 'Path'),
            new Field('path', 'Again'),
        ]);
    }

    #[Test]
    public function takesABuiltInType(): void
    {
        static::assertSame('select', (new Field('list_id', 'List', FieldType::Select))->type);
    }

    #[Test]
    public function takesAnAddOnsFieldTypeByName(): void
    {
        static::assertSame('mailing-list', (new Field('list_id', 'List', 'mailing-list'))->type);
    }
}
