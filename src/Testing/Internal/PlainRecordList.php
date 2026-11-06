<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Testing\Internal;

use Contenir\Extension\Api\Module\TemplateHelpersInterface;
use Contenir\Extension\Api\RecordList\RecordList;
use Contenir\Extension\Api\RecordList\RecordListPager;
use Contenir\Extension\Api\RecordList\RecordListRow;

use function implode;
use function intdiv;
use function sprintf;

/**
 * A record list as plain markup a test can read: rows with their boxes and links, the bulk actions and
 * the page links, or the empty message.
 *
 * @internal
 */
final readonly class PlainRecordList
{
    public function __construct(
        private TemplateHelpersInterface $view,
    ) {}

    public function of(RecordList $list, string $field): string
    {
        if ([] === $list->rows) {
            return sprintf(
                '<div class="empty"><h2>%s</h2><p>%s</p></div>',
                $this->view->escape($list->emptyTitle),
                $this->view->escape($list->emptyDescription),
            );
        }

        $heads = '';
        foreach ($list->headings as $heading) {
            $heads .= sprintf('<th>%s</th>', $this->view->escape($heading));
        }

        $rows = '';
        foreach ($list->rows as $row) {
            $rows .= $this->row($list, $row);
        }

        return sprintf(
            '%s<table><tr>%s</tr>%s</table>%s',
            $this->form($list, $field),
            $heads,
            $rows,
            $this->pages($list->pager),
        );
    }

    private function form(RecordList $list, string $field): string
    {
        if ([] === $list->bulk) {
            return '';
        }

        $bulk = '';
        foreach ($list->bulk as $action) {
            $bulk .= sprintf(
                '<button name="%s" value="%s">%s</button>',
                $this->view->escape($action->name),
                $this->view->escape($action->value),
                $this->view->escape($action->label),
            );
        }

        return sprintf('<form action="%s">%s%s</form>', $this->view->escape($list->bulkUrl), $field, $bulk);
    }

    private function pages(RecordListPager $pager): string
    {
        $pages = intdiv($pager->total + $pager->perPage - 1, $pager->perPage);
        if ($pages < 2) {
            return '';
        }

        $links = '';
        for ($page = 1; $page <= $pages; ++$page) {
            $links .= sprintf('<a href="%s">%d</a>', $this->view->escape(($pager->pageUrl)($page)), $page);
        }

        return $links;
    }

    private function row(RecordList $list, RecordListRow $row): string
    {
        $cells = [] === $list->bulk
            ? ''
            : sprintf(
                '<td><input type="checkbox" name="%s[]" value="%s" aria-label="Select %s"></td>',
                $this->view->escape($list->selectName),
                $this->view->escape($row->id),
                $this->view->escape($row->label),
            );
        foreach ($row->cells as $cell) {
            $text  = $this->view->escape(implode(' / ', $cell->lines));
            $cells .= null === $cell->href
                ? "<td>{$text}</td>"
                : sprintf('<td><a href="%s">%s</a></td>', $this->view->escape($cell->href), $text);
        }

        foreach ($row->actions as $action) {
            $cells .= sprintf(
                '<td><a href="%s">%s</a></td>',
                $this->view->escape($action->url),
                $this->view->escape($action->label),
            );
        }

        return "<tr>{$cells}</tr>";
    }
}
