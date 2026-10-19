<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Exception;

use Throwable;

/**
 * Marks every exception the extension API throws.
 *
 * @api
 */
interface ExceptionInterface extends Throwable {}
