<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance;

use Contenir\Extension\Api\Record\RecordStatus;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestFactoryInterface;

use function array_key_exists;
use function http_build_query;
use function json_decode;

/**
 * Overrides of section blocks (Section\SectionsInterface): an add-on puts a
 * block of its own in the place of the one admin.xml puts on a section, for
 * the environment, and the host gives it from then on and reads the columns
 * it names. An override stands in only for a block admin.xml puts there and
 * only under the add-on's name, reads only columns that are fields of the
 * section, is refused when the overrides have changed since they were read,
 * and goes when the block is restored.
 *
 * @api
 */
abstract class AbstractBlockOverridesConformanceTestCase extends AbstractConformanceTestCase
{
    private const string READS_TITLE = '<probe><column>title</column></probe>';

    private const string READS_TITLE_AND_EMAIL = '<probe><column>title</column><column>email</column></probe>';

    #[Test]
    public function anOverrideAgainstOverridesThatHaveChangedIsRefused(): void
    {
        $this->host->block(self::SECTION, '<probe/>');
        $read  = (string) $this->state('version');
        $first = $this->override('<probe schema="Product"/>', $read);
        $stale = $this->override('<probe schema="Event"/>', $read);

        static::assertSame(
            [false, true, ['schema' => 'Product']],
            [
                array_key_exists('refused', $first),
                array_key_exists('refused', $stale),
                $this->state('block'),
            ],
        );
    }

    #[Test]
    public function anOverrideNamedAfterAnotherAddOnIsRefused(): void
    {
        $this->host->block(self::SECTION, '<probe/>');

        static::assertSame(
            [true, false],
            [array_key_exists('refused', $this->override('<other/>')), $this->state('overridden')],
        );
    }

    #[Test]
    public function anOverrideOfASectionWithoutTheAddOnsBlockIsRefused(): void
    {
        $this->host->block(self::SECTION, '<other/>');

        static::assertSame(
            [true, null, false],
            [
                array_key_exists('refused', $this->override('<probe/>')),
                $this->state('block'),
                $this->state('overridden'),
            ],
        );
    }

    #[Test]
    public function anOverrideStandsInForTheBlockAndLeavesWhatAdminXmlSays(): void
    {
        $this->host->block(self::SECTION, '<probe schema="Article"/>');
        $this->override('<probe schema="Product"/>');

        static::assertSame(
            [['schema' => 'Product'], ['schema' => 'Article'], true],
            [$this->state('block'), $this->state('declared'), $this->state('overridden')],
        );
    }

    #[Test]
    public function aRestoreOfASectionWithNoOverrideChangesNothing(): void
    {
        $this->host->block(self::SECTION, '<probe schema="Article"/>');
        $restored = $this->ask('overrides', ['do' => 'restore', 'section' => self::SECTION]);

        static::assertSame(
            [false, ['schema' => 'Article']],
            [array_key_exists('refused', $restored), $this->state('block')],
        );
    }

    #[Test]
    public function aRestoreReturnsTheBlockToWhatAdminXmlSays(): void
    {
        $this->host->block(self::SECTION, '<probe schema="Article"/>');
        $this->override('<probe schema="Product"/>');
        $restored = $this->ask('overrides', ['do' => 'restore', 'section' => self::SECTION]);

        static::assertSame(
            [false, ['schema' => 'Article'], false],
            [array_key_exists('refused', $restored), $this->state('block'), $this->state('overridden')],
        );
    }

    #[Test]
    public function rowsReadAColumnOnlyTheOverrideNames(): void
    {
        $this->host->block(self::SECTION, self::READS_TITLE, ['title', 'email']);
        $this->override(self::READS_TITLE_AND_EMAIL);

        static::assertSame(
            [
                [
                    'record' => self::SECTION . ':2',
                    'status' => 'draft',
                    'values' => ['email' => 'b@example.com'],
                ],
            ],
            $this->rows('email'),
        );
    }

    #[Test]
    public function rowsRefuseAColumnOfAnOverrideThatIsNotAFieldOfTheSection(): void
    {
        $this->host->block(self::SECTION, self::READS_TITLE, ['title']);
        $this->override(self::READS_TITLE_AND_EMAIL);

        static::assertSame(
            [true, false],
            [array_key_exists('refused', $this->rows('email')), array_key_exists('refused', $this->rows('title'))],
        );
    }

    /**
     * Builds the requests the tests send.
     */
    abstract protected function requests(): ServerRequestFactoryInterface;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->bootProbe();
        $this->host->seed(self::SECTION, 2, [
            'title'  => 'Second',
            'email'  => 'b@example.com',
            'status' => RecordStatus::Draft->value,
        ]);
    }

    /**
     * What the probe's handler called $handler answered, decoded.
     *
     * @param array<string, string> $query
     *
     * @return array<array-key, mixed>
     */
    private function ask(string $handler, array $query): array
    {
        $response = $this->host->handle(
            $this->requests()
                ->createServerRequest(
                    'GET',
                    "https://cms.example.com/admin/extension/probe/{$handler}?" . http_build_query($query),
                )
                ->withQueryParams($query),
            ["extension.probe.{$handler}"],
        );

        /** @var array<array-key, mixed>|null $answer */
        $answer = json_decode((string) $response->getBody(), associative: true);

        return $answer ?? [];
    }

    /**
     * What the probe was answered when it overrode its block on the article section with $xml, against
     * $version or the overrides as they are.
     *
     * @return array<array-key, mixed>
     */
    private function override(string $xml, ?string $version = null): array
    {
        return $this->ask('overrides', [
            'do'      => 'override',
            'section' => self::SECTION,
            'xml'     => $xml,
            ...(null === $version ? [] : ['version' => $version]),
        ]);
    }

    /**
     * What a read of $column of the article section's second record gave.
     *
     * @return array<array-key, mixed>
     */
    private function rows(string $column): array
    {
        return $this->ask('sections', ['do' => 'rows', 'section' => self::SECTION, 'columns' => $column, 'ids' => '2']);
    }

    /**
     * One part of what the probe says of its block on the article section: "overridden", "version",
     * "block" or "declared".
     */
    private function state(string $part): mixed
    {
        return $this->ask('overrides', ['do' => 'state', 'section' => self::SECTION])[$part] ?? null;
    }
}
