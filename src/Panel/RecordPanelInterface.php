<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Panel;

use Contenir\Extension\Api\Context\ExtensionContextInterface;

/**
 * A panel beside one record on its view and edit screens, such as SEO's
 * notes. The host asks for it on sections a site binds the panel to, and
 * draws what it returns in its own theme. A panel only shows: anything it
 * offers to do is a link.
 *
 * @api
 */
interface RecordPanelInterface
{
    /**
     * What to show beside $record, or null to show nothing.
     */
    public function panel(RecordView $record, ExtensionContextInterface $context): ?Panel;
}
