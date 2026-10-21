<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing;

use Contenir\Extension\Api\Exception\MailFailedException;
use Contenir\Extension\Api\Mail\MailerInterface;
use Contenir\Extension\Api\Mail\Message;
use Override;

/**
 * A mailer that keeps what it is given, for tests to read back, or one that
 * fails every message as a mail server that can't be reached would.
 *
 * @api
 */
final class MemoryMailer implements MailerInterface
{
    /**
     * @var list<Message>
     */
    private array $sent = [];

    private bool $fails = false;

    /**
     * A mailer whose every send fails.
     */
    public static function failing(): self
    {
        $mailer        = new self();
        $mailer->fails = true;

        return $mailer;
    }

    #[Override]
    public function send(Message $message): void
    {
        if ($this->fails) {
            throw new MailFailedException('The mail server could not be reached');
        }

        $this->sent[] = $message;
    }

    /**
     * The messages sent, in order.
     *
     * @return list<Message>
     */
    public function sent(): array
    {
        return $this->sent;
    }
}
