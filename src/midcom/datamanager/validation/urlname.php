<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\validation;

use Symfony\Component\Validator\Constraint;

class urlname extends Constraint
{
    public $allow_unclean = false;
    public $allow_catenate = false;
    public $title_field = 'title';
    public $storage;
    public $property;
}
