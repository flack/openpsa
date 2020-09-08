<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class laterthanValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $element = $this->context->getRoot()->get($constraint->value);
        $compare = $element->getData();
        if ($compare !== null && $value <= $compare) {
            $label = $element->getConfig()->getOption('label');
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ compared_value }}', $label)
                ->addViolation();
        }
    }
}
