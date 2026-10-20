<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe\Module;

use Contenir\Extension\Api\ExtensionInterface;
use Contenir\Extension\Api\Handler\Privilege;
use Contenir\Extension\Api\Registry;
use Override;

/**
 * The module the conformance suite loads beside the probe: an area, Probes,
 * whose home is a template screen, with a Notes section drawn by a handler
 * and a handler that needs Delete.
 *
 * @api
 */
final readonly class ProbeModuleExtension implements ExtensionInterface
{
    public const string HANDLE = 'probe-module';

    /**
     * The area its admin.xml declares, which guards its handlers.
     */
    public const string AREA = 'probes';

    /**
     * The section naming the notes handler.
     */
    public const string NOTES = 'probes.notes.list';

    #[Override]
    public function register(Registry $registry): void
    {
        $registry->module(__DIR__ . '/admin.xml');
        $registry->templates(__DIR__ . '/templates');
        $registry->menu(ProbeMenu::class);
        $registry->handler('page', PageHandler::class);
        $registry->handler('notes', NotesHandler::class);
        $registry->handler('remove', NotesHandler::class, Privilege::Delete);
    }
}
