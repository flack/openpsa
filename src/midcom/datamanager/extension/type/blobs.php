<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use midcom\datamanager\extension\transformer\blobs as transformer;
use midcom\datamanager\extension\compat;

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
        $builder->add('title', compat::get_type_name('text'));
        $builder->add('file', compat::get_type_name('file'));
        $builder->add('identifier', compat::get_type_name('hidden'));
        $builder->addViewTransformer(new transformer($options));
    }

    /**
     * {@inheritdoc}
     *
     * Symfony < 2.8 compat
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'blobs';
    }
}
