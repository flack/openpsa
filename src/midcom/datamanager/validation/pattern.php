<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\validation;

use Symfony\Component\Validator\Constraint;

class pattern extends Constraint
{
    /**
     * @var array
     */
    public $forbidden_patterns = [];
}
