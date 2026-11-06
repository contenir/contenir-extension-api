<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Module;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Module\Meter;
use Contenir\Extension\Api\Module\MeterInterface;
use Contenir\Extension\Api\Module\MeterTone;

final class ProbeMeter implements MeterInterface
{
    public function meter(ExtensionContextInterface $context): ?Meter
    {
        return new Meter('72', 'Needs work', MeterTone::Warning);
    }
}
