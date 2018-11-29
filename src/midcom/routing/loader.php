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
use midcom;
use midcom_baseclasses_components_configuration;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

/**
 * @package midcom.routing
 */
class loader extends base
{
    /**
     * @var YamlFileLoader
     */
    private $yaml_loader;

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Config\Loader\LoaderInterface::load()
     */
    public function load($input, $type = null)
    {
        if (is_string($input)) {
            if (!$this->is_legacy($input)) {
                return $this->get_yaml()->load($this->get_path($input, 'yml'), $type);
            }
            $input = $this->get_legacy_routes($input);
        }

        $collection = new RouteCollection();

        foreach ($input as $name => $config) {
            $path = '/';
            $requirements = [];

            if (empty($config['fixed_args'])) {
                $config['fixed_args'] = [];
            } else {
                $config['fixed_args'] = (array) $config['fixed_args'];
                $path = '/' . implode('/', $config['fixed_args']) . '/';
            }

            if (!array_key_exists('variable_args', $config)) {
                $config['variable_args'] = 0;
            }
            for ($i = 0; $i < $config['variable_args']; $i++) {
                $path .= '{args_' . $i . '}/';
                if (!empty($config['validation'][$i])) {
                    $requirements['args_' . $i] = $this->translate_validation($config['validation'][$i]);
                }
            }

            $defaults = [
                '_controller' => implode('::', (array) $config['handler'])
            ];

            $route = new Route($path, $defaults, $requirements);
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
        if (is_string($resource)) {
            if (!$this->is_legacy($resource)) {
                return $this->get_yaml()->supports($this->get_path($resource, 'yml'), $type);
            }
            return (!$type || 'string' === $type);
        }

        return is_array($resource) && (!$type || 'array' === $type);
    }

    /**
     * @param string $component
     * @return boolean
     */
    private function is_legacy($component)
    {
        return !file_exists($this->get_path($component, 'yml'));
    }

    /**
     * @return \Symfony\Component\Routing\Loader\YamlFileLoader
     */
    private function get_yaml()
    {
        if (empty($this->yaml_loader)) {
            $this->yaml_loader = new YamlFileLoader(new FileLocator);
        }
        return $this->yaml_loader;
    }

    private function get_path($component, $suffix)
    {
        return midcom::get()->componentloader->path_to_snippetpath($component) . '/config/routes.' . $suffix;
    }

    /**
     * @param string $component
     * @return array
     */
    public function get_legacy_routes($component)
    {
        $routes = [];
        if (!$this->is_legacy($component)) {
            return $routes;
        }
        $manifest = midcom::get()->componentloader->manifests[$component];
        if (!empty($manifest->extends)) {
            $routes = $this->get_legacy_routes($manifest->extends);
        }

        return array_merge($routes, $this->load_routes($component));
    }

    private function load_routes($component)
    {
        $path = $this->get_path($component, 'inc');
        // Load and parse the global config
        return midcom_baseclasses_components_configuration::read_array_from_file($path);
    }
}
