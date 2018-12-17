<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\ImageValidator as base;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\NotBlank;

class imageValidator extends base
{
    public function validate($value, Constraint $constraint)
    {
        if (!empty($value['file'])) {
            $upload = new UploadedFile($value['file']->location, $value['file']->name);
            parent::validate($upload, $constraint);
        }

        if ($constraint->required) {
            if (   !empty($value['delete'])
                || (   empty($value['file'])
                    && (   empty($constraint->config['do_not_save_archival'] && empty($value['archival']))
                        || empty($value['main'])))) {
                $msg_constraint = new NotBlank();
                $this->context->buildViolation($msg_constraint->message)
                    ->addViolation();
            }
        }
    }
}
