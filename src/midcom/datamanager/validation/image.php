<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\validation;

use Symfony\Component\Validator\Constraints\Image as base;

class image extends base
{
    /**
     * @var array
     */
    public $config = [];

    public $required = false;
}
