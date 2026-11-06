<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\RecordList;

use Closure;
use Contenir\Extension\Api\Exception\InvalidDeclarationException;

/**
 * Which page of how many records a {@see RecordList} shows, and where each page is.
 *
 * @api
 */
final readonly class RecordListPager
{
    /**
     * @param Closure(int): string $pageUrl A page's URL, by its number
     *
     * @throws InvalidDeclarationException When the page or page size isn't positive, or the total is negative.
     */
    public function __construct(
        public int $page,
        public int $perPage,
        public int $total,
        public Closure $pageUrl,
    ) {
        if ($page < 1 || $perPage < 1 || $total < 0) {
            throw InvalidDeclarationException::because('A record list pager needs a page and size of 1 or more');
        }
    }
}
