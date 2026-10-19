<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Boundary;

use JsonException;

use function file_get_contents;
use function file_put_contents;
use function is_array;
use function is_file;
use function is_writable;
use function json_decode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * An add-on's mago-boundary.toml: what it should hold, from the add-on's
 * composer.json and Composer's installed.json, and what it holds.
 *
 * @api
 */
final readonly class BoundaryFile
{
    public const string NAME = 'mago-boundary.toml';

    /**
     * @param string      $root      The add-on's root folder, holding composer.json
     * @param string|null $installed Composer's installed.json; vendor/composer/installed.json under $root by default
     */
    public function __construct(
        private string $root,
        private ?string $installed = null,
    ) {}

    /**
     * @return array<array-key, mixed>
     *
     * @throws BoundaryException
     *
     * @mago-expect analysis:mixed-assignment JSON is untyped; the shape is checked below.
     */
    private static function json(string $path): array
    {
        $contents = is_file($path) ? file_get_contents($path) : false;
        if (false === $contents) {
            throw new BoundaryException([sprintf('Could not read %s', $path)]);
        }

        try {
            $data = json_decode($contents, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new BoundaryException([sprintf('%s is not valid JSON: %s', $path, $exception->getMessage())]);
        }

        if (! is_array($data)) {
            throw new BoundaryException([sprintf('%s does not hold a JSON object', $path)]);
        }

        return $data;
    }

    /**
     * What the file holds, or null when there is none.
     */
    public function current(): ?string
    {
        $contents = is_file($this->path()) ? file_get_contents($this->path()) : false;

        return false === $contents ? null : $contents;
    }

    /**
     * The rules the file should hold.
     *
     * @throws BoundaryException When composer.json or installed.json can't be read, or the add-on breaks the boundary.
     */
    public function expected(): string
    {
        $boundary = Boundary::forAddOn(
            self::json("{$this->root}/composer.json"),
            self::json($this->installed ?? "{$this->root}/vendor/composer/installed.json"),
        );

        return (new GuardRules($boundary))->toToml();
    }

    /**
     * Whether the file holds the rules it should.
     *
     * @throws BoundaryException
     */
    public function isCurrent(): bool
    {
        return $this->expected() === $this->current();
    }

    public function path(): string
    {
        return "{$this->root}/" . self::NAME;
    }

    /**
     * Writes the rules the file should hold.
     *
     * @throws BoundaryException
     */
    public function write(): void
    {
        $expected = $this->expected();
        if (! is_writable($this->root)) {
            throw new BoundaryException([sprintf('Could not write %s', $this->path())]);
        }

        file_put_contents($this->path(), $expected);
    }
}
