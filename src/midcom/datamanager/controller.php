<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager;

use midcom\datamanager\storage\container\container;
use midcom;
use midcom_error;
use Symfony\Component\Form\Form;

/**
 * Experimental controller class
 */
class controller
{
    const EDIT = 'edit';

    const SAVE = 'save';

    const CANCEL = 'cancel';

    /**
     *
     * @var Form
     */
    private $form;

    /**
     *
     * @var container
     */
    private $storage;

    /**
     *
     * @var renderer
     */
    private $renderer;

    public function __construct(Form $form, container $storage, renderer $renderer)
    {
        $this->form = $form;
        $this->storage = $storage;
        $this->renderer = $renderer;
    }

    public function process()
    {
        if (!$this->form->isSubmitted())
        {
            $this->form->handleRequest();
        }
        // we add the stylesheet regardless of processing result, since save does not automatically mean relocate...
        midcom::get()->head->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.datamanager/default.css");

        if ($this->form->isSubmitted())
        {
            $button = $this->form->getClickedButton();
            if (   $button
                && $button->getConfig()->getOption('operation') == self::CANCEL)
            {
                return self::CANCEL;
            }
            if ($this->form->isValid())
            {
                $this->storage->save();
                return self::SAVE;
            }
        }

        return self::EDIT;
    }

    /**
     *
     * @return array
     */
    public function get_errors()
    {
        $errors = array();
        foreach ($this->form as $child)
        {
            $messages = '';
            foreach ($child->getErrors(true) as $error)
            {
                $messages .= $error->getMessage();
            }
            if (!empty($messages))
            {
                $errors[$child->getName()] = $messages;
            }
        }
        return $errors;
    }

    public function get_form_values()
    {
        if (!$this->form->isSubmitted())
        {
            throw new midcom_error('form is not submitted');
        }
        return $this->form->getData();
    }

    public function display_form()
    {
        $view = $this->form->createView();
        $this->renderer->set_template($view, new template\form($this->renderer));
        echo $this->renderer->block($view, 'form');
    }

    public function display_view()
    {
        $view = $this->form->createView();
        $this->renderer->set_template(new template\view($this->renderer));
        echo $this->renderer->block($view, 'form');
    }
}
