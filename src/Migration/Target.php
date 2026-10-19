<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Migration;

/**
 * The database an add-on's migrations run against. The site's database is
 * the default, since that is where the public site can read the tables.
 *
 * @api
 */
enum Target: string
{
    case Cms  = 'cms';
    case Site = 'site';
}
