<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class photoValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (   empty($value['archival'])
            && empty($value['file'])) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
