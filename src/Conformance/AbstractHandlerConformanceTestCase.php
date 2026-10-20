<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance;

use Contenir\Extension\Api\Conformance\Probe\Hook;
use Contenir\Extension\Api\Event\Extension\FailurePoint;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Add-ons' requests (plan section 9, P1): admin handlers under
 * /admin/extension/<handle>/<name> behind their ACL resource, and public
 * webhooks under /api/extension/<handle>/<name>, run only once they verify.
 *
 * @api
 */
abstract class AbstractHandlerConformanceTestCase extends AbstractConformanceTestCase
{
    #[Test]
    public function aFailingHandlerIsReported(): void
    {
        $response = $this->host->handle(
            $this->request('POST', '/admin/extension/probe/broken'),
            [
                'extension.probe.broken',
            ],
        );
        $failure = $this->host->failures()[0] ?? null;

        static::assertSame(
            [500, FailurePoint::Handler, 'broken'],
            [$response->getStatusCode(), $failure?->point, $failure?->name],
        );
    }

    #[Test]
    public function aHandlerIsNotAWebhook(): void
    {
        $response = $this->host->handle(
            $this->request('GET', '/api/extension/probe/status')->withHeader(Hook::HEADER, Hook::SIGNATURE),
            ['extension.probe.status'],
        );

        static::assertSame([404, []], [$response->getStatusCode(), $this->journal->entries()]);
    }

    #[Test]
    public function anAdminHandlerAnswersSomeoneHoldingItsPermission(): void
    {
        $response = $this->host->handle(
            $this->request('GET', '/admin/extension/probe/status'),
            [
                'extension.probe.status',
            ],
        );

        static::assertSame(
            [200, '{"ok":true}', ['status']],
            [$response->getStatusCode(), (string) $response->getBody(), $this->journal->entries()],
        );
    }

    #[Test]
    public function anAdminHandlerRefusesSomeoneWithoutItsPermission(): void
    {
        $response = $this->host->handle(
            $this->request('GET', '/admin/extension/probe/status'),
            [
                'extension.probe.other',
            ],
        );

        static::assertSame([403, []], [$response->getStatusCode(), $this->journal->entries()]);
    }

    #[Test]
    public function aWebhookAnswersAVerifiedRequestWithItsRawBody(): void
    {
        $response = $this->host->handle(
            $this->request('POST', '/api/extension/probe/hook', 'payload')->withHeader(Hook::HEADER, Hook::SIGNATURE),
        );

        static::assertSame(
            [202, 'received', ['hook:POST:payload']],
            [$response->getStatusCode(), (string) $response->getBody(), $this->journal->entries()],
        );
    }

    #[Test]
    public function aWebhookAnswersGetAsWellAsPost(): void
    {
        $response = $this->host->handle(
            $this->request('GET', '/api/extension/probe/hook')->withHeader(Hook::HEADER, Hook::SIGNATURE),
        );

        static::assertSame([202, ['hook:GET:']], [$response->getStatusCode(), $this->journal->entries()]);
    }

    #[Test]
    public function aWebhookNeedsNoPermission(): void
    {
        $response = $this->host->handle(
            $this->request('POST', '/api/extension/probe/hook')->withHeader(Hook::HEADER, Hook::SIGNATURE),
            [],
        );

        static::assertSame(202, $response->getStatusCode());
    }

    #[Test]
    public function aWebhookRefusesARequestItCannotVerify(): void
    {
        $response = $this->host->handle($this->request('POST', '/api/extension/probe/hook', 'payload'));

        static::assertSame([401, []], [$response->getStatusCode(), $this->journal->entries()]);
    }

    /**
     * Builds the requests the tests send.
     */
    abstract protected function requests(): ServerRequestFactoryInterface;

    /**
     * Builds the request bodies the tests send.
     */
    abstract protected function streams(): StreamFactoryInterface;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->bootProbe();
    }

    private function request(string $method, string $path, string $body = ''): ServerRequestInterface
    {
        return $this->requests()
            ->createServerRequest($method, "https://cms.example.com{$path}")
            ->withBody($this->streams()->createStream($body));
    }
}
