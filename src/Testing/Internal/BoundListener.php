<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Listener\ListenerDeclaration;
use Contenir\Extension\Api\Listener\Scope;

/**
 * A listener a site bound, with the scope its binding left it.
 *
 * @internal
 */
final readonly class BoundListener
{
    public function __construct(
        public Handle $handle,
        public ListenerDeclaration $declaration,
        public Scope $scope,
    ) {}
}
