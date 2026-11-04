<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Section;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use DOMDocument;
use DOMElement;

use function array_filter;
use function array_values;
use function in_array;
use function libxml_clear_errors;
use function libxml_use_internal_errors;
use function sprintf;
use function trim;

use const XML_CDATA_SECTION_NODE;
use const XML_TEXT_NODE;

/**
 * One element of a section block as admin.xml holds it: its name, its
 * attributes, the text directly inside it, trimmed, and the elements inside
 * it, in the order they are written. The host reads the block and never
 * interprets it; what the names mean is up to the add-on.
 *
 * @api
 */
final readonly class Element
{
    /**
     * @param array<string, string> $attributes
     * @param list<Element>         $children
     *
     * @throws InvalidDeclarationException When the name is empty.
     */
    public function __construct(
        public string $name,
        public array $attributes = [],
        public string $text = '',
        public array $children = [],
    ) {
        if ('' === $name) {
            throw InvalidDeclarationException::because('An element needs a name');
        }
    }

    /**
     * An element as admin.xml's DOM holds it.
     *
     * @throws InvalidDeclarationException When the element has no name.
     */
    public static function fromDom(DOMElement $element): self
    {
        $attributes = [];
        foreach ($element->attributes ?? [] as $attribute) {
            $attributes[$attribute->nodeName] = $attribute->nodeValue ?? '';
        }

        $text     = '';
        $children = [];
        foreach ($element->childNodes as $node) {
            if ($node instanceof DOMElement) {
                $children[] = self::fromDom($node);

                continue;
            }

            if (in_array($node->nodeType, [XML_TEXT_NODE, XML_CDATA_SECTION_NODE], strict: true)) {
                $text .= $node->nodeValue ?? '';
            }
        }

        return new self($element->localName ?? $element->nodeName, $attributes, trim($text), $children);
    }

    /**
     * An element written as XML, such as a test's block.
     *
     * @throws InvalidDeclarationException When $xml is not one well-formed element.
     */
    public static function parse(string $xml): self
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded   = '' !== $xml && $document->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded || ! $document->documentElement instanceof DOMElement) {
            throw InvalidDeclarationException::because(sprintf('A section block is not well-formed XML: %s', $xml));
        }

        return self::fromDom($document->documentElement);
    }

    /**
     * An attribute's value, or $default when the element has none by that name.
     */
    public function attribute(string $name, ?string $default = null): ?string
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * The first element inside this one called $name, or null.
     */
    public function child(string $name): ?self
    {
        return $this->named($name)[0] ?? null;
    }

    /**
     * Every element inside this one called $name, in the order written.
     *
     * @return list<Element>
     */
    public function named(string $name): array
    {
        return array_values(array_filter($this->children, static fn(Element $child): bool => $name === $child->name));
    }
}
