<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Storage;

/**
 * The names of the storage profiles a host lends add-ons. A host that lends
 * storage offers contenir/contenir-storage's StorageManager from each
 * add-on's services(), named by its class; not every host does, so an add-on
 * asks has() first. Its profiles include the site's own, primary as the site
 * has it, and PRIVATE.
 *
 * @api
 */
final class StorageProfile
{
    /**
     * A profile no URL reaches, for files only a handler should send, such
     * as a form's uploads behind a signed link.
     */
    public const string PRIVATE = 'private';
}
