<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager;

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
     * @var datamanager
     */
    private $dm;

    public function __construct(datamanager $dm, $name = null)
    {
        $this->dm = $dm;
        $this->form = $dm->get_form($name);
    }

    public function process()
    {
        if (!$this->form->isSubmitted()) {
            $this->form->handleRequest();
        }
        // we add the stylesheet regardless of processing result, since save does not automatically mean relocate...
        midcom::get()->head->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.datamanager/default.css");

        if ($this->form->isSubmitted()) {
            $button = $this->form->getClickedButton();
            if (   $button
                && $button->getConfig()->getOption('operation') == self::CANCEL) {
                return self::CANCEL;
            }
            if ($this->form->isValid()) {
                $this->dm->get_storage()->save();
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
        $errors = [];
        foreach ($this->form as $child) {
            $messages = '';
            foreach ($child->getErrors(true) as $error) {
                $messages .= $error->getMessage();
            }
            if (!empty($messages)) {
                $errors[$child->getName()] = $messages;
            }
        }
        return $errors;
    }

    public function get_form_values()
    {
        if (!$this->form->isSubmitted()) {
            throw new midcom_error('form is not submitted');
        }
        return $this->form->getData();
    }

    public function display_form()
    {
        $view = $this->form->createView();
        $renderer = $this->dm->get_renderer();
        $renderer->set_template($view, new template\form($renderer));
        echo $renderer->block($view, 'form');
    }

    public function display_view()
    {
        $this->dm->display_view();
    }
}
