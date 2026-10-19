<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Settings;

/**
 * Where a settings section sits in Site Settings.
 *
 * @api
 */
enum SettingsCategory: string
{
    case Contact     = 'contact';
    case Integration = 'integration';
    case Seo         = 'seo';
    case Site        = 'site';
    case Social      = 'social';
    case System      = 'system';
}
