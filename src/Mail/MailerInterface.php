<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Mail;

use Contenir\Extension\Api\Exception\MailFailedException;

/**
 * The site's mail, lent to add-ons: it sends a message the way the site
 * sends its own. A host that sends mail offers it from each add-on's
 * services(), named by this interface; not every host does, so an add-on
 * asks has() first and carries on without it.
 *
 * @api
 */
interface MailerInterface
{
    /**
     * @throws MailFailedException When the message couldn't be sent.
     */
    public function send(Message $message): void;
}
