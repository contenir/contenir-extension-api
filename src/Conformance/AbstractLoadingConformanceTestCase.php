<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance;

use Contenir\Extension\Api\Conformance\Probe\InvalidExtension;
use Contenir\Extension\Api\Conformance\Probe\Journal;
use Contenir\Extension\Api\Conformance\Probe\ProbeExtension;
use Contenir\Extension\Api\Conformance\Probe\ThrowingExtension;
use PHPUnit\Framework\Attributes\Test;

use function array_keys;

/**
 * Loading add-ons: only those a site enables, each registered once, and one
 * that fails to load skipped without taking the others down (plan sections
 * 7 and 12).
 *
 * @api
 */
abstract class AbstractLoadingConformanceTestCase extends AbstractConformanceTestCase
{
    #[Test]
    public function leavesAnInstalledAddOnAloneUntilItIsEnabled(): void
    {
        $this->host->boot(
            [ProbeExtension::HANDLE => new ProbeExtension($this->journal)],
            [],
            [
                Journal::class => $this->journal,
            ],
        );

        static::assertSame([[], 0], [$this->host->loaded(), $this->journal->registrations()]);
    }

    #[Test]
    public function loadsAnEnabledAddOn(): void
    {
        $this->bootProbe();

        static::assertSame([[ProbeExtension::HANDLE], 1], [$this->host->loaded(), $this->journal->registrations()]);
    }

    #[Test]
    public function saysWhyAnAddOnWasSkipped(): void
    {
        $this->host->boot(['throwing' => new ThrowingExtension()], ['throwing']);

        static::assertStringContainsString(ThrowingExtension::MESSAGE, $this->host->problems()['throwing'] ?? '');
    }

    #[Test]
    public function skipsAnAddOnWhoseRegisterThrowsAndLoadsTheRest(): void
    {
        $this->host->boot(
            ['throwing' => new ThrowingExtension(), ProbeExtension::HANDLE => new ProbeExtension($this->journal)],
            ['throwing', ProbeExtension::HANDLE],
            [Journal::class => $this->journal],
        );

        static::assertSame(
            [
                [ProbeExtension::HANDLE],
                ['throwing'],
            ],
            [$this->host->loaded(), array_keys($this->host->problems())],
        );
    }

    #[Test]
    public function skipsAnAddOnWithAnInvalidDeclaration(): void
    {
        $this->host->boot(['invalid' => new InvalidExtension()], ['invalid']);

        static::assertSame([[], ['invalid']], [$this->host->loaded(), array_keys($this->host->problems())]);
    }
}
