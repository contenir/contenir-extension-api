<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use RuntimeException;

/**
 * A main-stage listener failed, so the action rolls back. The message is the
 * failure's safe summary.
 *
 * @internal
 */
final class ListenerFailedException extends RuntimeException {}
