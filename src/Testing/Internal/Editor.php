<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Handler\Privilege;

use function in_array;

/**
 * Who the host is acting for: the grants of the request being answered, or,
 * outside a request, an editor allowed everything, as when a test calls an
 * add-on's classes directly with its context.
 *
 * @internal
 */
final class Editor
{
    /**
     * @var list<string>|null
     */
    private ?array $grants = null;

    /**
     * Whether the editor holds $privilege on $resource, as handle() reads
     * grants.
     */
    public function may(string $resource, Privilege $privilege): bool
    {
        return (
            null === $this->grants
                || in_array($resource, $this->grants, strict: true)
                || in_array("{$resource}:{$privilege->value}", $this->grants, strict: true)
        );
    }

    /**
     * @param list<string> $grants
     */
    public function signIn(array $grants): void
    {
        $this->grants = $grants;
    }

    public function signOut(): void
    {
        $this->grants = null;
    }
}
