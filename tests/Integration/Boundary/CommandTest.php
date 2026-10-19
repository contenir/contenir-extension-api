<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Integration\Boundary;

use Contenir\Extension\Api\Boundary\Command;
use Contenir\Extension\Api\Tests\TestAsset\Boundary\ComposerFixtures;
use Contenir\Extension\Api\Tests\Trait\TemporaryFolderTrait;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_put_contents;
use function is_file;

#[Group('integration')]
final class CommandTest extends TestCase
{
    use TemporaryFolderTrait;

    /**
     * @var list<string>
     */
    private array $out = [];

    /**
     * @var list<string>
     */
    private array $err = [];

    #[Test]
    public function listsWhatBreaksTheBoundary(): void
    {
        $this->writeJson('composer.json', ComposerFixtures::addOn(['mezzio/mezzio' => '*', 'acme/missing' => '*']));
        $code = $this->command()->run($this->folder, ['--check']);

        static::assertSame(
            [
                Command::BROKEN,
                [
                    'An add-on may not require mezzio/mezzio',
                    'acme/missing is required but not installed: run composer install first',
                ],
            ],
            [$code, $this->err],
        );
    }

    #[Test]
    public function saysTheFileIsCurrent(): void
    {
        $this->command()->run($this->folder, []);
        $code = $this->command()->run($this->folder, ['--check']);

        static::assertSame(
            [Command::OK, ['Wrote mago-boundary.toml', 'mago-boundary.toml is current']],
            [$code, $this->out],
        );
    }

    #[Test]
    public function saysTheFileIsOutOfDateWithoutChangingIt(): void
    {
        file_put_contents("{$this->folder}/mago-boundary.toml", data: "# old\n");
        $code = $this->command()->run($this->folder, ['--check']);

        static::assertSame(
            [Command::STALE, ['mago-boundary.toml is out of date: run vendor/bin/extension-boundary']],
            [$code, $this->err],
        );
    }

    #[Test]
    public function writesTheBoundaryFile(): void
    {
        $code = $this->command()->run($this->folder, []);

        static::assertSame(
            [Command::OK, ['Wrote mago-boundary.toml'], [], true],
            [$code, $this->out, $this->err, is_file("{$this->folder}/mago-boundary.toml")],
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpFolder();
        $this->writeJson('composer.json', ComposerFixtures::addOn());
        $this->writeJson('vendor/composer/installed.json', ComposerFixtures::installed());
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->tearDownFolder();
    }

    private function command(): Command
    {
        return new Command(
            function (string $line): void {
                $this->out[] = $line;
            },
            function (string $line): void {
                $this->err[] = $line;
            },
        );
    }
}
