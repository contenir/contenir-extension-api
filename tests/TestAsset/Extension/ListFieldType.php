<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Extension;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Field\FieldTypeInterface;
use Contenir\Extension\Api\Form\FieldType;
use Override;

final class ListFieldType implements FieldTypeInterface
{
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
    public function normalise(mixed $value): mixed
    {
        return $value;
    }

    #[Override]
    public function options(ExtensionContextInterface $context): array
    {
        return ['main' => 'Main list'];
    }

    #[Override]
    public function validate(mixed $value): array
    {
        return [];
    }
}
