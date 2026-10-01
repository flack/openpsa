<?php
/**
 * @package org.openpsa.expenses
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\controller;
use midcom\datamanager\datamanager;
use midcom\datamanager\schemadb;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Edit several objects with one datamanager form
 *
 * Fields where all objects have the same value are prefilled, the others are left
 * empty and keep their current values unless the user enters something. On save,
 * the changed fields are submitted to every object's own datamanager form, so that
 * the normal validation runs for each of them before anything is written.
 *
 * @package org.openpsa.expenses
 */
class org_openpsa_expenses_batchedit
{
    private datamanager $dm;

    /**
     * @var datamanager[]
     */
    private array $object_dms = [];

    /**
     * Field names whose values differ between the objects
     */
    private array $multivalue_fields = [];

    /**
     * Mixed boolean fields that are rendered as select in the batch form
     */
    private array $multivalue_booleans = [];

    /**
     * Rendered values of the batch form before submission
     */
    private array $initial_values = [];

    /**
     * Raw request data of the batch form
     */
    private array $submitted_values = [];

    private array $changed_values = [];

    private midcom_services_i18n_l10n $l10n;

    /**
     * @param midcom_core_dbaobject[] $objects
     */
    public function __construct(string $schemadb_path, array $objects)
    {
        if (empty($objects)) {
            throw new midcom_error('No objects to edit');
        }
        $this->l10n = midcom::get()->i18n->get_l10n('org.openpsa.expenses');

        $schemadb = schemadb::from_path($schemadb_path);
        foreach ($objects as $object) {
            $this->object_dms[] = (new datamanager($schemadb))->set_storage($object);
        }
        $this->build_form($schemadb_path);
    }

    public function get_controller() : controller
    {
        return $this->dm->get_controller();
    }

    /**
     * Writes the validated changes to the objects (to be used as save_callback in the datamanager workflow)
     */
    public function save()
    {
        if (empty($this->changed_values)) {
            return;
        }
        foreach ($this->object_dms as $dm) {
            $dm->get_storage()->save();
        }
    }

    private function build_form(string $schemadb_path)
    {
        $first = $this->object_dms[0];
        $schema_name = $first->get_schema()->get_name();

        $values = [];
        foreach ($this->object_dms as $dm) {
            $values[] = $this->get_view_values($dm->get_form()->createView());
        }

        $defaults = [];
        foreach (array_keys($first->get_schema()->get('fields')) as $name) {
            $first_value = $values[0][$name] ?? null;
            foreach ($values as $object_values) {
                if (($object_values[$name] ?? null) !== $first_value) {
                    $this->multivalue_fields[] = $name;
                    continue 2;
                }
            }
            $defaults[$name] = $first->get_storage()->$name;
        }

        // Use a separate schemadb instance, since we modify the field configs
        $this->dm = datamanager::from_schemadb($schemadb_path);
        $schema = $this->dm->get_schema($schema_name);
        foreach ($this->multivalue_fields as $name) {
            $field =& $schema->get_field($name);
            $field['required'] = false;
            $field['attr']['placeholder'] = $this->l10n->get('multiple values');
            if ($field['type'] === 'boolean') {
                // A checkbox can't express "leave unchanged", so we use a select instead
                $this->multivalue_booleans[] = $name;
                $field['type'] = 'select';
                $field['widget'] = 'select';
                $field['choices'] = [
                    $this->l10n->get('multiple values') => '',
                    midcom::get()->i18n->get_string('yes', 'midcom') => '1',
                    midcom::get()->i18n->get_string('no', 'midcom') => '0',
                ];
            }
        }
        $this->dm->set_defaults($defaults)->set_storage(null, $schema_name);

        $builder = $this->dm->get_builder();
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (PreSubmitEvent $event) {
            $this->submitted_values = (array) $event->getData();
        });
        // Run after the regular validation of the batch form
        $builder->addEventListener(FormEvents::POST_SUBMIT, $this->validate_objects(...), -10);
        $this->dm->build_form($builder);

        $this->initial_values = $this->get_view_values($this->dm->get_form()->createView());
    }

    private function validate_objects(PostSubmitEvent $event)
    {
        $form = $event->getForm();
        if (   !$form->isValid()
            || $form->getClickedButton()?->getConfig()->getOption('operation') !== controller::SAVE) {
            return;
        }

        $this->changed_values = [];
        foreach ($this->get_view_values($form->createView()) as $name => $value) {
            if ($value === $this->initial_values[$name]) {
                continue;
            }
            // unchecked checkboxes are missing from the request, so we default to null
            $this->changed_values[$name] = $this->submitted_values[$name] ?? null;
            if (in_array($name, $this->multivalue_booleans)) {
                $this->changed_values[$name] = ($this->changed_values[$name] === '1') ? '1' : null;
            }
        }

        if (empty($this->changed_values)) {
            return;
        }

        foreach ($this->object_dms as $dm) {
            $object = $dm->get_storage()->get_value();
            if ($dm->get_storage()->is_locked()) {
                $label = midcom_helper_reflector::get($object)->get_object_label($object);
                $form->addError(new FormError(sprintf($this->l10n->get('%s is locked'), $label)));
                continue;
            }
            $object_form = $dm->get_form();
            $object_form->submit(array_intersect_key($this->changed_values, $object_form->all()), false);
            $this->validate_unsubmitted($object_form);
            foreach ($object_form->getErrors(true) as $error) {
                $label = midcom_helper_reflector::get($object)->get_object_label($object);
                $target = $this->find_target($form, $error->getOrigin());
                $target->addError(new FormError($label . ': ' . $error->getMessage()));
            }
        }
    }

    /**
     * Symfony only validates the fields that were part of a partial submission,
     * so we check the remaining ones against their constraints ourselves
     */
    private function validate_unsubmitted(FormInterface $form)
    {
        $validator = midcom::get()->getContainer()->get('validator');
        foreach ($form as $child) {
            if (   $child->isSubmitted()
                || $child->isDisabled()
                || !$constraints = $child->getConfig()->getOption('constraints')) {
                continue;
            }
            foreach ($validator->validate($child->getData(), $constraints) as $violation) {
                $child->addError(new FormError((string) $violation->getMessage(), $violation->getMessageTemplate(), $violation->getParameters(), $violation->getPlural(), $violation));
            }
        }
    }

    /**
     * Find the batch form field corresponding to the object form field an error occurred in
     */
    private function find_target(FormInterface $form, ?FormInterface $origin) : FormInterface
    {
        while ($origin?->getParent()?->getParent()) {
            $origin = $origin->getParent();
        }
        if ($origin && $origin->getParent() && $form->has($origin->getName())) {
            return $form->get($origin->getName());
        }
        return $form;
    }

    /**
     * Collect the values as they would be displayed to the user, so that
     * e.g. timestamps on the same date count as equal in a date-only widget
     */
    private function get_view_values(FormView $view) : array
    {
        $values = [];
        foreach ($view->children as $name => $child) {
            if ($name === 'form_toolbar') {
                continue;
            }
            if (count($child->children) > 0) {
                $values[$name] = $this->get_view_values($child);
            } else {
                $values[$name] = $child->vars['checked'] ?? $child->vars['value'] ?? null;
            }
        }
        return $values;
    }
}
