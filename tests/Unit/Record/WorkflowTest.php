<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Record;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Record\Workflow;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class WorkflowTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string}>
     */
    public static function emptyProvider(): array
    {
        return [
            'no id'    => ['', 'Standard page', 'A workflow id must not be empty'],
            'no title' => ['standard', '', 'A workflow title must not be empty'],
        ];
    }

    #[Test]
    public function namesATemplateByItsIdAndTitle(): void
    {
        $workflow = new Workflow('standard', 'Standard page');

        static::assertSame(['standard', 'Standard page'], [$workflow->id, $workflow->title]);
    }

    #[Test]
    #[DataProvider('emptyProvider')]
    public function needsAnIdAndATitle(string $id, string $title, string $message): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage($message);

        new Workflow($id, $title);
    }
}
