<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\AbstractType;

/**
 * Experimental markdown type
 */
class markdownType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix() : string
    {
        return 'markdown';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent() : ?string
    {
        return TextareaType::class;
    }
}
