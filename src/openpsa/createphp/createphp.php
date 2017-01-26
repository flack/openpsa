<?php
/**
 * @package openpsa.createphp
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace openpsa\createphp;

use midcom_error;
use openpsa\createphp\workflow\delete;
use Midgard\CreatePHP\RdfMapperInterface;
use Midgard\CreatePHP\ArrayLoader;

/**
 * MidCOM CreatePHP integration
 *
 * @package openpsa.createphp
 */
class createphp
{
    /**
     *
     * @var Midgard\CreatePHP\Manager
     */
    private $_manager;

    /**
     *
     * @var RdfMapperInterface
     */
    private $_mapper;

    public function __construct(array $config, RdfMapperInterface $mapper = null)
    {
        if (null === $mapper) {
            $this->_mapper = new dba2rdfMapper;
        } else {
            $this->_mapper = $mapper;
        }
        $loader = new ArrayLoader($config);
        $this->_manager = $loader->getManager($this->_mapper);
        $this->_manager->getRestHandler()->registerWorkflow('delete', new delete);
    }

    /**
     * Add Create.js static files to midcom_helper_head
     */
    public static function add_head_elements()
    {
        $head = \midcom::get()->head;
        $head->enable_jquery();
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/core.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/position.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/widget.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/button.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/dialog.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/droppable.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/effect.min.js');
        $head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/effect-highlight.min.js');

        $prefix = MIDCOM_STATIC_URL . '/openpsa.createphp/';

        $head->add_jsfile($prefix . 'deps/underscore-min.js');
        $head->add_jsfile($prefix . 'deps/backbone-min.js');
        $head->add_jsfile($prefix . 'deps/vie-min.js');
        $head->add_jsfile($prefix . 'deps/hallo-min.js');
        $head->add_jsfile($prefix . 'deps/rangy-core-1.2.3.js');
        $head->add_jsfile($prefix . 'create-min.js');

        $head->add_stylesheet($prefix . 'deps/font-awesome/css/font-awesome.css');
        $head->add_stylesheet($prefix . 'themes/create-ui/css/create-ui.css');
        $head->add_stylesheet($prefix . 'themes/midgard-notifications/midgardnotif.css');
    }

    public function get_mapper()
    {
        return $this->_mapper;
    }

    public function get_controller(\midcom_core_dbaobject $object, $type)
    {
        return $this->_manager->getEntity($object, $type);
    }

    public function render_widget()
    {
        return $this->_manager->getWidget();
    }

    /**
     * returns the rdf configs schema name we have to proceed on
     *
     * @param array $data
     * @return string
     */
    private function _get_rdf_schema_name(array $data = null)
    {
        $object = $this->get_object($data);
        return get_class($object);
    }

    public function get_object(array $data = null)
    {
        if (!empty($data)) {
            $object = $this->_mapper->getBySubject(trim($data['@subject'], '<>'));
        } elseif (!empty($_GET['subject'])) {
            $object = $this->_mapper->getBySubject(trim($_GET['subject'], '<>'));
        }

        return $object;
    }

    /**
     *
     * @param array $data
     * @param string $rdf_schema_name
     */
    public function process_rest($data, $rdf_schema_name = false)
    {
        if (null === $data) {
            $data = array();
        }

        if (!$rdf_schema_name) {
            $rdf_schema_name = $this->_get_rdf_schema_name($data);
        }

        $service = $this->_manager->getResthandler();
        $controller = $this->_manager->getType($rdf_schema_name);

        return new \midcom_response_json($service->run($data, $controller));
    }

    public function process_workflow()
    {
        if (empty($_GET["subject"])) {
            throw new midcom_error("no subject passed");
        }

        return new \midcom_response_json($this->_manager->getRestHandler()->getWorkflows($_GET["subject"]));
    }
}
