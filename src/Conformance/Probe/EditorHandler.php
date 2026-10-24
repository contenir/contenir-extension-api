<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Editor\CurrentEditorInterface;
use Contenir\Extension\Api\Handler\HandlerInterface;
use Contenir\Extension\Api\Handler\HandlerResult;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Answers with the editor the host says is signed in, as JSON: their id and
 * name, or null for no one. It answers 404 when the host lends no editor.
 *
 * @api
 */
final class EditorHandler implements HandlerInterface
{
    /**
     * @throws ContainerExceptionInterface When the host can't make its editor.
     */
    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): HandlerResult
    {
        $editors = $context->services()->has(CurrentEditorInterface::class)
            ? $context->services()->get(CurrentEditorInterface::class)
            : null;
        if (! $editors instanceof CurrentEditorInterface) {
            return HandlerResult::json(null, 404);
        }

        $editor = $editors->current();

        return HandlerResult::json(null === $editor ? null : ['id' => $editor->id, 'name' => $editor->name]);
    }
}
