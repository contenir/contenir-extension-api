<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Module;

use Contenir\Extension\Api\ExtensionInterface;
use Contenir\Extension\Api\Module\MenuInterface;
use Contenir\Extension\Api\Registry;
use Override;

/**
 * A module whose admin.xml and menu each test chooses.
 */
final readonly class ModuleFixture implements ExtensionInterface
{
    /**
     * @param class-string<MenuInterface>|null $menu
     */
    public function __construct(
        private string $adminXml,
        private ?string $menu = null,
    ) {}

    #[Override]
    public function register(Registry $registry): void
    {
        $registry->module(__DIR__ . "/{$this->adminXml}");
        $registry->templates(__DIR__ . '/templates');
        if (null !== $this->menu) {
            $registry->menu($this->menu);
        }

        $registry->handler('home', TemplateHandler::class);
        $registry->handler('boom', TemplateHandler::class);
    }
}
