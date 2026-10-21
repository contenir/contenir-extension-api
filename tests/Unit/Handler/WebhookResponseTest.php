<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Handler;

use Contenir\Extension\Api\Handler\Download;
use Contenir\Extension\Api\Handler\WebhookResponse;
use JsonException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use const NAN;

#[Group('unit')]
final class WebhookResponseTest extends TestCase
{
    #[Test]
    public function answersEmptyWithAGivenStatus(): void
    {
        static::assertSame(202, WebhookResponse::empty(202)->status);
    }

    #[Test]
    public function answersJsonWithAGivenStatus(): void
    {
        static::assertSame(409, WebhookResponse::json([], 409)->status);
    }

    #[Test]
    public function answersTextWithAGivenStatus(): void
    {
        static::assertSame(400, WebhookResponse::text('bad', 400)->status);
    }

    #[Test]
    public function answersWithADownload(): void
    {
        $writer   = static function ($stream): void {};
        $response = WebhookResponse::download('form.pdf', 'application/pdf', $writer);

        static::assertEquals(
            [200, '', 'application/pdf', new Download('form.pdf', 'application/pdf', $writer)],
            [$response->status, $response->body, $response->contentType, $response->download],
        );
    }

    #[Test]
    public function answersWithJson(): void
    {
        $response = WebhookResponse::json(['received' => true]);

        static::assertSame(
            [200, '{"received":true}', 'application/json'],
            [$response->status, $response->body, $response->contentType],
        );
    }

    #[Test]
    public function answersWithNoContentByDefault(): void
    {
        $response = WebhookResponse::empty();

        static::assertSame([204, '', 'text/plain'], [$response->status, $response->body, $response->contentType]);
    }

    #[Test]
    public function answersWithoutADownloadOtherwise(): void
    {
        static::assertNull(WebhookResponse::text('ok')->download);
    }

    #[Test]
    public function answersWithText(): void
    {
        $response = WebhookResponse::text('ok');

        static::assertSame([200, 'ok', 'text/plain'], [$response->status, $response->body, $response->contentType]);
    }

    #[Test]
    public function failsOnDataJsonCannotHold(): void
    {
        $this->expectException(JsonException::class);

        WebhookResponse::json(NAN);
    }
}
