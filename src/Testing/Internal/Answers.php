<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Registration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function preg_match;

/**
 * Routes requests to add-ons' admin handlers, under
 * /admin/extension/<handle>/<name>, and public webhooks, under
 * /api/extension/<handle>/<name>.
 *
 * @internal
 */
final readonly class Answers
{
    private const string ADMIN = '#^/admin/extension/([a-z0-9-]+)/([a-z0-9-]+)$#D';

    private const string PUBLIC = '#^/api/extension/([a-z0-9-]+)/([a-z0-9-]+)$#D';

    public function __construct(
        private AdminHandlers $handlers,
        private Webhooks $webhooks,
        private Responder $responder,
    ) {}

    /**
     * @param array<string, Registration> $registrations By handle
     * @param list<string>                $grants
     */
    public function answer(array $registrations, ServerRequestInterface $request, array $grants): ResponseInterface
    {
        $path  = $request->getUri()->getPath();
        $route = [];
        $admin = 1 === preg_match(self::ADMIN, $path, $route);
        if (! $admin) {
            preg_match(self::PUBLIC, $path, $route);
        }

        $registration = $registrations[$route[1] ?? ''] ?? null;
        if (null === $registration) {
            return $this->responder->status(404);
        }

        $name = $route[2] ?? '';

        return $admin
            ? $this->handlers->answer($registration, $name, $request, $grants)
            : $this->webhooks->answer($registration, $name, $request);
    }
}
