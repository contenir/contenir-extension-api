<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Integration\Testing;

use Contenir\Extension\Api\Conformance\Probe\Journal;
use Contenir\Extension\Api\Conformance\Probe\ProbeExtension;
use Contenir\Extension\Api\Diagnostic\Status;
use Contenir\Extension\Api\Event\Extension\ExtensionPostFail;
use Contenir\Extension\Api\Event\Extension\FailurePoint;
use Contenir\Extension\Api\Event\Outcome;
use Contenir\Extension\Api\Event\Record\RecordPostSave;
use Contenir\Extension\Api\Event\Settings\SettingsPostSave;
use Contenir\Extension\Api\Exception\DispatchRefusedException;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Exception\UnknownSettingException;
use Contenir\Extension\Api\Testing\FrozenClock;
use Contenir\Extension\Api\Testing\InMemoryHost;
use Contenir\Extension\Api\Testing\Internal\ListenerFailedException;
use Contenir\Extension\Api\Testing\MapUrlResolver;
use Contenir\Extension\Api\Tests\TestAsset\Edge\CountingListener;
use Contenir\Extension\Api\Tests\TestAsset\Edge\EdgeExtension;
use Contenir\Extension\Api\Tests\TestAsset\Edge\Synced;
use Contenir\Extension\Api\Url\RecordReference;
use DateTimeImmutable;
use LogicException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use stdClass;

use function array_map;

#[Group('integration')]
final class InMemoryHostTest extends TestCase
{
    private Psr17Factory $http;

    private FrozenClock $clock;

    private InMemoryHost $host;

    private CountingListener $counter;

