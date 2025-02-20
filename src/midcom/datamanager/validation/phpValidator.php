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
        if ($error = self::lint($value)) {
            $this->context
                ->buildViolation($error)
                ->addViolation();
        }
    }

    public static function lint(string $input) : ?string
    {
        $return_status = 0;
        $parse_results = [];

        // Not all shells seem to support disabling escape characters, so
        // enable them everywhere with -e and mask them instead
        $input = str_replace(
            ['\a', '\b', '\c', '\e', '\f', '\n', '\r', '\t', '\v'],
            [ '\\\a', '\\\b', '\\\c', '\\\e', '\\\f', '\\\n', '\\\r', '\\\t', '\\\v'],
            $input
        );
        exec(sprintf('echo -e %s | php -l', escapeshellarg($input)) . " 2>&1", $parse_results, $return_status);
        debug_print_r("php -l returned:", $parse_results);

        if ($return_status !== 0) {
            $parse_result = array_pop($parse_results);
            if (str_contains($parse_result, 'No syntax errors detected in ')) {
                // We have an error, but it's most likely a false positive, e.g. a PHP startup error
                return null;
            }
            $parse_result = array_pop($parse_results);
            $line = preg_replace('/^.+?on line (\d+).*?$/s', '\1', $parse_result);
            $l10n = midcom::get()->i18n->get_l10n('midcom.datamanager');

            return sprintf($l10n->get('type php: parse error in line %s'), $line);
        }
        return null;
    }
}
