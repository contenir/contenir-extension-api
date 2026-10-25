<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Form;

/**
 * The field types every host draws. An add-on may declare a field with one
 * of these, or with a field type it registers itself.
 *
 * @api
 *
 * @mago-expect lint:too-many-enum-cases One case per control every host draws.
 */
enum FieldType: string
{
    case Checkbox     = 'checkbox';
    case Date         = 'date';
    case Datetime     = 'datetime';
    case Email        = 'email';
    case Filedocument = 'filedocument';
    case Fileimage    = 'fileimage';
    case Hidden       = 'hidden';

    /**
     * A short name for addresses and templates, such as a slug or a handle,
     * filled from other fields as the editor types them until the editor
     * types in it. Hints: "data-identifier" (the names of the fields it
     * follows, separated by spaces) and "pattern" (the characters it allows,
     * as an HTML pattern, so the host fills it with nothing the add-on would
     * refuse). The add-on still checks what is posted.
     */
    case Identifier = 'identifier';

    /**
     * A link to one of the site's linkable records or to an address, chosen
     * in the host's picker, which can also start a new record. Its default is
     * a Link's encode(); it posts as Link::fromPosted() reads. Hints: "create"
     * (true offers Create new) and "prefix" (the path a new record's suggested
     * path starts with, such as /about).
     */
    case Link          = 'link';
    case Multicheckbox = 'multicheckbox';
    case Multilink     = 'multilink';
    case Multiselect   = 'multiselect';
    case Number        = 'number';
    case Password      = 'password';
    case Radio         = 'radio';
    case Richtext      = 'richtext';
    case Select        = 'select';
    case Tel           = 'tel';
    case Text          = 'text';
    case Textarea      = 'textarea';
    case Time          = 'time';
    case Url           = 'url';
}
