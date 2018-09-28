<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use midcom;

class patternValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $l10n = midcom::get()->i18n->get_l10n('midcom.datamanager');

        foreach ($constraint->forbidden_patterns as $condition) {
            if (!isset($condition['explanation'])) {
                $condition['explanation'] = '';
            }

            switch ($condition['type']) {
                case 'text':
                    $pos = strpos($value, $condition['pattern']);
                    if ($pos !== false) {
                        $offense = substr($value, $pos, strlen($condition['pattern']));
                        $message = sprintf($l10n->get('type text: value contains an expression that is not allowed: "%s". %s'), htmlentities($offense), $condition['explanation']);
                    }
                    break;
                case 'regex':
                    $matches = [];
                    if (preg_match($condition['pattern'], $value, $matches)) {
                        $message = sprintf($l10n->get('type text: value contains an expression that is not allowed: "%s". %s'), htmlentities($matches[0]), $condition['explanation']);
                    }
                    break;
                default:
                    // We do not know how to handle this
                    $message = "Unsupported pattern type '{$condition['type']}'";
                    debug_add($message, MIDCOM_LOG_WARN);
                    break;
            }

            if (!empty($message)) {
                $this->context
                    ->buildViolation($message)
                    ->addViolation();
                break;
            }
        }
    }
}
