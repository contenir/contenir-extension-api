<?php

declare(strict_types=1);

namespace Contenir\Extension\Api;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;

use function in_array;
use function preg_match;
use function sprintf;
use function str_replace;

/**
 * An add-on's stable id, from extra.contenir.handle in its composer.json. It
 * names the add-on's settings section, config files, tables, permissions and
 * log entries.
 *
 * @api
 */
final readonly class Handle
{
    private const string PATTERN = '/^[a-z][a-z0-9-]{1,39}$/D';

    /**
     * Handles no add-on may take: "api" would put its namespace,
     * Contenir\Extension\Api\, on top of this package's.
     */
    private const array RESERVED = ['api'];

    /**
     * @throws InvalidDeclarationException When the handle is malformed or reserved.
     */
    public function __construct(
        public string $value,
    ) {
        if (1 !== preg_match(self::PATTERN, $value)) {
            throw InvalidDeclarationException::invalidName(
                'Handle',
                $value,
                '2 to 40 lowercase letters, digits and dashes, starting with a letter',
            );
        }

        if (in_array($value, self::RESERVED, strict: true)) {
            throw InvalidDeclarationException::because(sprintf('Handle "%s" is reserved', $value));
        }
    }

    /**
     * The type sections use for one of the add-on's field types:
     * <handle>:<name>.
     */
    public function fieldType(string $name): string
    {
        return "{$this->value}:{$name}";
    }

    /**
     * The ACL resource needed to apply the add-on's diagnostic fixes:
     * extension.<handle>.fix.
     */
    public function fixPermission(): string
    {
        return $this->permission(Registry::FIX);
    }

    /**
     * The name the add-on's log entries carry as their source:
     * extension.<handle>.
     */
    public function logSource(): string
    {
        return "extension.{$this->value}";
    }

    /**
     * The ACL resource guarding one of the add-on's handlers:
     * extension.<handle>.<name>.
     */
    public function permission(string $name): string
    {
        return "{$this->logSource()}.{$name}";
    }

    /**
     * The prefix every table the add-on creates must carry: ext_<handle>_,
     * with dashes in the handle written as underscores.
     */
    public function tablePrefix(): string
    {
        return (
            'ext_'
                . str_replace(
                    search: '-',
                    replace: '_',
                    subject: $this->value,
                )
                . '_'
        );
    }
}
