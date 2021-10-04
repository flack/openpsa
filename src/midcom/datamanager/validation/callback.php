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

    public function __construct(array $validation)
    {
        $this->validation = $validation;
    }

    public function validate($payload, ExecutionContextInterface $context)
    {
        foreach ($this->validation as $entry) {
            if (!empty($entry['callback'])) {
                $form = $context->getObject();
                $result = $entry['callback']($this->to_array($form->getData()));
                if (is_array($result)) {
                    foreach ($result as $field => $message) {
                        $context
                            ->buildViolation($message)
                            ->atPath('[' . $field . ']')
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
