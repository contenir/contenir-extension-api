<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Integration\Conformance;

use Contenir\Extension\Api\Conformance\AbstractServiceConformanceTestCase;
use Contenir\Extension\Api\Tests\Trait\InMemoryHostTrait;
use PHPUnit\Framework\Attributes\Group;

#[Group('integration')]
#[Group('conformance')]
final class InMemoryServiceTest extends AbstractServiceConformanceTestCase
{
    use InMemoryHostTrait;
}
