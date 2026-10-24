<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Form;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Exception\LinkRejectedException;
use Contenir\Extension\Api\Record\NewRecord;
use Contenir\Extension\Api\Url\RecordReference;

use function is_array;
use function is_string;
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
 * "path" and "workflow". Its default is a record or address as encode()
 * writes it.
 *
 * @api
 */
final readonly class Link
{
    private function __construct(
        public LinkKind $kind,
        public ?RecordReference $record = null,
        public ?string $address = null,
        public ?NewRecord $newRecord = null,
    ) {}

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

        try {
            return match ($kind) {
                LinkKind::Record->value => self::recordFrom($rest),
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
            LinkKind::Record->value => self::recordFrom(self::text($posted, 'record')),
            LinkKind::Address->value => self::address(self::text($posted, 'address')),
            LinkKind::NewRecord->value => self::newRecordFrom($posted),
            default => null,
        };
    }

    public static function newRecord(NewRecord $record): self
    {
        return new self(LinkKind::NewRecord, newRecord: $record);
    }

    public static function record(RecordReference $record): self
    {
        return new self(LinkKind::Record, record: $record);
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
            return self::newRecord(new NewRecord(
                self::text($posted, 'section'),
                self::text($posted, 'title'),
                self::text($posted, 'path'),
                '' === $workflow ? null : $workflow,
            ));
        } catch (InvalidDeclarationException) {
            throw LinkRejectedException::because('Give the new page a title and an address');
        }
    }

    /**
     * @throws LinkRejectedException When $choice is not "<section>:<id>".
     */
    private static function recordFrom(string $choice): self
    {
        $colon   = strpos($choice, needle: ':');
        $section = false === $colon ? '' : substr($choice, offset: 0, length: $colon);
        $id      = false === $colon ? '' : substr($choice, $colon + 1);
        if ('' === $section || '' === $id) {
            throw LinkRejectedException::because('Choose the page or record to link to');
        }

        return self::record(new RecordReference($section, $id));
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
     * The value as a link field's default: "record:<section>:<id>" or
     * "address:<address>". A record still to create has none.
     */
    public function encode(): ?string
    {
        return match ($this->kind) {
            LinkKind::Record    => "record:{$this->record?->section}:{$this->record?->id}",
            LinkKind::Address   => "address:{$this->address}",
            LinkKind::NewRecord => null,
        };
    }
}
