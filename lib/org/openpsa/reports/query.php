<?php
/**
 * @package org.openpsa.reports
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM wrapped class for access to stored queries
 *
 * @property string $title title for a report meant for long-term storage
 * @property integer $start start timestamp for report window
 * @property integer $end  end timestamp for report window
 * @property string $style style used to display report
 * @property string $component component this query belongs to
 * @property string $relatedcomponent  component this query is related to
 * @property string $mimetype mimetype for output (in case it's not 'text/html')
 * @property string $extension file-extension (helps browsers, if not '.html')
 * @property integer $orgOpenpsaObtype used to distinguish between temporary and long-term storage
 * @package org.openpsa.reports
 */
class org_openpsa_reports_query_dba extends midcom_core_dbaobject
{
    const OBTYPE_REPORT = 7000;
    const OBTYPE_REPORT_TEMPORARY = 7001;

    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_query';

    public function _on_loaded()
    {
        if (!$this->orgOpenpsaObtype) {
            $this->orgOpenpsaObtype = self::OBTYPE_REPORT_TEMPORARY;
        }
        if (!$this->extension) {
            $this->extension = '.html';
        }
        if (!$this->mimetype) {
            $this->mimetype = 'text/html';
        }
    }

    /**
     * By default all authenticated users should be able to do
     * whatever they wish with query objects, later we can add
     * restrictions on object level as necessary.
     */
    public function get_class_magic_default_privileges()
    {
        $privileges = parent::get_class_magic_default_privileges();
        $privileges['USERS']['midgard:owner']  = MIDCOM_PRIVILEGE_ALLOW;
        // Just to be sure
        $privileges['USERS']['midgard:read']   = MIDCOM_PRIVILEGE_ALLOW;
        $privileges['USERS']['midgard:create'] = MIDCOM_PRIVILEGE_ALLOW;
        return $privileges;
    }

    /**
     * Autopurge after delete
     */
    public function _on_deleted()
    {
        $this->purge();
    }

    /**
     * @param string $component
     * @return org_openpsa_reports_query_dba[]
     */
    public static function get_saved($component)
    {
        $qb = self::new_query_builder();
        $qb->add_constraint('component', '=', 'org.openpsa.reports');
        $qb->add_constraint('orgOpenpsaObtype', '=', self::OBTYPE_REPORT);
        $qb->add_constraint('relatedcomponent', '=', $component);
        $qb->add_order('title');
        return $qb->execute();
    }
}
