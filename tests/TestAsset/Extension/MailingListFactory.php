<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Extension;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Service\ServiceFactoryInterface;
use Override;

final class MailingListFactory implements ServiceFactoryInterface
{
    #[Override]
    public function create(ExtensionContextInterface $context): MailingListInterface
    {
        return new readonly class($context->handle()->value) implements MailingListInterface {
            public function __construct(
                private string $name,
            ) {}

            #[Override]
            public function name(): string
            {
                return $this->name;
            }
        };
    }
}
