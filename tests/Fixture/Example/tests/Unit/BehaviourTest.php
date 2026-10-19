<?php

declare(strict_types=1);

namespace Contenir\Extension\Example\Tests\Unit;

use Contenir\Extension\Api\Diagnostic\Status;
use Contenir\Extension\Api\Testing\FrozenClock;
use Contenir\Extension\Api\Testing\InMemoryHost;
use Contenir\Extension\Example\ExampleExtension;
use Contenir\Extension\Example\Ping;
use Contenir\Extension\Example\Secret;
use DateTimeImmutable;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

use function hash_hmac;

#[Group('unit')]
final class BehaviourTest extends TestCase
{
    private const string SHARED = 'shared-with-the-service';

    private const string BODY = '{"ping":1}';

    private Psr17Factory $http;

    private InMemoryHost $host;

    #[Test]
    public function answersASignedPing(): void
    {
        $response = $this->host->handle($this->ping(hash_hmac(
            algo: 'sha256',
            data: self::BODY,
            key: self::SHARED,
        )));

        static::assertSame([200, '{"pong":true}'], [$response->getStatusCode(), (string) $response->getBody()]);
    }

    #[Test]
    public function failsItsCheckWithoutASecret(): void
    {
        $host = new InMemoryHost(
            $this->http,
            $this->http,
            new FrozenClock(new DateTimeImmutable('2026-10-19T09:00:00+00:00')),
        );
        $host->boot(['example' => new ExampleExtension()], ['example']);

        static::assertSame(Status::Failure, $host->check('example', 'secret')->status);
    }

    #[Test]
    public function logsANewTitleOnceTheSaveGoesThrough(): void
    {
        $this->host->bind('article', 'example', 'note-title', 'RecordPostSave');
        $this->host->seed('article', 3, ['title' => 'Old']);
        $this->host->save('article', 3, ['title' => 'New']);
        $this->host->runBackground();

        static::assertSame(
            ['A title changed', 'New', 'extension.example'],
            [
                $this->host->logs()[0]['message'] ?? null,
                $this->host->logs()[0]['context']['title'] ?? null,
                $this->host->logs()[0]['context']['source'] ?? null,
            ],
        );
    }

    #[Test]
    public function passesItsCheckOnceTheSecretIsSet(): void
    {
        static::assertSame(Status::Success, $this->host->check('example', 'secret')->status);
    }

    #[Test]
    public function refusesAPingSignedWithAnotherSecret(): void
    {
        $response = $this->host->handle($this->ping(hash_hmac(
            algo: 'sha256',
            data: self::BODY,
            key: 'guessed',
        )));

        static::assertSame(401, $response->getStatusCode());
    }

    #[Override]
    protected function setUp(): void
    {
        $this->http = new Psr17Factory();
        $this->host = new InMemoryHost(
            $this->http,
            $this->http,
            new FrozenClock(new DateTimeImmutable('2026-10-19T09:00:00+00:00')),
        );
        $this->host->boot(
            ['example' => new ExampleExtension()],
            ['example'],
            [Secret::class => new Secret(self::SHARED)],
        );
    }

    private function ping(string $signature): ServerRequestInterface
    {
        return $this->http
            ->createServerRequest('POST', 'https://cms.example.com/extension/example/ping')
            ->withHeader(Ping::HEADER, $signature)
            ->withBody($this->http->createStream(self::BODY));
    }
}
