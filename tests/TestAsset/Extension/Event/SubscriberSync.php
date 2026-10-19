<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Extension\Event;

use Contenir\Extension\Api\Event\EventInterface;

final readonly class SubscriberSync implements EventInterface
{
    public function __construct(
        public string $email,
    ) {}
}
