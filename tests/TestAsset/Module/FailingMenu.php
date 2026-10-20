<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Module;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Module\MenuInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class FailingMenu implements MenuInterface
{
    #[Override]
    public function groups(ServerRequestInterface $request, ExtensionContextInterface $context): array
    {
        throw new RuntimeException('The forms table is missing');
    }
}
