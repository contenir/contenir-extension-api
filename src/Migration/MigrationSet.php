<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Migration;

/**
 * A folder of an add-on's migrations, the namespace their classes are in, and
 * the database they create tables in. Every table must start with the
 * add-on's ext_<handle>_ prefix, and every migration needs a down step so
 * removing the add-on can leave the database clean.
 *
 * @api
 */
final readonly class MigrationSet
{
    public function __construct(
        public string $path,
        public string $namespace,
        public Target $target,
    ) {}
}
