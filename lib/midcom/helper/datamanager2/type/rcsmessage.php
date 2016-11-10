<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Simple rcsmessage datatype. The rcsmessage value encapsulated by this type is
 * passed as-is to the RCS service, no specialties done, just a string.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_type_rcsmessage extends midcom_helper_datamanager2_type
{
    /**
     * The current string encapsulated by this type.
     *
     * @var string
     */
    public $value = '';

    public function convert_from_storage($source)
    {
        if (method_exists($this->storage->object, 'get_rcs_message')) {
            $this->value = $this->storage->object->get_rcs_message();
        }
        // Nullstorage doesn't have RCS
    }

    public function convert_to_storage()
    {
        if (method_exists($this->storage->object, 'set_rcs_message')) {
            $this->storage->object->set_rcs_message($this->value);
        }
        // Nullstorage doesn't have RCS
    }

    public function convert_from_csv($source)
    {
        $this->value = $source;
    }

    public function convert_to_csv()
    {
        return $this->value;
    }

    public function convert_to_html()
    {
        return $this->value;
    }
}
