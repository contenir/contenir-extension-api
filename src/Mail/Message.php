<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Mail;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;

use function trim;

/**
 * One email for MailerInterface to send: who to, its subject, its body as
 * HTML and as plain text, and who it is from and replies go to, when the
 * site's own sender shouldn't be used.
 *
 * @api
 */
final readonly class Message
{
    /**
     * @param list<string> $to
     *
     * @throws InvalidDeclarationException When it has no recipient, an empty one, or no subject.
     *
     * @mago-expect lint:excessive-parameter-list A message is written with named arguments; the sender and reply address are optional.
     */
    public function __construct(
        public array $to,
        public string $subject,
        public string $html,
        public string $text,
        public ?string $from = null,
        public ?string $replyTo = null,
    ) {
        if ([] === $to) {
            throw new InvalidDeclarationException('A message needs a recipient');
        }

        foreach ($to as $address) {
            if ('' === trim($address)) {
                throw new InvalidDeclarationException('A message recipient must not be empty');
            }
        }

        if ('' === trim($subject)) {
            throw new InvalidDeclarationException('A message subject must not be empty');
        }
    }
}
