<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Diagnostic\CheckInterface;
use Contenir\Extension\Api\Diagnostic\CheckResult;
use Contenir\Extension\Api\Diagnostic\CopyText;
use Contenir\Extension\Api\Diagnostic\GuidedFix;
use Contenir\Extension\Api\Form\Field;
use Contenir\Extension\Api\Form\Form;
use Override;

/**
 * Fails until a key has been stored, offering a guided fix to store one.
 *
 * @api
 */
final class KeyCheck implements CheckInterface
{
    #[Override]
    public function check(ExtensionContextInterface $context): CheckResult
    {
        if (Journal::from($context)->has('key:abc')) {
            return CheckResult::success('The key is stored');
        }

        return CheckResult::failure('No key is stored', new GuidedFix(
            label: 'Add the key',
            explanation: 'Copy the key from the provider and enter it here.',
            copy: [new CopyText('Where to find it', 'Account > API keys')],
            form: new Form([Field::password('key', 'Key')]),
            handler: StoreKey::class,
        ));
    }

    #[Override]
    public function label(): string
    {
        return 'Probe key';
    }
}
