<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Snippet record with framework support.
 *
 * The uplink is the owning snippetdir.
 *
 * @property integer $id Local non-replication-safe database identifier
 * @property string $name Path name of the snippet
 * @property integer $snippetdir Snippetdir the snippet is under
 * @property string $code Code of the snippet
 * @property string $doc Documentation of the snippet
 * @property string $guid
 * @package midcom.db
 */
class midcom_db_snippet extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_snippet';

    public function get_icon()
    {
        return 'script.png';
    }
}
