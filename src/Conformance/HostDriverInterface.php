<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Conformance;

use Contenir\Extension\Api\Diagnostic\CheckResult;
use Contenir\Extension\Api\Event\ActionResult;
use Contenir\Extension\Api\Event\Extension\ExtensionPostFail;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\ExtensionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * What the conformance suite needs from a host to prove it implements the
 * API. Each host's test suite supplies one, built fresh for every test,
 * driving the host the way the CMS itself would: loading add-ons, saving
 * records, answering requests and running checks.
 *
 * @api
 */
interface HostDriverInterface
{
    /**
     * Binds a listener to a section, as a line in the section's admin.xml
     * <events> does. A null section binds it site-wide, for events that are
     * not about a section. $event is a core event's short name or an
     * add-on event's class.
     *
     * @param list<string> $fields Narrows the listener's scope
     *
     * @throws InvalidDeclarationException When the add-on declared no such listener, or the binding widens its scope.
     *
     * @mago-expect lint:excessive-parameter-list One parameter per attribute of an admin.xml <listen> line.
     */
    public function bind(
        ?string $section,
        string $handle,
        string $listener,
        string $event,
        array $fields = [],
        ?int $order = null,
    ): void;

    /**
     * Installs $installed, enables the handles in $enabled, and loads the
     * enabled add-ons as the host does on a request. $services are made
     * available to add-on code through its context, beside the host's own.
     *
     * @param array<string, ExtensionInterface> $installed By handle
     * @param list<string>                      $enabled
     * @param array<string, object>             $services  By id
     */
    public function boot(array $installed, array $enabled, array $services = []): void;

    /**
     * Runs one of an add-on's checks, as the update check does.
     */
    public function check(string $handle, string $name): CheckResult;

    /**
     * Every ExtensionPostFail the host fired, in order.
     *
     * @return list<ExtensionPostFail>
     */
    public function failures(): array;

    /**
     * Applies the fix a check offers, with the values entered in a guided
     * fix, then runs the check again. Null when $grants lacks the add-on's
     * fix permission, in which case nothing runs.
     *
     * @param array<string, mixed> $values
     * @param list<string>         $grants
     */
    public function fix(string $handle, string $name, array $values, array $grants): ?CheckResult;

    /**
     * Answers a request to an admin handler or a public webhook, for someone
     * holding the ACL resources in $grants.
     *
     * @param list<string> $grants
     */
    public function handle(ServerRequestInterface $request, array $grants = []): ResponseInterface;

    /**
     * The handles of the add-ons that loaded, in the order they loaded.
     *
     * @return list<string>
     */
    public function loaded(): array;

    /**
     * Every log entry, in order.
     *
     * @return list<array{level: string, message: string, context: array<array-key, mixed>}>
     */
    public function logs(): array;

    /**
     * Why each add-on that failed to load was skipped, by handle.
     *
     * @return array<string, string>
     */
    public function problems(): array;

    /**
     * Runs the job runner until no background work is left.
     */
    public function runBackground(): void;

    /**
     * Saves a record as an editor would, firing its events. A null id
     * creates one.
     *
     * @param array<string, mixed> $values The submitted values
     */
    public function save(string $section, string|int|null $id, array $values): ActionResult;

    /**
     * Stores a record as it already stands, without firing events.
     *
     * @param array<string, mixed> $values
     */
    public function seed(string $section, string|int $id, array $values): void;

    /**
     * A record's stored values, or null when there is no such record.
     *
     * @return array<string, mixed>|null
     */
    public function stored(string $section, string|int $id): ?array;
}
