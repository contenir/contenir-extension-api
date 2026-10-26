<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Form;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Exception\LinkRejectedException;
use Contenir\Extension\Api\Record\NewRecord;
use Contenir\Extension\Api\Url\RecordReference;

use function is_array;
use function is_string;
use function ltrim;
use function strpos;
use function substr;
use function trim;

/**
 * A link field's value: one of the site's records, an address as the editor
 * typed it, or a record to create, which the add-on makes with
 * RecordsInterface::create(), adding the parent it knows of.
 *
 * A link field posts an array under its name: "kind" (record, address or
 * new), then "record" as "<section>:<id>", "address", or "section", "title",
 * "path" and "workflow". A record, or a record to create, may carry a
 * "fragment": the spot on its page, such as "staff" for "#staff". An address
 * keeps any fragment in the address itself. Its default is a record or
 * address as encode() writes it.
 *
 * @api
 */
final readonly class Link
{
    /**
     * The spot on a record's page, without its leading "#", or null when there is none.
     */
    public ?string $fragment;

    private function __construct(
        public LinkKind $kind,
        public ?RecordReference $record = null,
        public ?string $address = null,
        public ?NewRecord $newRecord = null,
        ?string $fragment = null,
    ) {
        $fragment       = ltrim(trim((string) $fragment), characters: '#');
        $this->fragment = '' === $fragment ? null : $fragment;
    }

    /**
     * @throws LinkRejectedException When the address is empty.
     */
    public static function address(string $address): self
    {
        $address = trim($address);
        if ('' === $address) {
            throw LinkRejectedException::because('Type or paste the address to link to');
        }

        return new self(LinkKind::Address, address: $address);
    }

    /**
     * A record or address that encode() wrote, or null for anything else,
     * such as an empty default.
     */
    public static function decode(string $encoded): ?self
    {
        $colon = strpos($encoded, needle: ':');
        if (false === $colon) {
            return null;
        }

        $kind = substr($encoded, offset: 0, length: $colon);
        $rest = substr($encoded, $colon + 1);
        $hash = strpos($rest, needle: '#');
        [$choice, $fragment] = false === $hash
            ? [$rest, '']
            : [substr($rest, offset: 0, length: $hash), substr($rest, $hash)];

        try {
            return match ($kind) {
                LinkKind::Record->value => self::recordFrom($choice, $fragment),
                LinkKind::Address->value => self::address($rest),
                default => null,
            };
        } catch (LinkRejectedException) {
            return null;
        }
    }

    /**
     * The value read from what a link field posted, or null when the editor
     * chose nothing.
     *
     * @throws LinkRejectedException When the choice is incomplete.
     */
    public static function fromPosted(mixed $posted): ?self
    {
        if (! is_array($posted)) {
            return null;
        }

        return match (self::text($posted, 'kind')) {
            LinkKind::Record->value => self::recordFrom(self::text($posted, 'record'), self::text($posted, 'fragment')),
            LinkKind::Address->value => self::address(self::text($posted, 'address')),
            LinkKind::NewRecord->value => self::newRecordFrom($posted),
            default => null,
        };
    }

    /**
     * @param ?string $fragment The spot on the new record's page, with or without its leading "#"
     */
    public static function newRecord(NewRecord $record, ?string $fragment = null): self
    {
        return new self(LinkKind::NewRecord, newRecord: $record, fragment: $fragment);
    }

    /**
     * @param ?string $fragment The spot on the record's page, with or without its leading "#"
     */
    public static function record(RecordReference $record, ?string $fragment = null): self
    {
        return new self(LinkKind::Record, record: $record, fragment: $fragment);
    }

    /**
     * @param array<array-key, mixed> $posted
     *
     * @throws LinkRejectedException When the title or path is missing.
     */
    private static function newRecordFrom(array $posted): self
    {
        $workflow = self::text($posted, 'workflow');

        try {
            return self::newRecord(
                new NewRecord(
                    self::text($posted, 'section'),
                    self::text($posted, 'title'),
                    self::text($posted, 'path'),
                    '' === $workflow ? null : $workflow,
                ),
                self::text($posted, 'fragment'),
            );
        } catch (InvalidDeclarationException) {
            throw LinkRejectedException::because('Give the new page a title and an address');
        }
    }

    /**
     * @throws LinkRejectedException When $choice is not "<section>:<id>".
     */
    private static function recordFrom(string $choice, string $fragment = ''): self
    {
        $colon   = strpos($choice, needle: ':');
        $section = false === $colon ? '' : substr($choice, offset: 0, length: $colon);
        $id      = false === $colon ? '' : substr($choice, $colon + 1);
        if ('' === $section || '' === $id) {
            throw LinkRejectedException::because('Choose the page or record to link to');
        }

        return self::record(new RecordReference($section, $id), $fragment);
    }

    /**
     * @param array<array-key, mixed> $posted
     *
     * @mago-expect analysis:mixed-assignment The array is what the editor posted; the type is checked here.
     */
    private static function text(array $posted, string $key): string
    {
        $value = $posted[$key] ?? null;

        return is_string($value) ? trim($value) : '';
    }

    /**
     * The value as a link field's default: "record:<section>:<id>", followed
     * by "#<fragment>" when it has one, or "address:<address>". A record still
     * to create has none.
     */
    public function encode(): ?string
    {
        return match ($this->kind) {
            LinkKind::Record => "record:{$this->record?->section}:{$this->record?->id}"
                . (null === $this->fragment ? '' : "#{$this->fragment}"),
            LinkKind::Address   => "address:{$this->address}",
            LinkKind::NewRecord => null,
        };
    }
}
