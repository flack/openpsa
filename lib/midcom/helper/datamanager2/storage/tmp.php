<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Data storage implementation: Temporary storage object.
 *
 * This class is aimed to provide a dummy storage object, used as intermediate backend between
 * nullstorage based creations and final save operations if and only if attachments or parameters
 * are set outside the scope of regular storage operations.
 * Usually, you won't save datamanagers running with this storage backend, accessing the types
 * values directly. The types' convert_to_xxx helper functions can be a nice help if you need
 * string representations. This type is usually only used with the creation controller.
 *
 * This class is a specialization of the null storage backend with a pre-initialized $object
 * member.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_storage_tmp extends midcom_helper_datamanager2_storage_null
{
    /**
     * Start up the storage manager and bind it to a given temporary object.
     *
     * @param midcom_helper_datamanager2_schema $schema The data schema to use for processing.
     * @param array $defaults The defaults to use as "artificial" storage. This can be omitted
     *     safely.
     * @param midcom_core_temporary_object $object The temporary object to use.
     */
    public function __construct($schema, $defaults = [], $object)
    {
        parent::__construct($schema, $defaults);
        $this->object = $object;
    }
}
