<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Interface containing shortcut methods DBA classes must provide
 *
 * @package midcom
 */
interface midcom_core_dba_shortcuts
{
    /**
     * Shortcut for accessing MidCOM Query Builder
     *
     * @return midcom_core_querybuilder The initialized instance of the query builder.
     * @see midcom_core_querybuilder
     */
    public static function new_query_builder();

    /**
     * Shortcut for accessing MidCOM Collector
     *
     * @param string $domain The domain property of the collector instance
     * @param mixed $value Value match for the collector instance
     * @return midcom_core_collector The initialized instance of the collector.
     * @see midcom_core_collector
     */
    public static function new_collector($domain, $value);

    /**
     * Stub for accessing MidCOM object cache.
     * Like the previous two methods, this has to be implemented in all DBA classes
     * for PHP 5.2 compatibility due to the lack of late static bindings
     *
     * @param mixed $src GUID of object (ids work but are discouraged)
     * @return mixed Reference to the object or false
     */
    public static function &get_cached($src);
}
?>