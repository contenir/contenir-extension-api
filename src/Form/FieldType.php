<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Form;

/**
 * The field types every host draws. An add-on may declare a field with one
 * of these, or with a field type it registers itself.
 *
 * @api
 */
enum FieldType: string
{
    case Checkbox      = 'checkbox';
    case Date          = 'date';
    case Datetime      = 'datetime';
    case Email         = 'email';
    case Filedocument  = 'filedocument';
    case Fileimage     = 'fileimage';
    case Hidden        = 'hidden';
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
