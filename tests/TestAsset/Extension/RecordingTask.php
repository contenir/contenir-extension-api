<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Extension;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Editor\CurrentEditorInterface;
use Contenir\Extension\Api\Queue\QueuedTaskInterface;
use Override;
use RuntimeException;

use function json_encode;

/**
 * Logs its payload and the editor it ran as; a payload with "fail" makes it throw.
 */
final class RecordingTask implements QueuedTaskInterface
{
    #[Override]
    public function run(array $payload, ExtensionContextInterface $context): void
    {
        if (isset($payload['fail'])) {
            throw new RuntimeException('The task could not finish');
        }

        $editor = $context->services()->get(CurrentEditorInterface::class);
        $name   = $editor instanceof CurrentEditorInterface ? $editor->current()?->name : null;
        $context->logger()->info('ran ' . json_encode([$payload, $name]));
    }
}
