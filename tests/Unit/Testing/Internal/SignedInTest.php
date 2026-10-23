<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Testing\Internal;

use Contenir\Extension\Api\Editor\Editor;
use Contenir\Extension\Api\Handler\Privilege;
use Contenir\Extension\Api\Testing\Internal\SignedIn;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SignedInTest extends TestCase
{
    /**
     * @return array<string, array{list<string>, bool}>
     */
    public static function grantProvider(): array
    {
        return [
            'the whole resource'            => [['page'], true],
            'the privilege on the resource' => [['page:add'], true],
            'another privilege'             => [['page:update'], false],
            'another resource'              => [['news', 'news:add'], false],
            'nothing'                       => [[], false],
        ];
    }

    #[Test]
    public function mayAnythingOutsideARequest(): void
    {
        $editor = new SignedIn();
        $editor->signIn([], null);
        $editor->signOut();

        static::assertTrue((new SignedIn())->may('page', Privilege::Delete) && $editor->may('page', Privilege::Add));
    }

    #[Test]
    #[DataProvider('grantProvider')]
    public function mayWhatTheRequestsGrantsHold(array $grants, bool $may): void
    {
        $editor = new SignedIn();
        $editor->signIn($grants, null);

        static::assertSame($may, $editor->may('page', Privilege::Add));
    }

    #[Test]
    public function namesTheEditorSignedInUntilTheyAreSignedOut(): void
    {
        $signedIn = new SignedIn();
        $editor   = new Editor(7, 'Ann Editor');
        $signedIn->signIn([], $editor);
        $during = $signedIn->current();
        $signedIn->signOut();

        static::assertSame([$editor, null], [$during, $signedIn->current()]);
    }
}
