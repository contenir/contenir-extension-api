<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Handler;

use Contenir\Extension\Api\Handler\HandlerResult;
use Contenir\Extension\Api\Handler\ResultKind;
use Contenir\Extension\Api\Handler\Screen;
use Contenir\Extension\Api\Handler\Template;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class HandlerResultTest extends TestCase
{
    #[Test]
    public function addsMessagesKeepingEverythingElse(): void
    {
        $result = HandlerResult::json(['ok' => true], 202)
            ->withMessage('success', 'Saved')
            ->withMessage('info', 'Syncing');

        static::assertSame(
            [
                ResultKind::Json,
                ['ok' => true],
                202,
                [['level' => 'success', 'text' => 'Saved'], ['level' => 'info', 'text' => 'Syncing']],
            ],
            [$result->kind, $result->json, $result->status, $result->messages],
        );
    }

    #[Test]
    public function answersJsonWithOkByDefault(): void
    {
        static::assertSame(200, HandlerResult::json([])->status);
    }

    #[Test]
    public function answersWithAScreen(): void
    {
        $screen = new Screen('Lists');
        $result = HandlerResult::screen($screen);

        static::assertSame([ResultKind::Screen, $screen], [$result->kind, $result->screen]);
    }

    #[Test]
    public function answersWithATemplate(): void
    {
        $result = HandlerResult::template('fields', 'Fields', ['form' => 3]);

        static::assertEquals(
            [ResultKind::Template, new Template('fields', 'Fields', ['form' => 3])],
            [$result->kind, $result->template],
        );
    }

    #[Test]
    public function answersWithJson(): void
    {
        $result = HandlerResult::json(['lists' => ['main']], 201);

        static::assertSame([ResultKind::Json, ['lists' => ['main']], 201], [
            $result->kind,
            $result->json,
            $result->status,
        ]);
    }

    #[Test]
    public function goesBackToTheSettingsSection(): void
    {
        $result = HandlerResult::back();

        static::assertSame(
            [ResultKind::Redirect, null, null, 200, null, []],
            [$result->kind, $result->url, $result->json, $result->status, $result->screen, $result->messages],
        );
    }

    #[Test]
    public function keepsItsTemplateWithAMessage(): void
    {
        $result = HandlerResult::template('fields', 'Fields')->withMessage('success', 'Saved');

        static::assertEquals(new Template('fields', 'Fields'), $result->template);
    }

    #[Test]
    public function keepsTheUrlAndScreenWhenAddingAMessage(): void
    {
        $screen = new Screen('Lists');

        static::assertSame(
            ['https://example.com', $screen],
            [
                HandlerResult::redirect('https://example.com')->withMessage('info', 'Bye')->url,
                HandlerResult::screen($screen)->withMessage('info', 'Hi')->screen,
            ],
        );
    }

    #[Test]
    public function redirectsElsewhere(): void
    {
        $result = HandlerResult::redirect('https://login.example.com/authorize');

        static::assertSame([ResultKind::Redirect, 'https://login.example.com/authorize'], [
            $result->kind,
            $result->url,
        ]);
    }
}
