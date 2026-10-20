<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Testing\Internal;

use Contenir\Extension\Api\Form\Field;
use Contenir\Extension\Api\Form\Form;
use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Settings\SettingsCategory;
use Contenir\Extension\Api\Settings\SettingsSection;
use Contenir\Extension\Api\Testing\FrozenClock;
use Contenir\Extension\Api\Testing\InMemoryContext;
use Contenir\Extension\Api\Testing\Internal\OperationContext;
use Contenir\Extension\Api\Testing\Internal\SectionView;
use Contenir\Extension\Api\Testing\MapUrlResolver;
use Contenir\Extension\Api\Testing\MemoryLogger;
use Contenir\Extension\Api\Testing\ServiceMap;
use DateTimeImmutable;
use LogicException;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

#[Group('unit')]
final class OperationContextTest extends TestCase
{
    #[Test]
    public function describesTheSection(): void
    {
        $view = $this->operation([])->section();

        static::assertSame(
            ['newsletter', 'integration', 'settings.integration.newsletter', true, false],
            [$view->id(), $view->category(), $view->resourceId(), $view->hasField('list_id'), $view->hasField('nope')],
        );
    }

    #[Test]
    public function givesTheAddOnsContextServicesAndSite(): void
    {
        $extension = $this->extension();
        $context   = new OperationContext([], $this->view(), $extension);

        static::assertSame(
            [$extension, $extension->logger(), $extension->services(), '/site'],
            [$context->extension(), $context->logger(), $context->services(), $context->sitePath()],
        );
    }

    #[Test]
    public function givesThePostedValues(): void
    {
        $context = $this->operation(['value' => 'blue']);

        static::assertSame(
            [['value' => 'blue'], true, 'blue', 'blue', 'none', 'none'],
            [
                $context->allPost(),
                $context->isPost(),
                $context->post('value'),
                $context->param('value'),
                $context->post('other', 'none'),
                $context->param('other', 'none'),
            ],
        );
    }

    private function extension(): InMemoryContext
    {
        $handle = new Handle('newsletter');

        return new InMemoryContext(
            $handle,
            new ServiceMap(),
            new MemoryLogger(),
            new FrozenClock(new DateTimeImmutable('2026-10-21 09:00')),
            '/site',
            new MapUrlResolver(),
            new class implements EventDispatcherInterface {
                #[Override]
                public function dispatch(object $event): object
                {
                    return $event;
                }
            },
            static fn(string $section): never => throw new LogicException("No settings for {$section}"),
        );
    }

    /**
     * @param array<string, mixed> $posted
     */
    private function operation(array $posted): OperationContext
    {
        return new OperationContext($posted, $this->view(), $this->extension());
    }

    private function view(): SectionView
    {
        return new SectionView(new SettingsSection(
            name: 'newsletter',
            title: 'Newsletter',
            category: SettingsCategory::Integration,
            form: new Form([new Field('list_id', 'List')]),
        ));
    }
}
