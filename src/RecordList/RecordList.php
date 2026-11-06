<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\RecordList;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Internal\Names;

use function count;
use function sprintf;

/**
 * A list of records the host draws as it draws its own, so an add-on's list matches the Library's: the
 * table, each row's box and actions, Select all, what can be done to the ticked rows, and pagination,
 * or a message when there are no rows. A template prints it with
 * {@see \Contenir\Extension\Api\Module\TemplateHelpersInterface::recordList()} and keeps its own header
 * and filters around it. Rows have boxes only where the list has bulk actions. A page holds one list.
 *
 * @api
 */
final readonly class RecordList
{
    /**
     * @param list<string> $headings One for each cell of a row
     * @param list<RecordListRow> $rows The records on this page
     * @param list<RecordListAction> $bulk What can be done to the ticked rows
     * @param string $bulkUrl Where the ticked rows post to, as the host adds the form's token
     * @param array<string, string> $bulkValues Other values the post carries, such as the filters
     * @param string $selectName The name the ticked rows' ids post under, as a list
     *
     * @throws InvalidDeclarationException When a heading or the empty message is empty, a row's cells don't
     *     match the headings, or bulk actions have nowhere to post.
     *
     * @mago-expect lint:excessive-parameter-list A list is built with named arguments; most are optional.
     */
    public function __construct(
        public array $headings,
        public array $rows,
        public RecordListPager $pager,
        public string $emptyTitle = 'No records',
        public string $emptyDescription = 'Nothing here matches. Try other filters.',
        public array $bulk = [],
        public string $bulkUrl = '',
        public array $bulkValues = [],
        public string $selectName = 'records',
    ) {
        foreach ($headings as $heading) {
            Names::text('A record list heading', $heading);
        }

        Names::text('A record list\'s empty title', $emptyTitle);
        Names::text('A record list\'s bulk field', $selectName);

        foreach ($rows as $row) {
            if (count($row->cells) !== count($headings)) {
                throw InvalidDeclarationException::because(sprintf(
                    'Record list row "%s" has %d cells for %d headings',
                    $row->id,
                    count($row->cells),
                    count($headings),
                ));
            }
        }

        if ([] !== $bulk) {
            Names::text('A record list\'s bulk URL', $bulkUrl);
        }
    }
}
