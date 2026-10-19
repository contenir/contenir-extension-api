<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Diagnostic;

use Contenir\Extension\Api\Diagnostic\CopyText;
use Contenir\Extension\Api\Diagnostic\FixResult;
use Contenir\Extension\Api\Diagnostic\GuidedFix;
use Contenir\Extension\Api\Diagnostic\OneClickFix;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Form\Field;
use Contenir\Extension\Api\Form\Form;
use Contenir\Extension\Api\Tests\TestAsset\Extension\ApiKeyCheck;
use Contenir\Extension\Api\Tests\TestAsset\Extension\StoreApiKey;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class FixTest extends TestCase
{
    #[Test]
    public function declaresAGuidedFixThatOnlyExplainsAndRechecks(): void
    {
        $fix = new GuidedFix('Set up', 'Add a cron entry.');

        static::assertSame([[], [], null], [$fix->copy, $fix->form->fields, $fix->handler]);
    }

    #[Test]
    public function declaresAGuidedFixWithTextToCopyAndFields(): void
    {
        $copy = [new CopyText('Cron line', '* * * * * php bin/contenir-queue')];
        $form = new Form([Field::password('api_key', 'API key')]);
        $fix  = new GuidedFix('Set up', 'Add this line to the crontab.', $copy, $form, StoreApiKey::class);

        static::assertSame(
            ['Set up', 'Add this line to the crontab.', $copy, $form, StoreApiKey::class],
            [$fix->label, $fix->explanation, $fix->copy, $fix->form, $fix->handler],
        );
    }

    #[Test]
    public function declaresAOneClickFix(): void
    {
        $fix = new OneClickFix('Reconnect', StoreApiKey::class);

        static::assertSame(['Reconnect', StoreApiKey::class], [$fix->label, $fix->handler]);
    }

    #[Test]
    public function rejectsAGuidedFixWhoseHandlerIsNotAFix(): void
    {
        $this->expectException(InvalidDeclarationException::class);

        new GuidedFix('Set up', 'Add a key.', handler: ApiKeyCheck::class);
    }

    #[Test]
    public function rejectsAGuidedFixWithoutALabel(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('A fix label must not be empty');

        new GuidedFix('', 'Add a cron entry.');
    }

    #[Test]
    public function rejectsAGuidedFixWithoutAnExplanation(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('A guided fix explanation must not be empty');

        new GuidedFix('Set up', '');
    }

    #[Test]
    public function rejectsAOneClickFixWhoseHandlerIsNotAFix(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('Fix class "' . ApiKeyCheck::class . '"');

        new OneClickFix('Reconnect', ApiKeyCheck::class);
    }

    #[Test]
    public function rejectsAOneClickFixWithoutALabel(): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage('A fix label must not be empty');

        new OneClickFix(' ', StoreApiKey::class);
    }

    #[Test]
    public function reportsAFailedFix(): void
    {
        $result = FixResult::failed('The key was refused');

        static::assertSame([false, 'The key was refused'], [$result->applied, $result->message]);
    }

    #[Test]
    public function reportsAnAppliedFix(): void
    {
        $result = FixResult::applied('Key saved');

        static::assertSame([true, 'Key saved'], [$result->applied, $result->message]);
    }

    #[Test]
    public function reportsAnAppliedFixWithNoMessageByDefault(): void
    {
        static::assertSame('', FixResult::applied()->message);
    }
}
