<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: tags.php 22990 2009-07-23 15:46:03Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/** @ignore */
require_once(MIDCOM_ROOT . '/midcom/helper/datamanager2/type/text.php');

/**
 * Datamanager 2 tag datatype. The text value encapsulated by this type is
 * passed to the net.nemein.tag library and corresponding tag objects and
 * relations will be handled there.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_type_tags extends midcom_helper_datamanager2_type_text
{
    /**
     * Automatically use this context for all tags that lack one
     */
    var $auto_context = null;

    /**
     * This event handler is called after construction, so passing references to $this to the
     * outside is safe at this point.
     *
     * @return boolean Indicating success, false will abort the type construction sequence.
     * @access protected
     */
    function _on_initialize()
    {
        return $_MIDCOM->load_library('net.nemein.tag');
    }

    function convert_from_storage($source)
    {
        if (! $this->storage->object)
        {
            // That's all folks, no storage object, thus we cannot continue.
            return;
        }

        $tags = net_nemein_tag_handler::get_object_tags($this->storage->object);
        $this->value = net_nemein_tag_handler::tag_array2string($tags);
    }

    function convert_to_storage()
    {
    
        $tag_array = net_nemein_tag_handler::string2tag_array($this->value);
        $this->auto_context = trim($this->auto_context);
        if (!empty($this->auto_context))
        {
            $new_tag_array = array();
            foreach ($tag_array as $tagname => $url)
            {
                $context = net_nemein_tag_handler::resolve_context($tagname);
                if (empty($context))
                {
                    $tagname = "{$this->auto_context}:{$tagname}";
                }
                $new_tag_array[$tagname] = $url;
            }
            unset($tagname, $url);
            $tag_array = $new_tag_array;
            unset($new_tag_array);
        }
        $status = net_nemein_tag_handler::tag_object($this->storage->object, $tag_array);
        if (!$status)
        {
            debug_add("Tried to save the tags \"{$this->value}\" for field {$this->name}, but failed. Ignoring silently.",
                MIDCOM_LOG_WARN);
        }
    
        return null;
    }

    function convert_to_raw()
    {
        return net_nemein_tag_handler::string2tag_array($this->value);
    }
}

?>