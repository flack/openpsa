<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\datamanager\test;

use midcom\datamanager\validation\imageValidator;
use midcom\datamanager\validation\image;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class imageValidatorTest extends TestCase
{
    public function test_validate()
    {
        $constraint = new image;

        $validator = $this->get_validator();
        $validator->validate(null, $constraint);

        $validator = $this->get_validator();
        $validator->validate(['delete' => true], $constraint);

        $validator = $this->get_validator(true);
        $validator->validate(['file' => 'XX'], $constraint);

        $validator = $this->get_validator(true);
        $validator->validate(['archival' => 'XX'], $constraint);

        $constraint->config = ['do_not_save_archival' => true];
        $validator = $this->get_validator();
        $validator->validate(['archival' => 'XX'], $constraint);

        $validator = $this->get_validator(true);
        $validator->validate(['main' => 'XX'], $constraint);
    }

    private function get_validator(bool $expect_success = false) : imageValidator
    {
        $validator = new imageValidator;

        /** @var ExecutionContextInterface|MockObject $context */
        $context = $this->getMockBuilder(ExecutionContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ConstraintViolationBuilderInterface|MockObject $violation_builder */
        $violation_builder = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        if (!$expect_success) {
            $context
                ->expects($this->once())
                ->method('buildViolation')
                ->with('This value should not be blank.')
                ->willReturn($violation_builder);

            $violation_builder
                ->expects($this->once())
                ->method('addViolation')
                ->willReturn(null);
        } else {
            $context
                ->expects($this->exactly(0))
                ->method('buildViolation');
        }
        $validator->initialize($context);
        return $validator;
    }
}
