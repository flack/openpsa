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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

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

    const NEXT = 'next';

    const PREVIOUS = 'previous';

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

    public function __construct(datamanager $dm, string $name = null)
    {
        $this->dm = $dm;
        $this->form = $dm->get_form($name);
    }

    /**
     * Process the form
     *
     * @return string The processing result
     */
    public function handle(Request $request) : string
    {
        $operation = self::EDIT;

        $storage = $this->dm->get_storage();
        if ($request->request->has('midcom_datamanager_unlock')) {
            if (!$storage->unlock()) {
                $l10n = midcom::get()->i18n->get_l10n('midcom.datamanager');
                midcom::get()->uimessages->add($l10n->get('midcom.datamanager'), sprintf($l10n->get('failed to unlock, reason %s'), midcom_connection::get_error_string()), 'error');
            }
        } elseif (!$this->form->isSubmitted()) {
            $this->form->handleRequest($request);
            if (   $this->form->isSubmitted()
                && $button = $this->form->getClickedButton()) {
                $operation = $button->getConfig()->getOption('operation');
            }
        }

        if (   in_array($operation, [self::SAVE, self::NEXT])
            && !$this->form->isValid()) {
            $operation = self::EDIT;
        }
        if ($operation == self::SAVE) {
            $storage->save();
        }

        if (in_array($operation, [self::CANCEL, self::SAVE])) {
            $storage->unlock();
        } else {
            $storage->lock();
        }
        $storage->set_last_operation($operation);

        return $operation;
    }

    /**
     * Process the form (request object will be created on the fly)
     *
     * @deprecated Use handle() instead
     */
    public function process() : string
    {
        return $this->handle(Request::createFromGlobals());
    }

    public function get_datamanager() : datamanager
    {
        return $this->dm;
    }

    public function get_errors() : array
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
        $messages = '';
        foreach ($this->form->getErrors() as $error) {
            $messages .= $error->getMessage();
        }
        if (!empty($messages)) {
            $errors[] = $messages;
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
            if ($storage instanceof dbacontainer && $this->form->isSubmitted()) {
                // This is either a save without relocate or a validation error
                if ($storage->get_last_operation() === self::SAVE) {
                    // Get GUIDs and such for new-created dependent objects
                    $this->dm->set_storage($storage->get_value(), $this->dm->get_schema()->get_name());
                } elseif (   !$this->form->isValid()
                          && $storage->move_uploaded_files() > 0) {
                    $form = $this->dm->get_form($this->form->getName(), true);
                    $this->copy_errors($this->form, $form);
                }
            }

            $renderer = $this->dm->get_renderer('form');
            echo $renderer->block($renderer->get_view(), 'form');
        }
    }

    private function copy_errors(FormInterface $from, FormInterface $to)
    {
        foreach ($from->getErrors() as $error) {
            $to->addError($error);
        }

        foreach ($from->all() as $key => $child) {
            $this->copy_errors($child, $to->get($key));
        }
    }

    public function display_view()
    {
        $this->dm->display_view();
    }
}
