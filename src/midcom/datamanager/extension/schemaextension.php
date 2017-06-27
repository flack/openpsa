<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension;

use Symfony\Component\Form\AbstractExtension;

/**
 * Experimental extension class
 */
class schemaextension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    protected function loadTypes()
    {
        return [
            new type\autocomplete,
            new type\blobs,
            new type\codemirror,
            new type\images,
            new type\jsdate,
            new type\markdown,
            new type\photo,
            new type\radiocheckselect,
            new type\subform,
            new type\select,
            new type\tinymce,
            new type\toolbar,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTypeExtensions()
    {
        return [new formextension, new buttonextension];
    }
}
