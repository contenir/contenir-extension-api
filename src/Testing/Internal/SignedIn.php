<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Editor\CurrentEditorInterface;
use Contenir\Extension\Api\Editor\Editor;
use Contenir\Extension\Api\Handler\Privilege;
use Override;

use function in_array;

/**
 * Who the host is acting for: while a request is answered, its grants and
 * the editor signed in for it, if any; outside a request, no editor and an
 * allowance of everything, as when a test calls an add-on's classes
 * directly with its context.
 *
 * @internal
 */
final class SignedIn implements CurrentEditorInterface
{
    /**
     * @var list<string>|null
     */
    private ?array $grants = null;

    private ?Editor $editor = null;

    #[Override]
    public function current(): ?Editor
    {
        return $this->editor;
    }

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
    public function signIn(array $grants, ?Editor $editor): void
    {
        $this->grants = $grants;
        $this->editor = $editor;
    }

    public function signOut(): void
    {
        $this->grants = null;
        $this->editor = null;
    }
}
