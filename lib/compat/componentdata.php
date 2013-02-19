<?php
/**
 * @package midcom.compat
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Compat wrapper for midcom_component_data
 *
 * @package midcom.compat
 */
class midcom_compat_componentdata implements arrayaccess
{
    public function offsetSet($offset, $value)
    {
        //TODO: Do we need this?
    }

    public function offsetExists($offset)
    {
        try
        {
            midcom_baseclasses_components_configuration::get($offset);
            return true;
        }
        catch (midcom_error $e)
        {
            return false;
        }
    }

    public function offsetUnset($offset)
    {
        //TODO: Do we need this?
    }

    public function offsetGet($offset)
    {
        try
        {
            return midcom_baseclasses_components_configuration::get($offset);
        }
        catch (midcom_error $e)
        {
            return null;
        }
    }
}
?>
