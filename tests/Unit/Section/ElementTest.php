<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Section;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Section\Element;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function libxml_get_errors;
use function libxml_use_internal_errors;

#[Group('unit')]
final class ElementTest extends TestCase
{
    /**
     * @return array<string, array{bool}>
     */
    public static function errorSettingProvider(): array
    {
        return [
            'errors kept internally' => [true],
            'errors reported'        => [false],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function malformedProvider(): array
    {
        return [
            'unclosed'   => ['<seo><title>meta_title</seo>'],
            'two roots'  => ['<seo/><seo/>'],
            'no element' => ['just text'],
            'empty'      => [''],
            'blank'      => ['  '],
        ];
    }

    #[Test]
    public function aChildIsTheFirstOfItsName(): void
    {
        $element = Element::parse('<seo><image>first</image><image>second</image></seo>');

        static::assertSame(['first', null], [$element->child('image')?->text, $element->child('title')]);
    }

    #[Test]
    public function anAttributeFallsBackToTheDefaultGiven(): void
    {
        $element = Element::parse('<description required="true">meta_description</description>');

        static::assertSame(
            ['true', null, 'no'],
            [$element->attribute('required'), $element->attribute('alt'), $element->attribute('alt', 'no')],
        );
    }

    #[Test]
    #[DataProvider('malformedProvider')]
    public function malformedXmlIsRefused(string $xml): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('A section block is not well-formed XML');

        Element::parse($xml);
    }

    #[Test]
    public function namedKeepsTheOrderWrittenAndSkipsOtherNames(): void
    {
        $element = Element::parse('<seo><title>a</title><image>b</image><title>c</title></seo>');

        static::assertSame(
            ['a', 'c'],
            [$element->named('title')[0]->text, $element->named('title')[1]->text],
        );
    }

    #[Test]
    public function needsAName(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('An element needs a name');

        new Element('');
    }

    #[Test]
    #[DataProvider('errorSettingProvider')]
    public function parsingLeavesNoXmlErrorsBehindAndRestoresTheErrorSetting(bool $setting): void
    {
        $previous = libxml_use_internal_errors($setting);
        $refused  = null;
        try {
            Element::parse('<seo><broken></seo>');
        } catch (InvalidDeclarationException $exception) {
            $refused = $exception;
        }

        static::assertSame(
            [true, [], $setting],
            [
                $refused instanceof InvalidDeclarationException,
                libxml_get_errors(),
                libxml_use_internal_errors($previous),
            ],
        );
    }

    #[Test]
    public function readsAnElementFromAdminXmlsDom(): void
    {
        $document = new DOMDocument();
        $document->loadXML('<admin><extensions><seo schema="WebPage"/></extensions></admin>');
        $seo = $document->getElementsByTagName('seo')->item(0);

        static::assertEquals(
            new Element('seo', ['schema' => 'WebPage']),
            $seo instanceof DOMElement ? Element::fromDom($seo) : null,
        );
    }

    #[Test]
    public function readsNamesAttributesTrimmedTextAndChildren(): void
    {
        static::assertEquals(
            new Element('seo', ['schema' => 'CreativeWork'], 'Lead', [
                new Element('title', [], 'meta_title, title'),
                new Element('image', ['alt' => 'title'], 'image_lg'),
            ]),
            Element::parse(
                '<seo schema="CreativeWork"> Lead <title> meta_title, title </title>'
                    . '<!-- a note --><image alt="title"><![CDATA[image_lg]]></image></seo>',
            ),
        );
    }

    #[Test]
    public function textJoinsTheTextOnEitherSideOfAChild(): void
    {
        static::assertSame('Before after', Element::parse('<note>Before <b>bold</b>after</note>')->text);
    }
}
