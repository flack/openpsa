<?php
/**
 * @package midcom.db
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * This is a small helper for managing the mgd1 pagecache
 *
 * @package midcom.db
 */
abstract class midcom_db_cachemember extends midcom_core_dbaobject
{
    public function _on_created()
    {
        $this->_invalidate_cache();
    }

    public function _on_updated()
    {
        $this->_invalidate_cache();
    }

    public function _on_deleted()
    {
        $this->_invalidate_cache();
    }

    private function _invalidate_cache()
    {
        if (extension_loaded('midgard')) {
            mgd_cache_invalidate();
        }
    }
}
