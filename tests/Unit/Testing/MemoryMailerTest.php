<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Testing;

use Contenir\Extension\Api\Exception\MailFailedException;
use Contenir\Extension\Api\Mail\Message;
use Contenir\Extension\Api\Testing\MemoryMailer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class MemoryMailerTest extends TestCase
{
    #[Test]
    public function failsEveryMessageWhenFailing(): void
    {
        $mailer = MemoryMailer::failing();

        try {
            $mailer->send(new Message(['ada@example.com'], 'One', '<p>1</p>', '1'));
            static::fail('The message should have failed');
        } catch (MailFailedException $failure) {
            static::assertSame(['The mail server could not be reached', []], [$failure->getMessage(), $mailer->sent()]);
        }
    }

    #[Test]
    public function keepsTheMessagesSentInOrder(): void
    {
        $mailer = new MemoryMailer();
        $first  = new Message(['ada@example.com'], 'One', '<p>1</p>', '1');
        $second = new Message(['bo@example.com'], 'Two', '<p>2</p>', '2');
        $mailer->send($first);
        $mailer->send($second);

        static::assertSame([$first, $second], $mailer->sent());
    }
}
