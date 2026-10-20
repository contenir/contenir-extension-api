<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Handler;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Form\Form;
use Contenir\Extension\Api\Internal\Names;
use Contenir\Extension\Api\Operation\Operation;

/**
 * A screen described as data, which the host draws inside the dashboard
 * layout: a title and introduction, then a form, a table and operations,
 * each optional, and a row of tabs under the header leading to the other
 * screens about the same record.
 *
 * @api
 */
final readonly class Screen
{
    /**
     * @param list<Operation> $operations
     * @param list<Tab>       $tabs
     *
     * @throws InvalidDeclarationException When the title is empty.
     *
     * @mago-expect lint:excessive-parameter-list A screen is described with named arguments; all but the title are optional.
     */
    public function __construct(
        public string $title,
        public string $intro = '',
        public ?Form $form = null,
        public ?Table $table = null,
        public array $operations = [],
        public array $tabs = [],
    ) {
        Names::text('A screen title', $title);
    }
}
