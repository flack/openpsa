<?php
/**
 * @package midcom.templating
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\templating;

use midcom_services_cache_module_content;
use midcom_core_context;
use midgard_style;
use midgard_element;
use midcom_db_style;
use midcom_error_forbidden;

/**
 * Loader class for db-based templates
 *
 * @package midcom.templating
 */
class dbloader extends loader
{
    private $dbstyle;

    /**
     * @var midcom_services_cache_module_content
     */
    private $content_cache;

    public function __construct(midcom_services_cache_module_content $content_cache)
    {
        $this->content_cache = $content_cache;
    }

    public function get_element(string $name, bool $scope_from_path) : ?string
    {
        $scope = 0;
        if ($scope_from_path) {
            // we have full qualified path to element
            $matches = [];
            if (preg_match("|(.*)/(.*)|", $name, $matches)) {
                $styleid = midcom_db_style::id_from_path($matches[1]);
                $name = $matches[2];
            }
            $scope = $styleid ?? $this->dbstyle;
        } else {
            try {
                $root_topic = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ROOTTOPIC);
                if ($root_topic->style) {
                    $scope = midcom_db_style::id_from_path($root_topic->style);
                }
            } catch (midcom_error_forbidden $e) {
                $e->log();
            }
        }

        if ($scope && $content = $this->get_element_in_styletree($scope, $name)) {
            return $content;
        }

        return parent::get_element($name, $scope_from_path);
    }

    /**
     * Returns a style element that matches $name and is in style $id.
     * It also returns an element if it is not in the given style,
     * but in one of its parent styles.
     */
    private function get_element_in_styletree(int $id, string $name) : ?string
    {
        $cache_key = $id . '::' . $name;
        if (array_key_exists($cache_key, $this->cache)) {
            return $this->cache[$cache_key];
        }

        $element_mc = midgard_element::new_collector('style', $id);
        $element_mc->set_key_property('guid');
        $element_mc->add_value_property('value');
        $element_mc->add_constraint('name', '=', $name);
        $element_mc->execute();

        if ($element_guid = key($element_mc->list_keys())) {
            $this->content_cache->register($element_guid);
            return $this->add_to_cache($cache_key, $element_mc->get_subkey($element_guid, 'value'));
        }

        // No such element on this level, check parents
        $style_mc = midgard_style::new_collector('id', $id);
        $style_mc->set_key_property('guid');
        $style_mc->add_value_property('up');
        $style_mc->add_constraint('up', '>', 0);
        $style_mc->execute();

        if ($style_guid = key($style_mc->list_keys())) {
            $this->content_cache->register($style_guid);
            $up = $style_mc->get_subkey($style_guid, 'up');
            return $this->get_element_in_styletree($up, $name);
        }

        $this->cache[$cache_key] = null;
        return $this->cache[$cache_key];
    }

    /**
     * Initializes style sources
     */
    public function initialize(midcom_core_context $context, ?string $style)
    {
        $this->dbstyle = 0;
        // get user defined style for component
        // style inheritance
        // should this be cached somehow?

        if ($style && $this->dbstyle = midcom_db_style::id_from_path($style)) {
            if ($substyle = $context->get_key(MIDCOM_CONTEXT_SUBSTYLE)) {
                $chain = explode('/', $substyle);
                foreach ($chain as $stylename) {
                    if ($_subst_id = midcom_db_style::id_from_path($stylename, $this->dbstyle)) {
                        $this->dbstyle = $_subst_id;
                    }
                }
            }
        }
        parent::initialize($context, $style);
    }
}
