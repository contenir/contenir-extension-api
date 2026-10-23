<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Integration\Conformance;

use Contenir\Extension\Api\Conformance\AbstractEditorConformanceTestCase;
use Contenir\Extension\Api\Tests\Trait\InMemoryHostTrait;
use PHPUnit\Framework\Attributes\Group;

#[Group('integration')]
#[Group('conformance')]
final class InMemoryEditorTest extends AbstractEditorConformanceTestCase
{
    use InMemoryHostTrait;
}
