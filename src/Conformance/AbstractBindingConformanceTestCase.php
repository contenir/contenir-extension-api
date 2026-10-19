<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Override;
use PHPUnit\Framework\Attributes\Test;

/**
 * Where listeners run, which is the site's choice (plan section 8, E1): only
 * on the sections they are bound to, only for changes in their scope, which
 * a binding may narrow but not widen, and in the order the site gives.
 *
 * @api
 */
abstract class AbstractBindingConformanceTestCase extends AbstractConformanceTestCase
{
    #[Test]
    public function aBindingCanNarrowAListenersScope(): void
    {
        $this->bindProbe('record', 'RecordPreSave', fields: ['email']);
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame([], $this->journal->entries());
    }

    #[Test]
    public function aBindingCannotWidenAListenersScope(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        $this->bindProbe('email-only', 'RecordPreSave', fields: ['title']);
    }

    #[Test]
    public function aBindingToAListenerTheAddOnLacksIsRefused(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        $this->bindProbe('no-such-listener', 'RecordPreSave');
    }

    #[Test]
    public function aBindingToAnEventTheListenerDoesNotHearIsRefused(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        $this->bindProbe('change-value', 'RecordPostSave');
    }

    #[Test]
    public function aFieldScopedListenerRunsWhenOneOfItsFieldsChanges(): void
    {
        $this->bindProbe('email-only', 'RecordPreSave');
        $this->host->save(self::SECTION, 1, ['email' => 'b@example.com']);

        static::assertSame(['RecordPreSave'], $this->journal->entries());
    }

    #[Test]
    public function aFieldScopedListenerStaysQuietWhenOtherFieldsChange(): void
    {
        $this->bindProbe('email-only', 'RecordPreSave');
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame([], $this->journal->entries());
    }

    #[Test]
    public function aListenerOutsideItsScopeDoesNotHoldUpTheNext(): void
    {
        $this->bindProbe('email-only', 'RecordPreSave');
        $this->bindProbe('record', 'RecordPreSave');
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame(['RecordPreSave'], $this->journal->entries());
    }

    #[Test]
    public function aListenerRunsOnlyOnTheSectionItIsBoundTo(): void
    {
        $this->bindProbe('record', 'RecordPreSave', 'member');
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame([], $this->journal->entries());
    }

    #[Test]
    public function anExplicitOrderSlotsAListenerInBetween(): void
    {
        $this->bindProbe('order-a', 'RecordPreSave');
        $this->bindProbe('order-b', 'RecordPreSave');
        $this->bindProbe('order-c', 'RecordPreSave', order: 15);
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame(['a', 'c', 'b'], $this->journal->entries());
    }

    #[Test]
    public function anOrderJustBelowALinesPositionRunsBeforeIt(): void
    {
        $this->bindProbe('order-a', 'RecordPreSave');
        $this->bindProbe('order-b', 'RecordPreSave');
        $this->bindProbe('order-c', 'RecordPreSave', order: 19);
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame(['a', 'c', 'b'], $this->journal->entries());
    }

    #[Test]
    public function anUnboundListenerNeverRuns(): void
    {
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame([], $this->journal->entries());
    }

    #[Test]
    public function listenersRunInTheOrderTheyAreBound(): void
    {
        $this->bindProbe('order-b', 'RecordPreSave');
        $this->bindProbe('order-a', 'RecordPreSave');
        $this->bindProbe('order-c', 'RecordPreSave');
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame(['b', 'a', 'c'], $this->journal->entries());
    }

    #[Test]
    public function listenersSharingAPositionKeepTheOrderWritten(): void
    {
        $this->bindProbe('order-a', 'RecordPreSave');
        $this->bindProbe('order-b', 'RecordPreSave', order: 10);
        $this->host->save(self::SECTION, 1, ['title' => 'New title']);

        static::assertSame(['a', 'b'], $this->journal->entries());
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->bootProbe();
        $this->host->seed(self::SECTION, 1, ['title' => 'Old title', 'email' => 'a@example.com']);
    }
}
