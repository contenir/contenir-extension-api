<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Extension;

use Contenir\Extension\Api\Operation\RowHydrationContextInterface;
use Contenir\Extension\Api\Operation\RowHydratorInterface;
use Override;

final class ReadConfig implements RowHydratorInterface
{
    #[Override]
    public function hydrate(RowHydrationContextInterface $context): void {}
}
