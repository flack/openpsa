<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use midcom;

class phpValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (trim($value) == '') {
            return;
        }
        $tmpfile = tempnam(midcom::get()->config->get('midcom_tempdir'), 'midcom_datamanager_validator_php_');
        file_put_contents($tmpfile, $value);
        $return_status = 0;
        $parse_results = [];
        exec("php -l {$tmpfile} 2>&1", $parse_results, $return_status);
        unlink($tmpfile);

        debug_print_r("'php -l {$tmpfile}' returned:", $parse_results);

        if ($return_status !== 0) {
            $parse_result = array_pop($parse_results);
            if (strpos($parse_result, 'No syntax errors detected in ' . $tmpfile) !== false) {
                // We have an error, but it's most likely a false positive, e.g. a PHP startup error
                return;
            }
            $parse_result = array_pop($parse_results);
            $line = preg_replace('/^.+?on line (\d+).*?$/s', '\1', $parse_result);
            $l10n = midcom::get()->i18n->get_l10n('midcom.datamanager');

            $this->context
                ->buildViolation(sprintf($l10n->get('type php: parse error in line %s'), $line))
                ->addViolation();
        }
    }
}
