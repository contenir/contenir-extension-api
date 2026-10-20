<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Module;

/**
 * The pieces that make an add-on a module with an admin area of its own: an
 * admin.xml fragment of categories and sections, the folder of built scripts
 * and styles published under /cms/ext/<handle>/, its dashboard components,
 * templates for the few screens that can't be described, and what its
 * section menu lists beyond its admin.xml.
 *
 * @api
 */
final readonly class Module
{
    /**
     * @param list<Component>                    $components
     * @param class-string<MenuInterface>|null $menu
     */
    public function __construct(
        public ?string $adminXml = null,
        public ?string $assets = null,
        public array $components = [],
        public ?string $templates = null,
        public ?string $menu = null,
    ) {}
}
