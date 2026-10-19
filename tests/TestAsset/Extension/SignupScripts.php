<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Extension;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Script\ConsentCategory;
use Contenir\Extension\Api\Script\Placement;
use Contenir\Extension\Api\Script\Script;
use Contenir\Extension\Api\Script\ScriptProviderInterface;
use Override;

final class SignupScripts implements ScriptProviderInterface
{
    #[Override]
    public function scripts(ExtensionContextInterface $context): array
    {
        return [Script::src('https://example.com/signup.js', Placement::BodyEnd, ConsentCategory::Marketing)];
    }
}
