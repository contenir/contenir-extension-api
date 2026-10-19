<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance;

use Composer\Autoload\ClassLoader;
use Contenir\Extension\Api\Boundary\BoundaryException;
use Contenir\Extension\Api\Boundary\BoundaryFile;
use Contenir\Extension\Api\ExtensionInterface;
use Contenir\Extension\Api\Testing\FrozenClock;
use Contenir\Extension\Api\Testing\InMemoryHost;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use ReflectionClass;
use ReflectionException;

use function dirname;
use function sprintf;

/**
 * What every add-on proves in its own CI (plan section 12): it registers
 * without problems, its mago-boundary.toml matches its composer.json (mago
 * guard then enforces the boundary), and every public webhook it declares
 * refuses a request it cannot verify (P1).
 *
 * An add-on's test suite extends it once.
 *
 * @api
 */
abstract class AbstractAddOnConformanceTestCase extends TestCase
{
    #[Test]
    public function everyWebhookRefusesARequestItCannotVerify(): void
    {
        $host     = $this->host();
        $expected = [];
        $statuses = [];
        foreach ($host->registration($this->handle())->webhooks as $name => $webhook) {
            $request = $this->http()
                ->createServerRequest('POST', "https://cms.example.com{$webhook->path}")
                ->withBody($this->http()->createStream('{}'));

            $expected[$name] = 401;
            $statuses[$name] = $host->handle($request)->getStatusCode();
        }

        static::assertSame($expected, $statuses);
    }

    /**
     * @throws BoundaryException   When the add-on breaks the boundary, which fails the test.
     * @throws ReflectionException Never: Composer's class loader is always loaded.
     */
    #[Test]
    public function keepsItsBoundaryFileCurrent(): void
    {
        $file = new BoundaryFile($this->root(), $this->installed());

        static::assertSame(
            $file->expected(),
            $file->current(),
            sprintf('%s is out of date: run vendor/bin/extension-boundary', BoundaryFile::NAME),
        );
    }

    #[Test]
    public function registersWithoutProblems(): void
    {
        $host = $this->host();

        static::assertSame([[$this->handle()], []], [$host->loaded(), $host->problems()]);
    }

    /**
     * The add-on's entry class, as the host would construct it.
     */
    abstract protected function extension(): ExtensionInterface;

    /**
     * The add-on's handle, from extra.contenir.handle.
     */
    abstract protected function handle(): string;

    /**
     * PSR-17 factories for the requests and responses the tests use.
     */
    abstract protected function http(): ServerRequestFactoryInterface&ResponseFactoryInterface&StreamFactoryInterface;

    /**
     * The add-on's root folder, holding composer.json and mago-boundary.toml.
     */
    abstract protected function root(): string;

    /**
     * Composer's installed.json for the project the tests run in.
     *
     * @throws ReflectionException Never: Composer's class loader is always loaded.
     */
    protected function installed(): string
    {
        $loader = (new ReflectionClass(ClassLoader::class))->getFileName();

        return dirname((string) $loader) . '/installed.json';
    }

    /**
     * Services the add-on's code needs from the host, by id.
     *
     * @return array<string, object>
     */
    protected function services(): array
    {
        return [];
    }

    private function host(): InMemoryHost
    {
        $host = new InMemoryHost(
            $this->http(),
            $this->http(),
            new FrozenClock(new DateTimeImmutable('2026-10-19T09:00:00+00:00')),
        );
        $host->boot([$this->handle() => $this->extension()], [$this->handle()], $this->services());

        return $host;
    }
}
