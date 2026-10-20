<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Extension;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Panel\Link;
use Contenir\Extension\Api\Panel\Panel;
use Contenir\Extension\Api\Panel\RecordPanelInterface;
use Contenir\Extension\Api\Panel\RecordView;
use Override;

use function is_string;

/**
 * Says whether a member is on the mailing list, for a record with an email.
 */
final class SubscriberPanel implements RecordPanelInterface
{
    #[Override]
    public function panel(RecordView $record, ExtensionContextInterface $context): ?Panel
    {
        $email = $record->values['email'] ?? null;
        if (! is_string($email) || '' === $email) {
            return null;
        }

        return new Panel(
            'Newsletter',
            ["{$email} is subscribed."],
            [new Link('Open the list', '/admin/extension/newsletter/subscribers')],
        );
    }
}
