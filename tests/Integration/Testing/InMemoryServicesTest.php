<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Integration\Testing;

use Contenir\Extension\Api\Conformance\Probe\Journal;
use Contenir\Extension\Api\Conformance\Probe\ProbeExtension;
use Contenir\Extension\Api\Conformance\Probe\ProbeServiceInterface;
use Contenir\Extension\Api\Conformance\Probe\WrongServiceInterface;
use Contenir\Extension\Api\ExtensionInterface;
use Contenir\Extension\Api\Testing\FrozenClock;
use Contenir\Extension\Api\Testing\InMemoryHost;
use Contenir\Extension\Api\Tests\TestAsset\Edge\CopycatExtension;
use DateTimeImmutable;
use LogicException;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_column;
use function array_keys;

/**
 * The in-memory host's services and settings operations, beyond what the
 * conformance suite asks of every host.
 */
#[Group('integration')]
final class InMemoryServicesTest extends TestCase
{
    #[Test]
    public function aFailedFactoryIsNotPassedOverForTheNextProvider(): void
    {
        $host = $this->host(['probe' => new ProbeExtension(new Journal()), 'copycat' => new CopycatExtension()]);

        static::assertNull($host->provided(WrongServiceInterface::class));
    }

    #[Test]
    public function aLaterAddOnProvidesWhatAnEarlierOneDoesNot(): void
    {
        $host = $this->host(['copycat' => new CopycatExtension(), 'probe' => new ProbeExtension(new Journal())]);

        static::assertSame('probe', $host->provided(ProbeServiceInterface::class)?->madeBy());
    }

    #[Test]
    public function logsARivalForAnyOfTheFirstProvidersServices(): void
    {
        $host = $this->host(['probe' => new ProbeExtension(new Journal()), 'copycat' => new CopycatExtension()]);

        static::assertContains(
            'Add-on "copycat" also provides '
                . WrongServiceInterface::class
                . ', but add-on "probe" loaded first and provides it',
            array_column($host->logs(), 'message'),
        );
    }

    #[Test]
    public function refusesAnOperationOnASectionTheAddOnDoesNotHave(): void
    {
        $host = $this->host(['probe' => new ProbeExtension(new Journal())]);

        $this->expectExceptionObject(new LogicException('Add-on "probe" has no settings section "other"'));

        $host->operate('probe', 'other', 'note', [], ['settings.system.other']);
    }

    #[Test]
    public function refusesAnOperationTheSectionDoesNotHave(): void
    {
        $host = $this->host(['probe' => new ProbeExtension(new Journal())]);

        $this->expectExceptionObject(new LogicException('Settings section "probe" has no operation "other"'));

        $host->operate('probe', 'probe', 'other', [], ['settings.system.probe']);
    }

    #[Test]
    public function runsTheOperationNamedEvenWhenItIsNotTheLast(): void
    {
        $host = $this->host(['probe' => new ProbeExtension(new Journal())]);

        static::assertSame(
            'success',
            $host->operate('probe', 'probe', 'note', [], ['settings.system.probe'])?->messages()[0]['level'] ?? null,
        );
    }

    /**
     * @param array<string, ExtensionInterface> $installed
     */
    private function host(array $installed): InMemoryHost
    {
        $http = new Psr17Factory();
        $host = new InMemoryHost($http, $http, new FrozenClock(new DateTimeImmutable('2026-10-21T09:00:00+00:00')));
        $host->boot($installed, array_keys($installed), [Journal::class => new Journal()]);

        return $host;
    }
}
