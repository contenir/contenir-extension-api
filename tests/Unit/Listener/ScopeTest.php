<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Listener;

use Contenir\Extension\Api\Event\Cache\CachePostClear;
use Contenir\Extension\Api\Event\Change;
use Contenir\Extension\Api\Event\Record\RecordPostSave;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Listener\Scope;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ScopeTest extends TestCase
{
    #[Test]
    public function coversEveryChange(): void
    {
        static::assertNull(Scope::all()->fields);
    }

    #[Test]
    public function listsItsFieldsOnce(): void
    {
        static::assertSame(['email', 'name'], Scope::fields('email', 'name', 'email')->fields);
    }

    #[Test]
    public function matchesEveryEventWhenItCoversEverything(): void
    {
        static::assertSame(
            [true, true],
            [Scope::all()->matches(new CachePostClear('all')), Scope::all()->matches($this->saved(['title' => 'B']))],
        );
    }

    #[Test]
    public function matchesWhenOneOfItsFieldsChanged(): void
    {
        $scope = Scope::fields('email', 'newsletter');

        static::assertSame(
            [true, false],
            [
                $scope->matches($this->saved(['email' => 'b@example.com'])),
                $scope->matches($this->saved(['title' => 'B'])),
            ],
        );
    }

    #[Test]
    public function narrowsEveryChangeToAnyFields(): void
    {
        static::assertSame(['image'], Scope::all()->narrow('image')->fields);
    }

    #[Test]
    public function narrowsToAFewOfItsFields(): void
    {
        static::assertSame(['email'], Scope::fields('email', 'name')->narrow('email')->fields);
    }

    #[Test]
    public function neverMatchesAnEventWithoutValuesWhenItHasFields(): void
    {
        static::assertFalse(Scope::fields('email')->matches(new CachePostClear('all')));
    }

    #[Test]
    public function refusesToWiden(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Field "title" is outside the listener\'s scope, which a binding cannot widen');

        Scope::fields('email', 'name')->narrow('email', 'title');
    }

    #[Test]
    public function rejectsAMalformedField(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        Scope::fields('email', 'Name');
    }

    #[Test]
    public function rejectsAMalformedFirstField(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        Scope::fields('Email');
    }

    #[Test]
    public function staysAsItIsWhenABindingNamesNoFields(): void
    {
        $scope = Scope::fields('email');

        static::assertSame($scope, $scope->narrow());
    }

    /**
     * @param array<string, mixed> $after
     */
    private function saved(array $after): RecordPostSave
    {
        return new RecordPostSave(
            'member',
            1,
            new Change(['title' => 'A', 'email' => 'a@example.com'], [
                ...['title' => 'A', 'email' => 'a@example.com'],
                ...$after,
            ]),
        );
    }
}
