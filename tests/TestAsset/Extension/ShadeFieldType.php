<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Extension;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Exception\CellRejectedException;
use Contenir\Extension\Api\Field\ImportableFieldTypeInterface;
use Contenir\Extension\Api\Form\FieldType;
use Override;

/**
 * A choice of shade a CSV cell can fill.
 */
final class ShadeFieldType implements ImportableFieldTypeInterface
{
    private const array SHADES = ['lt' => 'Light', 'dk' => 'Dark'];

    #[Override]
    public function component(): ?string
    {
        return null;
    }

    #[Override]
    public function control(): FieldType
    {
        return FieldType::Select;
    }

    #[Override]
    public function importCell(string $cell): string
    {
        return null === (self::SHADES[$cell] ?? null) ? throw CellRejectedException::because('Not a shade') : $cell;
    }

    #[Override]
    public function normalise(mixed $value): mixed
    {
        return $value;
    }

    #[Override]
    public function options(ExtensionContextInterface $context): array
    {
        return self::SHADES;
    }

    #[Override]
    public function validate(mixed $value): array
    {
        return [];
    }
}
