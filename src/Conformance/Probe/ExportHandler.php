<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Handler\HandlerInterface;
use Contenir\Extension\Api\Handler\HandlerResult;
use Override;
use Psr\Http\Message\ServerRequestInterface;

use function fwrite;

/**
 * An admin handler answering with a file to download, written in two
 * parts.
 *
 * @api
 */
final class ExportHandler implements HandlerInterface
{
    public const string FILENAME = 'probe-export.csv';

    public const string CONTENT = "name,count\nprobe,2\n";

    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): HandlerResult
    {
        Journal::from($context)->write('export');

        return HandlerResult::download(self::FILENAME, 'text/csv', static function ($stream): void {
            fwrite($stream, data: "name,count\n");
            fwrite($stream, data: "probe,2\n");
        });
    }
}
