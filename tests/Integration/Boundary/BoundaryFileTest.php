<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Integration\Boundary;

use Contenir\Extension\Api\Boundary\BoundaryException;
use Contenir\Extension\Api\Boundary\BoundaryFile;
use Contenir\Extension\Api\Tests\TestAsset\Boundary\ComposerFixtures;
use Contenir\Extension\Api\Tests\Trait\TemporaryFolderTrait;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function chmod;
use function file_get_contents;
use function file_put_contents;

#[Group('integration')]
final class BoundaryFileTest extends TestCase
{
    use TemporaryFolderTrait;

    #[Test]
    public function failsOnJsonThatDoesNotParse(): void
    {
        file_put_contents("{$this->folder}/composer.json", data: '{');

        $this->expectException(BoundaryException::class);
        $this->expectExceptionMessage("{$this->folder}/composer.json is not valid JSON: Syntax error");

        (new BoundaryFile($this->folder))->expected();
    }

    #[Test]
    public function failsOnJsonThatIsNotAnObject(): void
    {
        file_put_contents("{$this->folder}/composer.json", data: '"text"');

        $this->expectException(BoundaryException::class);
        $this->expectExceptionMessage("{$this->folder}/composer.json does not hold a JSON object");

        (new BoundaryFile($this->folder))->expected();
    }

    #[Test]
    public function failsWhenTheFolderCannotBeWritten(): void
    {
        chmod($this->folder, permissions: 0o555);

        $this->expectException(BoundaryException::class);
        $this->expectExceptionMessage("Could not write {$this->folder}/mago-boundary.toml");

        (new BoundaryFile($this->folder))->write();
    }

    #[Test]
    public function failsWithoutAComposerJson(): void
    {
        $this->expectException(BoundaryException::class);
        $this->expectExceptionMessage("Could not read {$this->folder}/missing/composer.json");

        (new BoundaryFile("{$this->folder}/missing"))->expected();
    }

    #[Test]
    public function holdsNothingBeforeItIsWritten(): void
    {
        $file = new BoundaryFile($this->folder);

        static::assertSame([null, false], [$file->current(), $file->isCurrent()]);
    }

    #[Test]
    public function isOutOfDateWhenTheRulesChange(): void
    {
        file_put_contents("{$this->folder}/mago-boundary.toml", data: "# old\n");

        static::assertFalse((new BoundaryFile($this->folder))->isCurrent());
    }

    #[Test]
    public function readsAnotherInstalledJson(): void
    {
        $this->writeJson('installed.json', ['packages' => []]);

        $this->expectException(BoundaryException::class);
        $this->expectExceptionMessage('php-db/phpdb is required but not installed');

        (new BoundaryFile($this->folder, "{$this->folder}/installed.json"))->expected();
    }

    #[Test]
    public function writesTheRulesTheFileShouldHold(): void
    {
        $file = new BoundaryFile($this->folder);
        $file->write();

        static::assertSame(
            [$file->expected(), true, "{$this->folder}/mago-boundary.toml"],
            [file_get_contents("{$this->folder}/mago-boundary.toml"), $file->isCurrent(), $file->path()],
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpFolder();
        $this->writeJson('composer.json', ComposerFixtures::addOn(['php-db/phpdb' => '^0.6']));
        $this->writeJson('vendor/composer/installed.json', ComposerFixtures::installed());
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->tearDownFolder();
    }
}
