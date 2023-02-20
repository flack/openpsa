<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM wrapped class for access to stored queries
 *
 * @property integer $frompage
 * @property string $topage
 * @property integer $topageid
 * @package net.nemein.wiki
 */
class net_nemein_wiki_link_dba extends midcom_core_dbaobject
{
    public string $__midcom_class_name__ = __CLASS__;
    public string $__mgdschema_class_name__ = 'net_nemein_wiki_link';

    /**
     * @inheritdoc
     */
    public bool $_use_rcs = false;
}
