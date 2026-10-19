<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Boundary;

use Closure;

use function in_array;
use function sprintf;

/**
 * vendor/bin/extension-boundary: writes an add-on's mago-boundary.toml from
 * its composer.json, or with --check says whether the file is current.
 *
 * Exits 0 when it wrote the file or the file is current, 1 when --check
 * finds it out of date, and 2 when the add-on breaks the boundary.
 *
 * @api
 */
final readonly class Command
{
    public const int OK = 0;

    public const int STALE = 1;

    public const int BROKEN = 2;

    /**
     * @param Closure(string): void $out
     * @param Closure(string): void $err
     */
    public function __construct(
        private Closure $out,
        private Closure $err,
    ) {}

    /**
     * @param list<string> $arguments
     */
    public function run(string $root, array $arguments): int
    {
        $file = new BoundaryFile($root);
        try {
            if (in_array('--check', $arguments, strict: true)) {
                return $this->check($file);
            }

            $file->write();
        } catch (BoundaryException $exception) {
            foreach ($exception->problems as $problem) {
                ($this->err)($problem);
            }

            return self::BROKEN;
        }

        ($this->out)(sprintf('Wrote %s', BoundaryFile::NAME));

        return self::OK;
    }

    /**
     * @throws BoundaryException
     */
    private function check(BoundaryFile $file): int
    {
        if ($file->isCurrent()) {
            ($this->out)(sprintf('%s is current', BoundaryFile::NAME));

            return self::OK;
        }

        ($this->err)(sprintf('%s is out of date: run vendor/bin/extension-boundary', BoundaryFile::NAME));

        return self::STALE;
    }
}
