<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\validation;

use Symfony\Component\Validator\Constraint;

class urlname extends Constraint
{
    public bool $allow_unclean = false;
    public bool $allow_catenate = false;
    public string $title_field = 'title';
    public $property;
}
