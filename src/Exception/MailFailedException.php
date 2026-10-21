<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Exception;

use RuntimeException;

/**
 * The host couldn't send a message: its mail server refused it or couldn't
 * be reached. The message isn't sent; an add-on logs it and carries on.
 *
 * @api
 */
final class MailFailedException extends RuntimeException implements ExceptionInterface {}
