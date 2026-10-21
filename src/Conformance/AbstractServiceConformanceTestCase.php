<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance;

use Contenir\Extension\Api\Conformance\Probe\BrokenServiceInterface;
use Contenir\Extension\Api\Conformance\Probe\ProbeExtension;
use Contenir\Extension\Api\Conformance\Probe\ProbeServiceInterface;
use Contenir\Extension\Api\Conformance\Probe\RivalExtension;
use Contenir\Extension\Api\Conformance\Probe\WrongServiceInterface;
use Contenir\Extension\Api\Event\Extension\ExtensionPostFail;
use JsonSerializable;
use PHPUnit\Framework\Attributes\Test;

use function array_column;
use function array_filter;
use function array_map;

/**
 * Services add-ons provide: the host makes one with its add-on's context,
 * once, only while the add-on is enabled, from the first enabled add-on to
 * provide it; a factory that fails, or makes something else, is reported
 * and provides nothing.
 *
 * @api
 */
abstract class AbstractServiceConformanceTestCase extends AbstractConformanceTestCase
{
    #[Test]
    public function aDisabledAddOnProvidesNothing(): void
    {
        $this->host->boot([ProbeExtension::HANDLE => new ProbeExtension($this->journal)], []);

        static::assertNull($this->host->provided(ProbeServiceInterface::class));
    }

    #[Test]
    public function aFactoryThatFailsIsReportedAndProvidesNothing(): void
    {
        $this->bootProbe();

        static::assertSame(
            [null, [['probe', 'service', BrokenServiceInterface::class]]],
            [$this->host->provided(BrokenServiceInterface::class), $this->failures()],
        );
    }

    #[Test]
    public function aFactoryThatMakesSomethingElseIsReportedAndProvidesNothing(): void
    {
        $this->bootProbe();

        static::assertSame(
            [null, [['probe', 'service', WrongServiceInterface::class]]],
            [$this->host->provided(WrongServiceInterface::class), $this->failures()],
        );
    }

    #[Test]
    public function anEnabledAddOnProvidesItsServiceMadeWithItsContext(): void
    {
        $this->bootProbe();

        static::assertSame('probe', $this->host->provided(ProbeServiceInterface::class)?->madeBy());
    }

    #[Test]
    public function aServiceIsMadeOnce(): void
    {
        $this->bootProbe();

        static::assertSame(
            $this->host->provided(ProbeServiceInterface::class),
            $this->host->provided(ProbeServiceInterface::class),
        );
    }

    #[Test]
    public function aServiceNoAddOnProvidesIsNull(): void
    {
        $this->bootProbe();

        static::assertNull($this->host->provided(JsonSerializable::class));
    }

    #[Test]
    public function theFirstAddOnToLoadProvidesAServiceAndTheOthersAreLogged(): void
    {
        $this->host->boot(
            [
                ProbeExtension::HANDLE => new ProbeExtension($this->journal),
                RivalExtension::HANDLE => new RivalExtension(),
            ],
            [ProbeExtension::HANDLE, RivalExtension::HANDLE],
        );

        static::assertSame(
            [
                'probe',
                [
                    'Add-on "rival" also provides '
                        . ProbeServiceInterface::class
                        . ', but add-on "probe" loaded first and provides it',
                ],
            ],
            [
                $this->host->provided(ProbeServiceInterface::class)?->madeBy(),
                array_column(
                    array_filter(
                        $this->host->logs(),
                        /** @param array{level: string, message: string, context: array<array-key, mixed>} $entry */
                        static fn(array $entry): bool => (
                            'warning' === $entry['level']
                            && ['source' => 'extension.rival'] === $entry['context']
                        ),
                    ),
                    'message',
                ),
            ],
        );
    }

    /**
     * Each failure the host announced, as its add-on, where and name.
     *
     * @return list<array{string, string, string}>
     */
    private function failures(): array
    {
        return array_map(
            /** @return array{string, string, string} */
            static fn(ExtensionPostFail $failure): array => [
                $failure->handle->value,
                $failure->point->value,
                $failure->name,
            ],
            $this->host->failures(),
        );
    }
}
