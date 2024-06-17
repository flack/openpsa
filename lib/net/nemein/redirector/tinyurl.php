<?php
/**
 * @package net.nemein.redirector
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * TinyURL abstraction class for generating short URLs
 *
 * @property string $name
 * @property integer $code
 * @property string $node
 * @property string $title
 * @property string $url
 * @property string $description
 * @package net.nemein.redirector
 */
class net_nemein_redirector_tinyurl_dba extends midcom_core_dbaobject
{
    public string $__midcom_class_name__ = __CLASS__;
    public string $__mgdschema_class_name__ = 'net_nemein_redirector_tinyurl';

    /**
     * Trim a tiny url
     */
    public static function generate() : string
    {
        return midcom_helper_misc::random_string(6, '23456789abcdefghjkmnopqrstuvwxyz');
    }
}
