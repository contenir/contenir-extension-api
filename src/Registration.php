<?php

declare(strict_types=1);

namespace Contenir\Extension\Api;

use Contenir\Extension\Api\Diagnostic\DiagnosticDeclaration;
use Contenir\Extension\Api\Field\FieldTypeDeclaration;
use Contenir\Extension\Api\Handler\HandlerDeclaration;
use Contenir\Extension\Api\Listener\ListenerDeclaration;
use Contenir\Extension\Api\Migration\MigrationSet;
use Contenir\Extension\Api\Module\Module;
use Contenir\Extension\Api\Panel\PanelDeclaration;
use Contenir\Extension\Api\Schedule\ScheduleDeclaration;
use Contenir\Extension\Api\Script\ScriptDeclaration;
use Contenir\Extension\Api\Settings\SettingsSection;

/**
 * Everything one add-on declared, as the host reads it back to wire the
 * add-on in. Each list is keyed by the declaration's name, in the order the
 * add-on declared them.
 *
 * @api
 */
final readonly class Registration
{
    /**
     * @param array<string, SettingsSection>       $settingsSections
     * @param array<string, FieldTypeDeclaration>  $fieldTypes
     * @param array<string, DiagnosticDeclaration> $diagnostics
     * @param list<ListenerDeclaration>            $listeners
     * @param array<string, HandlerDeclaration>    $handlers
     * @param array<string, HandlerDeclaration>    $webhooks
     * @param list<MigrationSet>                   $migrations
     * @param array<string, ScheduleDeclaration>   $schedules
     * @param array<string, ScriptDeclaration>     $scripts
     * @param array<string, Handle>                $dependencies
     * @param array<string, PanelDeclaration>      $panels
     *
     * @mago-expect lint:excessive-parameter-list One parameter per extension point.
     */
    public function __construct(
        public Handle $handle,
        public array $settingsSections,
        public array $fieldTypes,
        public ?string $icons,
        public array $diagnostics,
        public array $listeners,
        public array $handlers,
        public array $webhooks,
        public array $migrations,
        public array $schedules,
        public array $scripts,
        public Module $module,
        public array $dependencies,
        public array $panels = [],
    ) {}
}
