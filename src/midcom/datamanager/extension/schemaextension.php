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
        return array
        (
            new type\attachment,
            new type\autocomplete,
            new type\codemirror,
            new type\downloads,
            new type\jsdate,
            new type\photo,
            new type\radiocheckselect,
            new type\select,
        	new type\tinymce,
            new type\toolbar,
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTypeExtensions()
    {
        return array(new formextension, new buttonextension);
    }
}