<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Section;

/**
 * What an add-on that reads section blocks tells the host about each one:
 * the columns it reads, which are all SectionsInterface::rows() will return,
 * and the listeners it binds to the section. The host asks once per block
 * when it loads the site's admin.xml, and never interprets the block itself.
 *
 * A block whose meaning is wrong, such as a column the section lacks, is the
 * add-on's to report from its own checks. Both methods should answer what
 * they can rather than throw.
 *
 * @api
 */
interface SectionBlockInterface
{
    /**
     * @return list<string>
     */
    public function columns(SectionBlock $block): array;

    /**
     * @return list<ImpliedListener>
     */
    public function listeners(SectionBlock $block): array;
}
