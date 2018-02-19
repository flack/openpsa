<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Article record with framework support.
 *
 * @property integer $id Local non-replication-safe database identifier
 * @property string $name URL name of the article
 * @property string $extra1 Extra string field
 * @property string $extra2 Extra string field
 * @property string $extra3 Extra string field
 * @property integer $type Type of the article
 * @property integer $up Possible prior part of the article
 * @property integer $topic Topic the article is under
 * @property string $title Title of the article
 * @property string $abstract Short abstract of the article
 * @property string $content Content of the article
 * @property string $url External URL of the article
 * @property string $guid
 * @package midcom.db
 */
class midcom_db_article extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_article';
}
