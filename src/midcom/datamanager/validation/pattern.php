<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\validation;

use Symfony\Component\Validator\Constraint;

class pattern extends Constraint
{
    public array $forbidden_patterns = [];
}
