<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Script;

/**
 * The consent a visitor must give before a script runs. Necessary scripts
 * always run; when a site asks for consent, the others are written inert
 * (type="text/plain" data-category="…") until the visitor agrees.
 *
 * @api
 */
enum ConsentCategory: string
{
    case Analytics   = 'analytics';
    case Marketing   = 'marketing';
    case Necessary   = 'necessary';
    case Preferences = 'preferences';
}
