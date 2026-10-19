<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Script;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;

/**
 * A script for public pages: loaded from a URL or written inline, where it
 * goes, and the consent it needs.
 *
 * @api
 */
final readonly class Script
{
    /**
     * @param array<string, string|bool> $attributes Extra attributes such as async or defer; true writes the bare name
     */
    private function __construct(
        public Placement $placement,
        public ConsentCategory $consent,
        public ?string $src,
        public ?string $inline,
        public array $attributes,
    ) {}

    /**
     * @param array<string, string|bool> $attributes
     *
     * @throws InvalidDeclarationException When $code is empty.
     */
    public static function inline(
        string $code,
        Placement $placement,
        ConsentCategory $consent,
        array $attributes = [],
    ): self {
        if ('' === $code) {
            throw InvalidDeclarationException::because('An inline script needs code');
        }

        return new self($placement, $consent, null, $code, $attributes);
    }

    /**
     * @param array<string, string|bool> $attributes
     *
     * @throws InvalidDeclarationException When $src is empty.
     */
    public static function src(
        string $src,
        Placement $placement,
        ConsentCategory $consent,
        array $attributes = [],
    ): self {
        if ('' === $src) {
            throw InvalidDeclarationException::because('A script needs a src');
        }

        return new self($placement, $consent, $src, null, $attributes);
    }
}
