<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\TestAsset\Extension;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Handler\HandlerInterface;
use Contenir\Extension\Api\Handler\HandlerResult;
use Contenir\Extension\Api\Queue\TaskQueueInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;

final class QueueingHandler implements HandlerInterface
{
    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): HandlerResult
    {
        $queue = $context->services()->get(TaskQueueInterface::class);
        if ($queue instanceof TaskQueueInterface) {
            $queue->enqueue($context->handle()->value, 'record', ['n' => 1]);
        }

        return HandlerResult::json(['queued' => true]);
    }
}
