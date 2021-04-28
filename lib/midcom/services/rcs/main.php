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
     * @var midcom_services_rcs_config
     */
    private $config;

    public function __construct(midcom_config $config)
    {
        $this->config = new midcom_services_rcs_config($config);
    }

    /**
     * Factory function for the handler object.
     */
    public function load_backend(midcom_core_dbaobject $object) : midcom_services_rcs_backend
    {
        if (!$object->guid) {
            $class = midcom_services_rcs_backend_null::class;
        } else {
            $class = $this->config->get_backend_class();
        }
        return new $class($object, $this->config);
    }

    /**
     * Create or update the RCS file for the object.
     */
    public function update(midcom_core_dbaobject $object, string $message = null) : bool
    {
        if (!$this->config->use_rcs()) {
            return true;
        }
        $backend = $this->load_backend($object);
        try {
            $backend->update($message);
            return true;
        } catch (midcom_error $e) {
            debug_add('RCS: Could not save file!');
            $e->log();
            return false;
        }
    }

    /**
     * Determine if we should display a particular field in the diff or preview states
     */
    public static function is_field_showable(string $field) : bool
    {
        return !in_array($field, ['id', 'guid'], true);
    }
}
