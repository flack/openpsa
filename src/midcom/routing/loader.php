<?php
/**
 * @package midcom.routing
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\routing;

use Symfony\Component\Config\Loader\Loader as base;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

/**
 * @package midcom.routing
 */
class loader extends base
{
    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Config\Loader\LoaderInterface::load()
     */
    public function load($array, $type = null)
    {
        $collection = new RouteCollection();

        foreach ($array as $name => $config) {
            $path = '/';
            $requirements = [];
            if (!empty($config['fixed_args'])) {
                $path = '/' . implode('/', $config['fixed_args']) . '/';
            }
            for ($i = 0; $i < $config['variable_args']; $i++) {
                $path .= '{args_' . $i . '}/';
                if (!empty($config['validation'][$i])) {
                    $requirements['args_' . $i] = $this->translate_validation($config['validation'][$i]);
                }
            }
            $route = new Route($path, $config, $requirements);
            $collection->add($name, $route);
        }

        return $collection;
    }

    /**
     * Small transitory helper for old-style route validation configs
     *
     * @param array $input
     * @return string
     */
    private function translate_validation(array $input)
    {
        foreach ($input as &$value) {
            if ($value == 'is_numeric' || $value == 'is_int') {
                $value = '\d+';
            } elseif ($value == 'mgd_is_guid') {
                $value = '[0-9a-f]{21,80}';
            }
        }
        return implode('|', $input);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return is_array($resource) && (!$type || 'array' === $type);
    }
}
