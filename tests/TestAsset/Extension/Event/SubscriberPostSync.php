<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Extension\Event;

use Contenir\Extension\Api\Event\AbstractPostEvent;

final readonly class SubscriberPostSync extends AbstractPostEvent
{
    public function __construct(
        public string $email,
    ) {
        parent::__construct();
    }
}
