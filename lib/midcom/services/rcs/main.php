<?php
/**
 * @author tarjei huse
 * @package midcom.services.rcs
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * The RCS service gives a write only interface to different services wanting to save changes to objects.
 *
 * The RCS service will try to initialize the backend based on GNU RCS, but, if that fails, fall back
 * to the nullrcs handler. The nullrcs handler does not save anything at all.
 *
 * <b>Configuration parameters that are in use by this service:</b>
 * * string midcom_services_rcs_bin_dir - the prefix for the rcs utilities (normally /usr/bin)
 * * string midcom_services_rcs_root - the directory where the rcs files get placed.
 * * boolean midcom_services_rcs_enable - if set, midcom will fail hard if the rcs service is not operational.
 *
 * @package midcom.services.rcs
 */
class midcom_services_rcs
{
    /**
     * Array of handlers that rcs uses to manage object versioning.
     */
    private $_handlers = array();

    /**
     * The configuration object for the rcs service.
     *
     * @var midcom_services_rcs_config
     */
    private $config;

    /**
     * Constructor
     *
     * @param array $config the midcom_config
     */
    public function __construct($config = null)
    {
        if (is_null($config)) {
            $config = midcom::get()->config;
        }

        $this->config = new midcom_services_rcs_config($config);
    }

    /**
     * Loads the handler
     *
     * @return midcom_services_rcs_backend
     */
    function load_handler($object)
    {
        if (!$object->guid) {
            return false;
        }

        if (!array_key_exists($object->guid, $this->_handlers)) {
            $this->_handlers[$object->guid] = $this->config->get_handler($object);
        }

        return $this->_handlers[$object->guid];
    }

    /**
     * Create or update the RCS file for the object.
     *
     * @param object $object the midgard object to be saved
     * @param string $message the update message to save (optional)
     */
    function update($object, $message = null)
    {
        if (!$this->config->use_rcs()) {
            return true;
        }
        $handler = $this->load_handler($object);
        if (!is_object($handler)) {
            debug_add('Could not load handler!');
            return false;
        }
        if (!$handler->update($object, $message)) {
            debug_add('RCS: Could not save file!');
            return false;
        }
        return true;
    }

    /**
     * Determine if we should display a particular field in the diff or preview states
     */
    public static function is_field_showable($field)
    {
        switch ($field) {
            case 'guid':
            case 'id':
            case 'sitegroup':
                return false;
            case 'password':
                return midcom::get()->auth->admin;
            default:
                return true;
        }
    }
}
