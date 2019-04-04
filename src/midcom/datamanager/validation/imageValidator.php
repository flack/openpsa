<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class imageValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (   empty($value)
            || !empty($value['delete'])
            || (   empty($value['file'])
                && (   empty($constraint->config['do_not_save_archival'] && empty($value['archival']))
                    xor empty($value['main'])))) {
            $this->context->buildViolation('This value should not be blank.')
                ->addViolation();
        }
    }
}
