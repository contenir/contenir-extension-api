<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Testing\Internal;

use Contenir\Extension\Api\Handler\Privilege;
use Contenir\Extension\Api\Testing\Internal\Editor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class EditorTest extends TestCase
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
        $editor = new Editor();
        $editor->signIn([]);
        $editor->signOut();

        static::assertTrue((new Editor())->may('page', Privilege::Delete) && $editor->may('page', Privilege::Add));
    }

    #[Test]
    #[DataProvider('grantProvider')]
    public function mayWhatTheRequestsGrantsHold(array $grants, bool $may): void
    {
        $editor = new Editor();
        $editor->signIn($grants);

        static::assertSame($may, $editor->may('page', Privilege::Add));
    }
}
