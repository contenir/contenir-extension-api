<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Handler\WebhookInterface;
use Contenir\Extension\Api\Handler\WebhookResponse;
use Override;
use Psr\Http\Message\ServerRequestInterface;

use function fwrite;
use function hash_equals;

/**
 * A public webhook that answers a request signed "valid" with a file to
 * download, as a signed link to an upload would.
 *
 * @api
 */
final class FileHook implements WebhookInterface
{
    public const string FILENAME = 'probe-file.txt';

    public const string CONTENT = 'probe file';

    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): WebhookResponse
    {
        return WebhookResponse::download(self::FILENAME, 'text/plain', static function ($stream): void {
            fwrite($stream, self::CONTENT);
        });
    }

    #[Override]
    public function verify(ServerRequestInterface $request, ExtensionContextInterface $context): bool
    {
        return hash_equals(Hook::SIGNATURE, $request->getHeaderLine(Hook::HEADER));
    }
}
