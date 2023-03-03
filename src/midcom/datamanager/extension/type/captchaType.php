<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use midcom_connection;
use midcom_services_session;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Experimental captcha type
 */
class captchaType extends AbstractType
{
    private \midcom_services_i18n_l10n $l10n;

    public function __construct(\midcom_services_i18n $i18n)
    {
        $this->l10n = $i18n->get_l10n('midcom.datamanager');
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('mapped', false);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $session_key = md5($builder->getForm()->getName() . '_session_key');
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($session_key) {
            $value = $event->getForm()->getData();
            $session = new midcom_services_session('midcom_datamanager_captcha');

            if (   !$session->exists($session_key)
                || $value != $session->get($session_key)) {
                $event->getForm()->addError(new FormError($this->l10n->get('captcha validation failed')));
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $session_key = md5($form->getName() . '_session_key');
        $view->vars['captcha_url'] = midcom_connection::get_url('self') . 'midcom-exec-midcom.datamanager/captcha.php/' . $session_key;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'captcha';
    }
}
