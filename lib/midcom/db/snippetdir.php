<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard SnippetDir record with framework support.
 *
 * The uplink is the owning snippetdir.
 *
 * @property string $name Path name of the snippetdir
 * @property integer $up Snippetdir the snippetdir is under
 * @package midcom.db
 */
class midcom_db_snippetdir extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_snippetdir';
}
