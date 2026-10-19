<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Event\Extension\FailurePoint;
use Contenir\Extension\Api\Handler\HandlerInterface;
use Contenir\Extension\Api\Registration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

use function in_array;

/**
 * Answers requests to add-ons' admin handlers, for someone holding the
 * handler's ACL resource.
 *
 * @internal
 */
final readonly class AdminHandlers
{
    public function __construct(
        private Runtime $runtime,
        private Responder $responder,
    ) {}

    /**
     * @param list<string> $grants
     */
    public function answer(
        Registration $registration,
        string $name,
        ServerRequestInterface $request,
        array $grants,
    ): ResponseInterface {
        $declaration = $registration->handlers[$name] ?? null;
        if (null === $declaration) {
            return $this->responder->status(404);
        }

        if (! in_array($registration->handle->permission($name), $grants, strict: true)) {
            return $this->responder->status(403);
        }

        try {
            $result = $this->runtime
                ->make($declaration->class, HandlerInterface::class)
                ->handle($request, $this->runtime->context($registration->handle));

            return $this->responder->respond($result, $registration->handle);
        } catch (Throwable $failure) {
            $this->runtime->report($registration->handle, FailurePoint::Handler, $name, $failure);

            return $this->responder->status(500);
        }
    }
}
