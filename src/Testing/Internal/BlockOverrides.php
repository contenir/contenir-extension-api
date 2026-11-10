<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Exception\BlockNotSavedException;
use Contenir\Extension\Api\Section\Element;

use function array_intersect;
use function array_values;
use function sprintf;

/**
 * The overrides each add-on keeps of its own section blocks, as a host keeps
 * them beside the site's data and apart from admin.xml, with a version for
 * each add-on that moves whenever its overrides change.
 *
 * @internal
 */
final class BlockOverrides
{
    /**
     * @var array<string, array<string, Element>> By add-on handle, then section
     */
    private array $elements = [];

    /**
     * @var array<string, int> By add-on handle
     */
    private array $revisions = [];

    /**
     * Drops $handle's override on $section, where it has one.
     *
     * @param string $version What version() gave when the add-on read the block
     *
     * @throws BlockNotSavedException When the overrides have changed since $version.
     */
    public function drop(string $handle, string $section, string $version): void
    {
        $this->unchanged($handle, $version);
        if (null === ($this->elements[$handle][$section] ?? null)) {
            return;
        }

        unset($this->elements[$handle][$section]);
        $this->moved($handle);
    }

    /**
     * $declared, the elements admin.xml gives $handle by section, each with $handle's override in its
     * place where the section has one. An override of a section admin.xml gives it nothing on is left out.
     *
     * @param array<string, Element> $declared
     *
     * @return array<string, Element>
     */
    public function effective(string $handle, array $declared): array
    {
        $found = [];
        foreach ($declared as $section => $element) {
            $found[$section] = $this->elements[$handle][$section] ?? $element;
        }

        return $found;
    }

    /**
     * Keeps $element as $handle's override on $section, replacing one it had.
     *
     * @param string                 $version  What version() gave when the add-on read the block
     * @param array<string, Element> $declared The elements admin.xml gives $handle, by section
     *
     * @throws BlockNotSavedException When the overrides have changed since $version, admin.xml gives $handle nothing on $section, or $element is not named $handle.
     */
    public function keep(string $handle, string $section, Element $element, string $version, array $declared): void
    {
        $this->unchanged($handle, $version);
        if (null === ($declared[$section] ?? null)) {
            throw BlockNotSavedException::refused(sprintf(
                'Section %s has no block of add-on "%s" to override',
                $section,
                $handle,
            ));
        }

        if ($element->name !== $handle) {
            throw BlockNotSavedException::refused(sprintf(
                'A block on %s is named %s, not %s',
                $section,
                $handle,
                $element->name,
            ));
        }

        $this->elements[$handle][$section] = $element;
        $this->moved($handle);
    }

    /**
     * The columns of $reads the host lends $handle's block on $section: all of them, or only those among
     * the section's $fields once the block is an override.
     *
     * @param list<string> $reads
     * @param list<string> $fields
     *
     * @return list<string>
     */
    public function lent(string $handle, string $section, array $reads, array $fields): array
    {
        return $this->overrides($handle, $section) ? array_values(array_intersect($reads, $fields)) : $reads;
    }

    /**
     * Whether $handle keeps an override on $section.
     */
    public function overrides(string $handle, string $section): bool
    {
        return null !== ($this->elements[$handle][$section] ?? null);
    }

    /**
     * How many times $handle's overrides have changed, as text, which stands
     * for their version.
     */
    public function version(string $handle): string
    {
        return (string) ($this->revisions[$handle] ?? 0);
    }

    private function moved(string $handle): void
    {
        $this->revisions[$handle] = ($this->revisions[$handle] ?? 0) + 1;
    }

    /**
     * @throws BlockNotSavedException When $handle's overrides have changed since $version.
     */
    private function unchanged(string $handle, string $version): void
    {
        if ($version !== $this->version($handle)) {
            throw BlockNotSavedException::overridesChanged();
        }
    }
}
