<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance;

use Contenir\Extension\Api\Conformance\Probe\Module\PageHandler;
use Contenir\Extension\Api\Conformance\Probe\Module\ProbeModuleExtension;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

use function htmlspecialchars;
use function str_contains;
use function trim;

/**
 * Modules (plan section 10): a module's handlers answer with its templates,
 * drawn with values escaped, behind its area's or section's permission and
 * the privilege each declares.
 *
 * @api
 */
abstract class AbstractModuleConformanceTestCase extends AbstractConformanceTestCase
{
    #[Test]
    public function aHandlerNamedBySectionIsGuardedByThatSection(): void
    {
        $answers = [
            $this->host->handle($this->request('/admin/extension/probe-module/notes'), [ProbeModuleExtension::NOTES]),
            $this->host->handle($this->request('/admin/extension/probe-module/page'), [ProbeModuleExtension::NOTES]),
        ];

        static::assertSame([200, 403], [$answers[0]->getStatusCode(), $answers[1]->getStatusCode()]);
    }

    #[Test]
    public function aHandlerNeedsThePrivilegeItDeclaresWhateverTheMethod(): void
    {
        $answers = [
            $this->host->handle(
                $this->request('/admin/extension/probe-module/remove'),
                [ProbeModuleExtension::AREA . ':view', ProbeModuleExtension::AREA . ':update'],
            ),
            $this->host->handle(
                $this->request('/admin/extension/probe-module/remove'),
                [ProbeModuleExtension::AREA . ':delete'],
            ),
        ];

        static::assertSame([403, 200], [$answers[0]->getStatusCode(), $answers[1]->getStatusCode()]);
    }

    #[Test]
    public function aHandlerWithoutAPrivilegeNeedsViewToRead(): void
    {
        $answers = [
            $this->host->handle(
                $this->request('/admin/extension/probe-module/page'),
                [ProbeModuleExtension::AREA . ':update'],
            ),
            $this->host->handle(
                $this->request('/admin/extension/probe-module/page'),
                [ProbeModuleExtension::AREA . ':view'],
            ),
        ];

        static::assertSame([403, 200], [$answers[0]->getStatusCode(), $answers[1]->getStatusCode()]);
    }

    #[Test]
    public function aModuleHandlerAnswersWithAFragmentAloneWithItsStatus(): void
    {
        $response = $this->host->handle(
            $this->request('/admin/extension/probe-module/notes-part'),
            [ProbeModuleExtension::AREA],
        );

        static::assertSame(
            [201, "<ul class=\"probe-notes\">\n    <li>&lt;i&gt;One&lt;/i&gt;</li>\n</ul>"],
            [$response->getStatusCode(), trim((string) $response->getBody())],
        );
    }

    #[Test]
    public function aModuleHandlerAnswersWithItsTemplateValuesEscaped(): void
    {
        $body = (string) $this->host
            ->handle($this->request('/admin/extension/probe-module/page'), [ProbeModuleExtension::AREA])
            ->getBody();

        static::assertSame(
            [true, false, true],
            [
                str_contains($body, 'Hello ' . htmlspecialchars(PageHandler::NAME)),
                str_contains($body, PageHandler::NAME),
                str_contains($body, '<li>Fish &amp; chips</li>'),
            ],
        );
    }

    #[Test]
    public function aModuleHandlerIsGuardedByItsAreaNotItsOwnName(): void
    {
        $response = $this->host->handle(
            $this->request('/admin/extension/probe-module/page'),
            ['extension.probe-module.page'],
        );

        static::assertSame(403, $response->getStatusCode());
    }

    /**
     * Builds the requests the tests send.
     */
    abstract protected function requests(): ServerRequestFactoryInterface;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->host->boot(
            [ProbeModuleExtension::HANDLE => new ProbeModuleExtension()],
            [ProbeModuleExtension::HANDLE],
        );
    }

    private function request(string $path): ServerRequestInterface
    {
        return $this->requests()->createServerRequest('GET', "https://cms.example.com{$path}");
    }
}
