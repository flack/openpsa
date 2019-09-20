<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Topic record with framework support.
 *
 * @property string $name URL name of the topic
 * @property string $code Executable PHP code for topic display
 * @property integer $up Topic the topic is under
 * @property string $component MidCOM component responsible for the topic
 * @property string $style MidCOM style path for the topic
 * @property boolean $styleInherit Whether the MidCOM style should be inherited to subtopics
 * @property string $title Title of the topic
 * @property string $extra Extra information about the topic
 * @property string $description Topic description
 * @package midcom.db
 */
class midcom_db_topic extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_topic';

    public function get_label() : string
    {
        return $this->extra ?: $this->name ?: '#' . $this->id;
    }

    public function _on_loaded()
    {
        // if we are missing the component, use the nullcomponent.
        if (   !$this->component
            || !array_key_exists($this->component, midcom::get()->componentloader->manifests)) {
            debug_add("Topic {$this->id} has no component assigned to it, using 'midcom.core.nullcomponent'.",
            MIDCOM_LOG_INFO);
            $this->component = 'midcom.core.nullcomponent';
        }
    }
}
