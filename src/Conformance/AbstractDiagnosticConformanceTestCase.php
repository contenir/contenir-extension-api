<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance;

use Contenir\Extension\Api\Diagnostic\GuidedFix;
use Contenir\Extension\Api\Diagnostic\Status;
use Override;
use PHPUnit\Framework\Attributes\Test;

/**
 * Add-ons' checks in the update check, with one-click and guided fixes
 * (plan section 9, P16): applying a fix needs extension.<handle>.fix, and
 * runs the check again.
 *
 * @api
 */
abstract class AbstractDiagnosticConformanceTestCase extends AbstractConformanceTestCase
{
    private const array FIXER = ['extension.probe.fix'];

    #[Test]
    public function aFixThatDoesNotWorkLeavesTheCheckFailing(): void
    {
        $result = $this->host->fix('probe', 'key', [], self::FIXER);

        static::assertSame(Status::Failure, $result?->status);
    }

    #[Test]
    public function aGuidedFixGetsWhatWasEnteredAndTheCheckRunsAgain(): void
    {
        $result = $this->host->fix('probe', 'key', ['key' => 'abc'], self::FIXER);

        static::assertSame(
            [Status::Success, ['key:abc']],
            [$result?->status, $this->journal->entries()],
        );
    }

    #[Test]
    public function aOneClickFixRunsAndTheCheckRunsAgain(): void
    {
        $result = $this->host->fix('probe', 'cache', [], self::FIXER);

        static::assertSame(Status::Success, $result?->status);
    }

    #[Test]
    public function applyingAFixNeedsTheAddOnsFixPermission(): void
    {
        $result = $this->host->fix('probe', 'key', ['key' => 'abc'], ['extension.probe.status']);

        static::assertSame([null, []], [$result, $this->journal->entries()]);
    }

    #[Test]
    public function runsAnAddOnsCheck(): void
    {
        $result = $this->host->check('probe', 'key');

        static::assertSame([Status::Failure, true], [$result->status, $result->fix instanceof GuidedFix]);
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->bootProbe();
    }
}
