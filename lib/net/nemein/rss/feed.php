<?php
/**
 * @package net.nemein.rss
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM wrapped class for access to stored queries
 *
 * @property string $title
 * @property string $url
 * @property integer $node
 * @property integer $defaultauthor
 * @property boolean $forceauthor
 * @property boolean $keepremoved
 * @property boolean $autoapprove
 * @property integer $latestupdate
 * @property integer $latestfetch
 * @package net.nemein.rss
 */
class net_nemein_rss_feed_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'net_nemein_rss_feed';

    public function _on_loaded()
    {
        if ($this->title == '') {
            $this->title = "Feed #{$this->id}";
        }
    }
}
