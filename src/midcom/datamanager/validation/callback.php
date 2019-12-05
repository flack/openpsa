<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\validation;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use midcom\datamanager\storage\container\container;

class callback
{
    private $validation;

    public function __construct($validation)
    {
        $this->validation = $validation;
    }

    public function validate($payload, ExecutionContextInterface $context)
    {
        foreach ($this->validation as $entry) {
            if (!empty($entry['callback'])) {
                $form = $context->getRoot();
                // hack to get error messages to show up at the correct place (also see below)
                $context->setNode($form->getData(), $form, null, '');
                $result = call_user_func($entry['callback'], $this->to_array($form->getData()));
                if (is_array($result)) {
                    foreach ($result as $field => $message) {
                        $context
                            ->buildViolation($message)
                            // There might be a nice way to do this, but I have no idea what it could be...
                            ->atPath('children[' . $field . '].data')
                            ->addViolation();
                    }
                }
            }
        }
    }

    private function to_array(container $container) : array
    {
        $data = [];

        foreach ($container as $field => $value) {
            $data[$field] = $value->get_value();
        }

        return $data;
    }
}
