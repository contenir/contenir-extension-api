<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Integration\Testing;

use Contenir\Extension\Api\Conformance\Probe\Module\ProbeModuleExtension;
use Contenir\Extension\Api\Event\Extension\ExtensionPostFail;
use Contenir\Extension\Api\Event\Extension\FailurePoint;
use Contenir\Extension\Api\Module\MenuGroup;
use Contenir\Extension\Api\Module\MenuItem;
use Contenir\Extension\Api\Testing\FrozenClock;
use Contenir\Extension\Api\Testing\InMemoryHost;
use Contenir\Extension\Api\Tests\TestAsset\Edge\EdgeExtension;
use Contenir\Extension\Api\Tests\TestAsset\Module\FailingMenu;
use Contenir\Extension\Api\Tests\TestAsset\Module\ModuleFixture;
use DateTimeImmutable;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

use function array_map;

#[Group('integration')]
final class InMemoryModuleTest extends TestCase
{
    private Psr17Factory $http;

    private InMemoryHost $host;

    /**
     * @return array<string, array{string, string, int}>
     */
    public static function guardProvider(): array
    {
        return [
            'the area guards a handler no section names'                => ['area.xml', 'fixtures', 200],
            'a section that names no handler guards nothing'            => ['area.xml', 'fixtures.reports.plain', 403],
            'an empty category guards nothing'                          => ['area.xml', 'fixtures.empty', 403],
            'an admin.xml with no area leaves its own'                  => ['empty.xml', 'extension.fixture.home', 200],
            'an admin.xml that will not parse leaves its own'           => [
                'broken.xml',
                'extension.fixture.home',
                200,
            ],
            'a missing admin.xml leaves its own'                        => [
                'missing.xml',
                'extension.fixture.home',
                200,
            ],
            'adding to an existing area guards the rest by its section' => ['existing.xml', 'site.pages.boom', 200],
            'adding to an existing area takes the rest from their own'  => [
                'existing.xml',
                'extension.fixture.home',
                403,
            ],
            'an existing area with no sections leaves them their own'   => [
                'existing-empty.xml',
                'extension.fixture.home',
                200,
            ],
            'the first section added guards the rest'                   => ['existing-two.xml', 'site.pages.boom', 200],
            'a later section added guards nothing else'                 => [
                'existing-two.xml',
                'site.pages.later',
                403,
            ],
            'an existing area guards nothing it does not name'          => ['existing.xml', 'site', 403],
            'the first area of its own guards what no section names'    => ['existing-first.xml', 'fixtures', 200],
            'a later area of its own guards nothing'                    => ['existing-first.xml', 'later', 403],
        ];
    }

    #[Test]
    public function drawsATemplateAsHtml(): void
    {
        $this->host->boot(['fixture' => new ModuleFixture('area.xml')], ['fixture']);
        $response = $this->get('fixture', 'home', ['fixtures']);

        static::assertSame(
            ['text/html', "<p>Tea &amp; cake</p>\n"],
            [$response->getHeaderLine('Content-Type'), (string) $response->getBody()],
        );
    }

    #[Test]
    public function failsATemplateThatThrowsWithoutPrintingIt(): void
    {
        $this->host->boot(['fixture' => new ModuleFixture('area.xml')], ['fixture']);
        $response = $this->get('fixture', 'boom', ['fixtures.reports.boom']);

        static::assertSame(
            [500, '', [FailurePoint::Handler]],
            [$response->getStatusCode(), (string) $response->getBody(), $this->points()],
        );
    }

    #[Test]
    public function guardsAHandlerByTheSectionNamingItThoughItsNameIsPadded(): void
    {
        $this->host->boot(['fixture' => new ModuleFixture('area.xml')], ['fixture']);

        static::assertSame(
            [403, 500],
            [
                $this->get('fixture', 'boom', ['fixtures'])->getStatusCode(),
                $this->get('fixture', 'boom', ['fixtures.reports.boom'])->getStatusCode(),
            ],
        );
    }

    #[Test]
    #[DataProvider('guardProvider')]
    public function guardsAModuleHandler(string $adminXml, string $grant, int $status): void
    {
        $this->host->boot(['fixture' => new ModuleFixture($adminXml)], ['fixture']);

        static::assertSame($status, $this->get('fixture', 'home', [$grant])->getStatusCode());
    }

    #[Test]
    public function listsAModuleMenuWithTheCurrentItem(): void
    {
        $this->host->boot([ProbeModuleExtension::HANDLE => new ProbeModuleExtension()], [ProbeModuleExtension::HANDLE]);

        static::assertEquals(
            [new MenuGroup(null, [
                new MenuItem('First note', 'notes', ['note' => 1]),
                new MenuItem('Second note', 'notes', ['note' => 2], current: true),
            ])],
            $this->host->menu(
                ProbeModuleExtension::HANDLE,
                $this->http
                    ->createServerRequest('GET', '/admin/extension/probe-module/notes?note=2')
                    ->withQueryParams([
                        'note' => '2',
                    ]),
            ),
        );
    }

    #[Test]
    public function listsNothingForAnAddOnWithoutAMenu(): void
    {
        $this->host->boot(['edge' => new EdgeExtension(), 'fixture' => new ModuleFixture('area.xml')], [
            'edge',
            'fixture',
        ]);
        $request = $this->http->createServerRequest('GET', '/admin');

        static::assertSame([[], []], [$this->host->menu('edge', $request), $this->host->menu('fixture', $request)]);
    }

    #[Test]
    public function reportsAMenuThatFailsAndListsNothing(): void
    {
        $this->host->boot(['fixture' => new ModuleFixture('area.xml', FailingMenu::class)], ['fixture']);

        static::assertSame(
            [[], [FailurePoint::Menu], ['menu']],
            [
                $this->host->menu('fixture', $this->http->createServerRequest('GET', '/admin')),
                $this->points(),
                array_map(static fn(ExtensionPostFail $failure): string => $failure->name, $this->host->failures()),
            ],
        );
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
    }

    /**
     * @param list<string> $grants
     */
    private function get(string $handle, string $handler, array $grants): ResponseInterface
    {
        return $this->host->handle(
            $this->http->createServerRequest('GET', "/admin/extension/{$handle}/{$handler}"),
            $grants,
        );
    }

    /**
     * @return list<FailurePoint>
     */
    private function points(): array
    {
        return array_map(
            static fn(ExtensionPostFail $failure): FailurePoint => $failure->point,
            $this->host->failures(),
        );
    }
}
