<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Extension;

use Contenir\Extension\Api\Section\SectionBlock;
use Contenir\Extension\Api\Section\SectionBlockInterface;
use Override;

final class SubscriberSections implements SectionBlockInterface
{
    #[Override]
    public function columns(SectionBlock $block): array
    {
        return ['email'];
    }

    #[Override]
    public function listeners(SectionBlock $block): array
    {
        return [];
    }
}
