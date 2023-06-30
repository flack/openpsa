<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\validation;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use midcom\datamanager\storage\container\container;

class callback
{
    private array $validation;

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
                        $this->add_violation([$field], $context, $message);
                    }
                }
            }
        }
    }

    private function add_violation(array $path, ExecutionContextInterface $context, $data)
    {
        if (is_string($data)) {
            $context
                ->buildViolation('[' . implode('][', $path) . ']')
                ->atPath($path)
                ->addViolation();
        } else {
            foreach ($data as $field => $message) {
                $childpath = $path;
                $childpath[] = $field;
                $this->add_violation($childpath, $context, $message);
            }
        }
    }

    /**
     * @param container|array $container
     * @return array
     */
    private function to_array($container) : array
    {
        if (is_array($container)) {
            // This is a newly added subform, not yet saved, so it's already in view format
            return $container;
        }
        $data = [];

        foreach ($container as $field => $value) {
            $data[$field] = $value->get_value();
        }

        return $data;
    }
}
