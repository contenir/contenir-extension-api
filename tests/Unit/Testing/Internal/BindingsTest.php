<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Testing\Internal;

use Contenir\Extension\Api\Event\Record\RecordPreSave;
use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Listener\ListenerDeclaration;
use Contenir\Extension\Api\Listener\Scope;
use Contenir\Extension\Api\Testing\Internal\Bindings;
use Contenir\Extension\Api\Testing\Internal\BoundListener;
use Contenir\Extension\Api\Tests\TestAsset\Extension\SyncSubscriber;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;

#[Group('unit')]
final class BindingsTest extends TestCase
{
    private const string SECTION = 'article';

    private const string EVENT = RecordPreSave::class;

    private Bindings $bindings;

    private static function bound(string $handle, string $name): BoundListener
    {
        return new BoundListener(
            new Handle($handle),
            ListenerDeclaration::of($name, self::EVENT, SyncSubscriber::class),
            Scope::all(),
        );
    }

    #[Test]
    public function aLineTheSiteWritesKeepsTheOthersItWrote(): void
    {
        $this->bindings->add(self::SECTION, self::EVENT, self::bound('seo', 'check'), null);
        $this->bindings->add(self::SECTION, self::EVENT, self::bound('seo', 'check'), null);

        static::assertSame(['seo/check', 'seo/check'], $this->names());
    }

    #[Test]
    public function aLineTheSiteWritesReplacesOnlyTheSameListenersImpliedLine(): void
    {
        $this->bindings->imply(self::SECTION, self::EVENT, self::bound('seo', 'check'));
        $this->bindings->imply(self::SECTION, self::EVENT, self::bound('seo', 'tidy'));
        $this->bindings->imply(self::SECTION, self::EVENT, self::bound('audit', 'check'));
        $this->bindings->add(self::SECTION, self::EVENT, self::bound('seo', 'check'), null);

        static::assertSame(['seo/tidy', 'audit/check', 'seo/check'], $this->names());
    }

    #[Test]
    public function anImpliedLineComesAfterTheLinesBeforeIt(): void
    {
        $this->bindings->add(self::SECTION, self::EVENT, self::bound('seo', 'first'), null);
        $this->bindings->imply(self::SECTION, self::EVENT, self::bound('seo', 'implied'));
        $this->bindings->add(self::SECTION, self::EVENT, self::bound('seo', 'before'), 19);
        $this->bindings->add(self::SECTION, self::EVENT, self::bound('seo', 'between'), 25);

        static::assertSame(['seo/first', 'seo/before', 'seo/implied', 'seo/between'], $this->names());
    }

    #[Test]
    public function anImpliedLineIsDroppedWhereTheSiteBindsTheListener(): void
    {
        $this->bindings->add(self::SECTION, self::EVENT, self::bound('seo', 'check'), null);
        $this->bindings->imply(self::SECTION, self::EVENT, self::bound('seo', 'check'));
        $this->bindings->imply(self::SECTION, self::EVENT, self::bound('seo', 'tidy'));

        static::assertSame(['seo/check', 'seo/tidy'], $this->names());
    }

    #[Test]
    public function anImpliedLineKeepsToItsSectionAndEvent(): void
    {
        $this->bindings->imply('member', self::EVENT, self::bound('seo', 'check'));

        static::assertSame([[], ['seo/check']], [$this->names(), $this->names('member')]);
    }

    #[Test]
    public function anImpliedLineTakesTheFirstPositionInAnEmptySection(): void
    {
        $this->bindings->imply(self::SECTION, self::EVENT, self::bound('seo', 'implied'));
        $this->bindings->add(self::SECTION, self::EVENT, self::bound('seo', 'first'), 5);

        static::assertSame(['seo/first', 'seo/implied'], $this->names());
    }

    #[Override]
    protected function setUp(): void
    {
        $this->bindings = new Bindings();
    }

    /**
     * @return list<string>
     */
    private function names(string $section = self::SECTION): array
    {
        return array_map(
            static fn(BoundListener $listener): string => "{$listener->handle->value}/{$listener->declaration->name}",
            $this->bindings->for($section, self::EVENT),
        );
    }
}
