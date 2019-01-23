<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use midcom;
use midcom_helper_reflector_nameresolver;
use midcom_helper_misc;
use midcom\datamanager\storage\container\dbacontainer;

class urlnameValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (empty($value)) {
            return;
        }

        $l10n = midcom::get()->i18n->get_l10n('midcom.datamanager');

        $data = $this->context->getRoot()->getData();
        if (!$data instanceof dbacontainer) {
            throw new \midcom_error('invalid storage, can only validate DBA objects');
        }

        $copy = clone $data->get_value();
        $property = $constraint->property['location'];
        $copy->{$property} = $value;
        $resolver = new midcom_helper_reflector_nameresolver($copy);

        $message = null;
        if (!$resolver->name_is_safe($property)) {
            $message = sprintf($l10n->get('type urlname: name is not "URL-safe", try "%s"'), midcom_helper_misc::urlize($value));
        } elseif (!$constraint->allow_unclean && !$resolver->name_is_clean($property)) {
            $message = sprintf($l10n->get('type urlname: name is not "clean", try "%s"'), midcom_helper_misc::urlize($value));
        } elseif (!$constraint->allow_catenate && !$resolver->name_is_unique()) {
            $message = sprintf($l10n->get('type urlname: name is already taken, try "%s"'), $resolver->generate_unique_name());
        }

        if (!empty($message)) {
            $this->context->buildViolation($message)
                ->addViolation();
        }
    }
}
