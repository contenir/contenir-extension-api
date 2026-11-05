<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Testing;

use Contenir\Extension\Api\Conformance\Probe\Module\ProbeModuleExtension;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Site\SiteAnswer;
use Contenir\Extension\Api\Site\SiteRequestsInterface;
use Contenir\Extension\Api\Testing\FrozenClock;
use Contenir\Extension\Api\Testing\InMemoryHost;
use Contenir\Extension\Api\Url\RecordReference;
use DateTimeImmutable;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The site the in-memory host stands in with for an add-on's tests.
 */
#[Group('unit')]
final class InMemoryHostSiteTest extends TestCase
{
    private const string SECTION = 'site.pages.page';

    private InMemoryHost $host;

    private SiteRequestsInterface $site;

    #[Test]
    public function anAnswerForAPathOutsideTheApiIsRefused(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        $this->host->answerSite('/admin/users', new SiteAnswer(200, ''));
    }

    #[Test]
    public function answersAPathAsTheTestSaid(): void
    {
        $answer = new SiteAnswer(200, '{"title":"About"}');
        $this->host->answerSite('/api/seo/inspect', $answer);
        $this->host->seed(self::SECTION, 1, ['title' => 'About']);

        static::assertSame($answer, $this->site->post(new RecordReference(self::SECTION, 1), '/api/seo/inspect'));
    }

    #[Test]
    public function aPathOutsideTheApiIsRefused(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        $this->site->post(new RecordReference(self::SECTION, 1), '/admin/users');
    }

    #[Test]
    public function aPathWithNoAnswerGetsNone(): void
    {
        $this->host->seed(self::SECTION, 1, ['title' => 'About']);

        static::assertNull($this->site->post(new RecordReference(self::SECTION, 1), '/api/seo/inspect'));
    }

    #[Test]
    public function aRecordThatIsNotStoredGetsNone(): void
    {
        $this->host->answerSite('/api/seo/inspect', new SiteAnswer(200, '{}'));

        static::assertNull($this->site->post(new RecordReference(self::SECTION, 1), '/api/seo/inspect'));
    }

    #[Test]
    public function listsWhatWasAsked(): void
    {
        $record = new RecordReference(self::SECTION, 1);
        $this->host->seed(self::SECTION, 1, ['title' => 'About']);
        $this->site->post($record, '/api/seo/inspect', ['path' => '/about']);

        static::assertEquals(
            [['record' => $record, 'path' => '/api/seo/inspect', 'fields' => ['path' => '/about']]],
            $this->host->sitePosts(),
        );
    }

    protected function setUp(): void
    {
        $http       = new Psr17Factory();
        $this->host = new InMemoryHost(
            $http,
            $http,
            new FrozenClock(new DateTimeImmutable('2026-11-05T09:00:00+00:00')),
        );
        $this->host->boot(
            [ProbeModuleExtension::HANDLE => new ProbeModuleExtension()],
            [ProbeModuleExtension::HANDLE],
        );
        $this->site = $this->host
            ->context(ProbeModuleExtension::HANDLE)
            ->services()
            ->get(SiteRequestsInterface::class);
    }
}