    #[Test]
    public function answersJsonWithItsContentType(): void
    {
        $this->host->boot(
            [ProbeExtension::HANDLE => new ProbeExtension(new Journal())],
            [ProbeExtension::HANDLE],
            [
                Journal::class => new Journal(),
            ],
        );
        $response = $this->host->handle(
            $this->http->createServerRequest('GET', '/admin/extension/probe/status'),
            [
                'extension.probe.status',
            ],
        );

        static::assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    #[Test]
    public function answersNotFoundForAnUnknownAddOn(): void
    {
        $response = $this->host->handle(
            $this->http->createServerRequest('GET', '/admin/extension/other/redirect'),
            [
                'extension.other.redirect',
            ],
        );

        static::assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function answersNotFoundForAnUnknownHandler(): void
    {
        static::assertSame(404, $this->admin('missing')->getStatusCode());
    }

    #[Test]
    public function answersNotFoundForAnUnknownWebhook(): void
    {
        static::assertSame(
            404,
            $this->host->handle($this->http->createServerRequest('POST', '/extension/edge/missing'))->getStatusCode(),
        );
    }

    #[Test]
    public function answersNotFoundOutsideTheExtensionPaths(): void
    {
        static::assertSame(
            [404, 404, 404],
            [
                $this->host->handle($this->http->createServerRequest('GET', '/admin/settings/edge'))->getStatusCode(),
                $this->host
                    ->handle(
                        $this->http->createServerRequest('GET', '/admin/extension/edge/redirect/more'),
                        [
                            'extension.edge.redirect',
                        ],
                    )
                    ->getStatusCode(),
                $this->host
                    ->handle(
                        $this->http->createServerRequest('GET', '/x/admin/extension/edge/redirect'),
                        [
                            'extension.edge.redirect',
                        ],
                    )
                    ->getStatusCode(),
            ],
        );
    }

    #[Test]
    public function checksAgainForAFixThatOnlyExplains(): void
    {
        static::assertSame(Status::Failure, $this->host->fix('edge', 'explain', [], ['extension.edge.fix'])?->status);
    }

    #[Test]
    public function drawsADescribedScreenAsItsTitle(): void
    {
        $response = $this->admin('screen');

        static::assertSame([200, 'text/plain', 'Subscribers'], [
            $response->getStatusCode(),
            $response->getHeaderLine('Content-Type'),
            (string) $response->getBody(),
        ]);
    }

    #[Test]
    public function failsACheckThatThrows(): void
    {
        $result = $this->host->check('edge', 'explode');

        static::assertSame(
            [Status::Failure, 'EdgeFailure: Check crashed', [FailurePoint::Check]],
            [$result->status, $result->message, $this->points()],
        );
    }

    #[Test]
    public function failsAHandlerWhoseJsonCannotBeEncoded(): void
    {
        static::assertSame([500, [FailurePoint::Handler]], [
            $this->admin('bad-json')->getStatusCode(),
            $this->points(),
        ]);
    }

    #[Test]
    public function failsAWebhookWhoseVerificationThrows(): void
    {
        $response = $this->host->handle($this->http->createServerRequest('POST', '/extension/edge/explode'));

        static::assertSame([500, [FailurePoint::Webhook]], [$response->getStatusCode(), $this->points()]);
    }

    #[Test]
    public function failsTheDispatchWhenAMainStageListenerOfAnAddOnEventFails(): void
    {
        $this->host->bind(null, 'edge', 'fail-on-sync', Synced::class);

        $this->expectException(ListenerFailedException::class);
        $this->expectExceptionMessage('RuntimeException: Sync listener failed');

        $this->host->context('edge')->events()->dispatch(new Synced());
    }

    #[Test]
    public function givesAnAddOnsCodeItsContext(): void
    {
        $context = $this->host->context('edge');

        static::assertSame(
            ['edge', $this->clock, '/var/www/site', 'https://example.com/a', true],
            [
                $context->handle()->value,
                $context->clock(),
                $context->sitePath(),
                $context->urls()->url(new RecordReference('article', 1)),
                $context->services()->has(CountingListener::class),
            ],
        );
    }

    #[Test]
    public function handsBackARegistration(): void
    {
        static::assertSame('edge', $this->host->registration('edge')->handle->value);
    }

    #[Test]
    public function handsBackTheEventDispatched(): void
    {
        $this->host->bind(null, 'edge', 'count', Synced::class);
        $event = new Synced();

        static::assertSame([$event, 1], [
            $this->host->context('edge')->events()->dispatch($event),
            $this->counter->calls,
        ]);
    }

    #[Test]
    public function keepsOneContextPerAddOn(): void
    {
        static::assertSame($this->host->context('edge'), $this->host->context('edge'));
    }

    #[Test]
    public function letsTheChainGoOnAfterAnEventFinishes(): void
    {
        $this->host->bind(null, 'edge', 'count', Synced::class);
        $events = $this->host->context('edge')->events();
        $events->dispatch(new Synced());
        $events->dispatch(new Synced());

        static::assertSame(2, $this->counter->calls);
    }

    #[Test]
    public function loadsOnlyTheEnabledAddOnsInTheOrderInstalled(): void
    {
        $host = new InMemoryHost($this->http, $this->http, $this->clock);
        $host->boot(
            ['first' => new EdgeExtension(), 'second' => new EdgeExtension(), 'third' => new EdgeExtension()],
            ['third', 'first'],
        );

        static::assertSame(['first', 'third'], $host->loaded());
    }

    #[Test]
    public function logsAFailureWithItsSource(): void
    {
        $this->host->check('edge', 'explode');
        $entry = $this->host->logs()[0] ?? null;

        static::assertSame(
            [
                'error',
                'The check "explode" failed',
                [
                    'point'   => 'check',
                    'name'    => 'explode',
                    'summary' => 'EdgeFailure: Check crashed',
                    'source'  => 'extension.edge',
                ],
            ],
            [$entry['level'] ?? null, $entry['message'] ?? null, $entry['context'] ?? null],
        );
    }

    #[Test]
    public function logsAnAddOnThatCouldNotLoad(): void
    {
        $this->host->boot(['Bad Handle' => new EdgeExtension()], ['Bad Handle']);
        $entry = $this->host->logs()[0] ?? null;

        static::assertSame(
            [
                'warning',
                'Extension "Bad Handle" could not load: Handle name "Bad Handle" must be 2 to 40 lowercase letters, digits and dashes, starting with a letter',
                ['source' => 'extension.Bad Handle'],
            ],
            [$entry['level'] ?? null, $entry['message'] ?? null, $entry['context'] ?? null],
        );
    }

    #[Test]
    public function namesTheListenerAndEventItCannotBind(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Add-on "edge" has no listener "nothing" for RecordPostSave');

        $this->host->bind('article', 'edge', 'nothing', 'RecordPostSave');
    }

    #[Test]
    public function numbersNewRecordsInEachSection(): void
    {
        static::assertSame(
            [1, 2, 1],
            [
                $this->host->save('article', null, ['title' => 'A'])->id,
                $this->host->save('article', null, ['title' => 'B'])->id,
                $this->host->save('page', null, ['title' => 'C'])->id,
            ],
        );
    }

    #[Test]
    public function prefersAFieldsDeclaredDefaultToTheCallersOwn(): void
    {
        $settings = $this->host->context('edge')->settings();

        static::assertSame(['medium', 'fallback'], [
            $settings->get('size', 'large'),
            $settings->get('mode', 'fallback'),
        ]);
    }

    #[Test]
    public function redirectsBackToTheAddOnsSettings(): void
    {
        static::assertSame('/admin/settings/edge', $this->admin('back')->getHeaderLine('Location'));
    }

    #[Test]
    public function redirectsToTheUrlAHandlerGives(): void
    {
        $response = $this->admin('redirect');

        static::assertSame([302, 'https://provider.example.com/authorize'], [
            $response->getStatusCode(),
            $response->getHeaderLine('Location'),
        ]);
    }

    #[Test]
    public function refusesACheckTheAddOnLacks(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Add-on "edge" has no check "missing"');

        $this->host->check('edge', 'missing');
    }

    #[Test]
    public function refusesAnAddOnThatIsNotLoaded(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No add-on "probe" is loaded');

        $this->host->registration('probe');
    }

    #[Test]
    public function refusesAnObjectThatIsNotAnEvent(): void
    {
        $this->expectException(DispatchRefusedException::class);
        $this->expectExceptionMessage('stdClass is not an event');

        $this->host->context('edge')->events()->dispatch(new stdClass());
    }

    #[Test]
    public function refusesASettingsSectionTheAddOnLacks(): void
    {
        $this->expectException(UnknownSettingException::class);
        $this->expectExceptionMessage('Add-on "edge" has no settings section "edge-more"');

        $this->host->context('edge')->settings('edge-more');
    }

    #[Test]
    public function refusesToBindAnAddOnThatIsNotLoaded(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('No add-on "probe" is loaded');

        $this->host->bind('article', 'probe', 'record', 'RecordPreSave');
    }

    #[Test]
    public function refusesToReadAFieldTheSectionLacks(): void
    {
        $this->expectException(UnknownSettingException::class);
        $this->expectExceptionMessage('Settings section "edge" has no field "colour"');

        $this->host->context('edge')->settings()->get('colour');
    }

    #[Test]
    public function reportsAFailingFailureListenerWithoutAnnouncingItAgain(): void
    {
        $this->host->bind(null, 'edge', 'fail-on-failure', 'ExtensionPostFail');
        $this->host->check('edge', 'explode');
        $this->host->runBackground();

        static::assertSame([FailurePoint::Check, FailurePoint::Listener], $this->points());
    }

    #[Test]
    public function reportsAFixThatThrowsAndChecksAgain(): void
    {
        $result = $this->host->fix('edge', 'broken-fix', [], ['extension.edge.fix']);

        static::assertSame([Status::Warning, [FailurePoint::Fix]], [$result?->status, $this->points()]);
    }

    #[Test]
    public function reportsAStoppedSettingsWriteWithItsMessage(): void
    {
        $this->host->bind('edge', 'edge', 'stop', 'SettingsPreSave');
        $this->host->bind('edge', 'edge', 'count', 'SettingsPostSave');
        $result = $this->host->context('edge')->settings()->set(['mode' => 'second']);

        static::assertSame(
            ['Stopped by edge', 'Stopped by edge'],
            [
                $result->message,
                $this->counter->last instanceof SettingsPostSave ? $this->counter->last->reason() : null,
            ],
        );
    }

    #[Test]
    public function rollsBackASettingsWriteWhoseMainStageFails(): void
    {
        $settings = $this->host->context('edge')->settings();
        $settings->set(['mode' => 'first']);
        $this->host->bind('edge', 'edge', 'fail-settings', 'SettingsSave');
        $result = $settings->set(['mode' => 'second']);

        static::assertSame(
            [Outcome::Failed, 'RuntimeException: Sync listener failed', 'first'],
            [$result->outcome, $result->message, $settings->get('mode')],
        );
    }

    #[Test]
    public function sitsAtSitePathByDefault(): void
    {
        $host = new InMemoryHost($this->http, $this->http, $this->clock);
        $host->boot(['edge' => new EdgeExtension()], ['edge']);

        static::assertSame(['/site', null], [
            $host->context('edge')->sitePath(),
            $host->context('edge')->urls()->url(new RecordReference('article', 1)),
        ]);
    }

    #[Test]
    public function tellsThePostListenerAboutAFailedSettingsWrite(): void
    {
        $this->host->bind('edge', 'edge', 'fail-settings', 'SettingsSave');
        $this->host->bind('edge', 'edge', 'count', 'SettingsPostSave');
        $this->host->context('edge')->settings()->set(['mode' => 'second']);

        static::assertSame(
            'RuntimeException: Sync listener failed',
            $this->counter->last instanceof SettingsPostSave ? $this->counter->last->reason() : null,
        );
    }

    #[Test]
    public function tellsThePostListenerWhyASaveStopped(): void
    {
        $this->host->bind('article', 'edge', 'stop', 'RecordPreSave');
        $this->host->bind('article', 'edge', 'count', 'RecordPostSave');
        $this->host->save('article', null, ['title' => 'A']);

        static::assertSame(
            'Stopped by edge',
            $this->counter->last instanceof RecordPostSave ? $this->counter->last->reason() : null,
        );
    }

    #[Test]
    public function usesAListenerTheServicesProvide(): void
    {
        $this->host->bind('article', 'edge', 'count', 'RecordPreSave');
        $this->host->save('article', null, ['title' => 'A']);

        static::assertSame(1, $this->counter->calls);
    }

    #[Override]
    protected function setUp(): void
    {
        $this->http    = new Psr17Factory();
        $this->clock   = new FrozenClock(new DateTimeImmutable('2026-10-19T09:00:00+00:00'));
        $this->counter = new CountingListener();
        $this->host    = new InMemoryHost(
            $this->http,
            $this->http,
            $this->clock,
            (new MapUrlResolver())->with(new RecordReference('article', 1), 'https://example.com/a'),
            '/var/www/site',
        );
        $this->host->boot(['edge' => new EdgeExtension()], ['edge'], [CountingListener::class => $this->counter]);
    }

    private function admin(string $name): ResponseInterface
    {
        return $this->host->handle($this->http->createServerRequest('GET', "/admin/extension/edge/{$name}"), [
            "extension.edge.{$name}",
        ]);
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
