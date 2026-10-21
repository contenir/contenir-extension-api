<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Mail;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Mail\Message;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class MessageTest extends TestCase
{
    /**
     * @return array<string, array{list<string>, string, string}>
     */
    public static function invalidProvider(): array
    {
        return [
            'no recipient' => [[], 'Hello', 'A message needs a recipient'],
            'an empty one' => [['ada@example.com', ' '], 'Hello', 'A message recipient must not be empty'],
            'no subject'   => [['ada@example.com'], '  ', 'A message subject must not be empty'],
        ];
    }

    #[Test]
    public function keepsWhatItWasGiven(): void
    {
        $message = new Message(
            ['ada@example.com', 'bo@example.com'],
            'New entry',
            '<p>Hi</p>',
            'Hi',
            from: 'site@example.com',
            replyTo: 'ada@example.com',
        );

        static::assertSame(
            [
                ['ada@example.com', 'bo@example.com'],
                'New entry',
                '<p>Hi</p>',
                'Hi',
                'site@example.com',
                'ada@example.com',
            ],
            [$message->to, $message->subject, $message->html, $message->text, $message->from, $message->replyTo],
        );
    }

    #[Test]
    public function leavesTheSenderToTheSiteUnlessGiven(): void
    {
        $message = new Message(['ada@example.com'], 'New entry', '<p>Hi</p>', 'Hi');

        static::assertSame([null, null], [$message->from, $message->replyTo]);
    }

    /**
     * @param list<string> $to
     */
    #[Test]
    #[DataProvider('invalidProvider')]
    public function refusesAMessageWithNowhereToGoOrNoSubject(array $to, string $subject, string $reason): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage($reason);

        new Message($to, $subject, '<p>Hi</p>', 'Hi');
    }
}
