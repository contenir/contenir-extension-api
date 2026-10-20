<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Operation\SectionViewInterface;
use Contenir\Extension\Api\Settings\SettingsSection;
use Override;

/**
 * An add-on's settings section, as an operation on it sees it.
 *
 * @internal
 */
final readonly class SectionView implements SectionViewInterface
{
    public function __construct(
        private SettingsSection $section,
    ) {}

    #[Override]
    public function category(): string
    {
        return $this->section->category->value;
    }

    #[Override]
    public function hasField(string $name): bool
    {
        return null !== $this->section->form->field($name);
    }

    #[Override]
    public function id(): string
    {
        return $this->section->name;
    }

    #[Override]
    public function resourceId(): string
    {
        return "settings.{$this->section->category->value}.{$this->section->name}";
    }
}
