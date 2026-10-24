<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance;

use Contenir\Extension\Api\Editor\Editor;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestFactoryInterface;

use function json_decode;

/**
 * The editor a host lends add-ons (Editor\CurrentEditorInterface): the one
 * signed in for the admin request being answered, and no one once it is
 * over or when no one signed in.
 *
 * @api
 */
abstract class AbstractEditorConformanceTestCase extends AbstractConformanceTestCase
{
    /**
     * What lets a request reach the probe's editor handler.
     */
    private const string HANDLER = 'extension.probe.editor';

    #[Test]
    public function aHandlerSeesNoOneWhenNoOneSignedIn(): void
    {
        static::assertNull($this->ask(null));
    }

    #[Test]
    public function aHandlerSeesTheEditorSignedInForItsRequest(): void
    {
        static::assertSame(['id' => 7, 'name' => 'Ann Editor'], $this->ask(new Editor(7, 'Ann Editor')));
    }

    #[Test]
    public function theEditorIsForgottenOnceTheirRequestIsOver(): void
    {
        $this->ask(new Editor(7, 'Ann Editor'));

        static::assertNull($this->ask(null));
    }

    /**
     * Builds the requests the tests send.
     */
    abstract protected function requests(): ServerRequestFactoryInterface;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->bootProbe();
    }

    /**
     * What the probe's editor handler answered for a request made by $editor.
     */
    private function ask(?Editor $editor): mixed
    {
        $response = $this->host->handle(
            $this->requests()->createServerRequest('GET', 'https://cms.example.com/admin/extension/probe/editor'),
            [self::HANDLER],
            $editor,
        );

        return json_decode((string) $response->getBody(), associative: true);
    }
}
