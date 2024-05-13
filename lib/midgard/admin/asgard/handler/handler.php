<?php
/**
 * @package midgard.admin.asgard
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midgard\portable\storage\connection;

/**
 * @package midgard.admin.asgard
 */
trait midgard_admin_asgard_handler
{
    public function get_response(?string $element = null) : midcom_response_styled
    {
        if (isset($_GET['ajax'])) {
            midcom::get()->skip_page_style = true;
        }
        $this->populate_breadcrumb_line();
        if ($element) {
            return $this->show($element, 'ASGARD_ROOT');
        }
        return new midcom_response_styled(midcom_core_context::get(), 'ASGARD_ROOT');
    }

    public function load_deleted(string $guid) : midcom_core_dbaobject
    {
        $type = connection::get_em()
            ->createQuery('SELECT r.typename from midgard_repligard r WHERE r.guid = ?1')
            ->setParameter(1, $guid)
            ->getSingleScalarResult();

        $dba_type = midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($type);

        $qb = midcom::get()->dbfactory->new_query_builder($dba_type);
        $qb->include_deleted();
        $qb->add_constraint('guid', '=', $guid);

        return $qb->get_result(0);
    }
}
