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
 * @package net.nemein.calendar
 */
class net_nemein_redirector_tinyurl_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'net_nemein_redirector_tinyurl';

    /**
     * Check that everything is ok on creation event
     */
    public function _on_creating()
    {
        return (!$this->duplicate_names());
    }

    /**
     * Check that everything is ok on creation event
     */
    public function _on_updating()
    {
        return (!$this->duplicate_names());
    }

    /**
     * Check for duplicate names
     */
    private function duplicate_names()
    {
        $mc = self::new_collector('name', $this->name);
        $mc->add_constraint('node', '=', $this->node);

        // This item already exists, exclude itself from duplicate name check
        if ($this->guid)
        {
            $mc->add_constraint('guid', '<>', $this->guid);
        }

        $mc->execute();

        return (count($mc->list_keys()) > 0);
    }

    /**
     * Trim a tiny url
     */
    public static function generate($chars = null, $length = 6)
    {
        if (!$chars)
        {
            $chars = '23456789abcdefghjkmnopqrstuvwxyz';
        }

        $url = '';
        $tmp = (int) strlen($chars);

        for ($i = 0; $i < $length; $i++)
        {
            $url .= substr($chars, rand(0, $tmp - 1), 1);
        }

        return $url;
    }
}
