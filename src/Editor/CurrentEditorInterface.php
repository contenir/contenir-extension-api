<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Editor;

/**
 * Who is signed in to the CMS, for add-ons that record who did something,
 * such as who published a menu. A host offers it from each add-on's
 * services(), named by this interface; not every host does, so an add-on
 * asks has() first.
 *
 * @api
 */
interface CurrentEditorInterface
{
    /**
     * The editor signed in for the admin request being answered, or null
     * when no one is: a public webhook, a scheduled task or a listener
     * running in the background.
     */
    public function current(): ?Editor;
}
