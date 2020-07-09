<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\datamanager\dependencyInjection\test;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Config\Definition\Builder\ValidationBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\RequestStack;
use midcom\bundle\dependencyInjection\translatorPass;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class translatorPassTest extends TestCase
{
    public function test_process()
    {
        $container = new ContainerBuilder();
        $container->register('request_stack', RequestStack::class);
        $container->register('i18n', \midcom_services_i18n::class)
            ->addArgument(new Reference('request_stack'));
        $container->register('validator.builder', ValidationBuilder::class);
        $container->register('translator', Translator::class)
            ->setFactory([new Reference('i18n'), 'get_translator']);
        $container->register('validator.builder', ValidationBuilder::class);
        $container->register('form.factory', FormFactory::class);

        $pass = new translatorPass;
        $pass->process($container);

        $this->assertCount(2, $container->getDefinition('translator')->getArgument(0));
    }
}
