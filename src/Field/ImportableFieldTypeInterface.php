<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Field;

use Contenir\Extension\Api\Exception\CellRejectedException;

/**
 * A field type a host's CSV import can fill. Without it, a column naming a
 * field of the type is left out of an import, with the reason given.
 *
 * @api
 */
interface ImportableFieldTypeInterface extends FieldTypeInterface
{
    /**
     * The value a CSV cell gives the field, as an editor would submit it.
     * The host trims the cell first, and validates and normalises the value
     * afterwards as it does any submitted value, so this only reads what a
     * person would type in a spreadsheet.
     *
     * @throws CellRejectedException When the cell can't become a value; the host shows its message against the cell.
     */
    public function importCell(string $cell): string;
}
