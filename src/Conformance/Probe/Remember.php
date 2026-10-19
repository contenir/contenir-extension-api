<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance\Probe;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Exception\UnknownSettingException;
use Contenir\Extension\Api\Handler\HandlerInterface;
use Contenir\Extension\Api\Handler\HandlerResult;
use Override;
use Psr\Http\Message\ServerRequestInterface;

use function is_string;

/**
 * Saves the "note" query parameter to the probe's settings, or the field
 * the "field" parameter names, then answers with what the settings hold.
 *
 * @api
 */
final class Remember implements HandlerInterface
{
    #[Override]
    public function handle(ServerRequestInterface $request, ExtensionContextInterface $context): HandlerResult
    {
        $query    = $request->getQueryParams();
        $field    = is_string($query['field'] ?? null) ? $query['field'] : 'note';
        $settings = $context->settings();

        try {
            $result = $settings->set([$field => $query['note'] ?? '']);
        } catch (UnknownSettingException $exception) {
            return HandlerResult::json(['error' => $exception->getMessage()], 422);
        }

        return HandlerResult::json([
            'outcome' => $result->outcome->value,
            'note'    => $settings->get('note'),
            'all'     => $settings->all(),
        ]);
    }
}
