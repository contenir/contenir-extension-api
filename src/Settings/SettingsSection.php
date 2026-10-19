<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Settings;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Form\Form;
use Contenir\Extension\Api\Internal\Names;
use Contenir\Extension\Api\Operation\Contribution;
use Contenir\Extension\Api\Operation\Operation;
use Contenir\Extension\Api\Operation\OperationHandlerInterface;
use Contenir\Extension\Api\Operation\RowHydratorInterface;
use Contenir\Extension\Api\Operation\SaveListenerInterface;

use function array_key_exists;

/**
 * A Site Settings section described as data, which replaces the admin.xml
 * shaped arrays today's plugins return from getConfig(). The host's generic
 * settings screens draw it, save it and run its operations.
 *
 * @api
 */
final readonly class SettingsSection
{
    /**
     * @param list<Operation>                           $operations    Buttons on the section's screens
     * @param list<Contribution>                        $contributions Text and links in the section's regions
     * @param list<class-string<SaveListenerInterface>> $saveListeners Run when the section is saved, in order
     * @param list<class-string<RowHydratorInterface>>  $rowHydrators  Supply transient field values when the section loads
     * @param ?int                                      $order         Position within the category; null appends it
     *
     *
     * @mago-expect lint:excessive-parameter-list Sections are declared with named arguments; all but four are optional.
     *
     * @throws InvalidDeclarationException When a name is malformed, two operations share a name, or a class is the wrong kind.
     */
    public function __construct(
        public string $name,
        public string $title,
        public SettingsCategory $category,
        public Form $form,
        public array $operations = [],
        public array $contributions = [],
        public array $saveListeners = [],
        public array $rowHydrators = [],
        public ?int $order = null,
    ) {
        Names::name('Settings section', $name);
        Names::text('A settings section title', $title);

        $names = [];
        foreach ($operations as $operation) {
            if (array_key_exists($operation->name, $names)) {
                throw InvalidDeclarationException::duplicate('operation', $operation->name);
            }
            $names[$operation->name] = $operation;
            Names::implementing('Operation handler', $operation->handler, OperationHandlerInterface::class);
        }

        foreach ($saveListeners as $listener) {
            Names::implementing('Save listener', $listener, SaveListenerInterface::class);
        }

        foreach ($rowHydrators as $hydrator) {
            Names::implementing('Row hydrator', $hydrator, RowHydratorInterface::class);
        }
    }
}
