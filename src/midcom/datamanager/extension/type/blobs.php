<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use midcom\datamanager\extension\transformer\blobs as transformer;

/**
 * Experimental attachment type
 */
class blobs extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text');
        $builder->add('file', 'file');
        $builder->add('identifier', 'hidden');
        $builder->addViewTransformer(new transformer($options));
    }

    public function getName()
    {
        return 'blobs';
    }
}