<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Extension;

use Contenir\Extension\Api\Operation\SaveContextInterface;
use Contenir\Extension\Api\Operation\SaveListenerInterface;
use Override;

final class WriteConfig implements SaveListenerInterface
{
    #[Override]
    public function onSave(SaveContextInterface $context): void {}
}
