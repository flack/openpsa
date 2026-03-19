<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\validation;

use Symfony\Component\Validator\Constraint;

class urlname extends Constraint
{
    public bool $allow_unclean;
    public bool $allow_catenate;
    public string $title_field;
    public array $property;

    public function __construct(array $input)
    {
        $this->allow_unclean = $input['allow_unclean'] ?? false;
        $this->allow_catenate = $input['allow_catenate'] ?? false;
        $this->title_field = $input['title_field'] ?? 'title';
        $this->property = $input['property'];
        parent::__construct();
    }
}
