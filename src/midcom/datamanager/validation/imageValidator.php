<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;

class imageValidator extends NotBlank
{
    public function validate($value, Constraint $constraint)
    {
        if ($constraint->required) {
            if (   !empty($value['delete'])
                || (   empty($value['file'])
                    && (   empty($constraint->config['do_not_save_archival'] && empty($value['archival']))
                        || empty($value['main'])))) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
            }
        }
    }
}
