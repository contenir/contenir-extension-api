<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Diagnostic;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Form\Form;
use Contenir\Extension\Api\Internal\Names;

/**
 * A fix that needs a person: a dialog the host draws with an explanation,
 * text to copy and fields to fill. Submitting it runs the handler with the
 * values entered, then the check again; "Check again" re-runs the check
 * without changing anything. A fix with no handler only explains and
 * re-checks, for steps done outside the CMS such as adding a cron entry.
 *
 * @api
 */
final readonly class GuidedFix
{
    /**
     * @param list<CopyText>                  $copy
     * @param class-string<FixInterface>|null $handler
     *
     * @throws InvalidDeclarationException When the label or explanation is empty, or the handler is not a fix.
     */
    public function __construct(
        public string $label,
        public string $explanation,
        public array $copy = [],
        public Form $form = new Form(),
        public ?string $handler = null,
    ) {
        Names::text('A fix label', $label);
        Names::text('A guided fix explanation', $explanation);
        if (null !== $handler) {
            Names::implementing('Fix', $handler, FixInterface::class);
        }
    }
}
