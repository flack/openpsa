<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager;

use midcom;
use midcom_connection;
use midcom_error;
use Symfony\Component\Form\Form;
use midcom\datamanager\storage\container\dbacontainer;

/**
 * Experimental controller class
 */
class controller
{
    const EDIT = 'edit';

    const SAVE = 'save';

    const CANCEL = 'cancel';

    const DELETE = 'delete';

    const PREVIEW = 'preview';

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
        // we add the stylesheet regardless of processing result, since save does not automatically mean relocate...
        midcom::get()->head->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.datamanager/default.css");
        $operation = self::EDIT;

        $storage = $this->dm->get_storage();
        if (!empty($_REQUEST['midcom_datamanager_unlock'])) {
            if (!$storage->unlock()) {
                $l10n = midcom::get()->i18n->get_l10n('midcom.datamanager');
                midcom::get()->uimessages->add($l10n->get('midcom.datamanager'), sprintf($l10n->get('failed to unlock, reason %s'), midcom_connection::get_error_string()), 'error');
            }
        } elseif (!$this->form->isSubmitted()) {
            $this->form->handleRequest();
            if (   $this->form->isSubmitted()
                && $button = $this->form->getClickedButton()) {
                $operation = $button->getConfig()->getOption('operation');
            }
        }

        if (   $operation == self::SAVE
            && !$this->form->isValid()) {
            $operation = self::EDIT;
        }

        if (in_array($operation, [self::CANCEL, self::SAVE])) {
            $storage->unlock();
            if ($operation == self::SAVE) {
                $storage->save();
            }
            return $operation;
        } else {
            $storage->lock();
        }

        if (in_array($operation, [self::DELETE, self::PREVIEW])) {
            return $operation;
        }

        return self::EDIT;
    }

    /**
     * @return \midcom\datamanager\datamanager
     */
    public function get_datamanager()
    {
        return $this->dm;
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
        $storage = $this->dm->get_storage();
        if ($storage->is_locked()) {
            midcom::get()->style->data['handler'] = $this;
            midcom::get()->style->show_midcom('midcom_datamanager_unlock');
        } else {
            $view = $this->form->createView();
            $renderer = $this->dm->get_renderer();
            $renderer->set_template($view, new template\form($renderer));
            echo $renderer->block($view, 'form');
        }
    }

    public function display_view()
    {
        $this->dm->display_view();
    }
}
