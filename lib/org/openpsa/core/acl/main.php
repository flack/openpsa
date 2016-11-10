<?php
/**
 * @package org.openpsa.core
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * ACL access types
 *
 * @package org.openpsa.core
 */
class org_openpsa_core_acl
{
    //Constants for ACL shortcuts
    const ACCESS_PRIVATE = 100;
    const ACCESS_WGPRIVATE = 101;
    const ACCESS_PUBLIC = 102;
    const ACCESS_AGGREGATED = 103;
    const ACCESS_WGRESTRICTED = 104;
    const ACCESS_ADVANCED = 105;

    /**
     * Make the ACL selection array available to all components
     */
    public static function get_options()
    {
        $l10n = midcom::get()->i18n->get_l10n('org.openpsa.core');
        return array(
            self::ACCESS_WGRESTRICTED => $l10n->get('workgroup restricted'),
            self::ACCESS_WGPRIVATE => $l10n->get('workgroup private'),
            self::ACCESS_PRIVATE => $l10n->get('private'),
            self::ACCESS_PUBLIC => $l10n->get('public'),
            self::ACCESS_AGGREGATED => $l10n->get('aggregated'),
        );
    }
}
