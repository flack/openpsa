<?php
/**
 * @package org.openpsa.relatedto
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM wrapped base class, keep logic here
 *
 * @property string $linkGuid
 * @property string $title
 * @property string $text
 * @property integer $followUp date to show up the entry
 * @property integer $status
 * @property boolean $closed
 * @package org.openpsa.relatedto
 */
class org_openpsa_relatedto_journal_entry_dba extends midcom_core_dbaobject
{
    public string $__midcom_class_name__ = __CLASS__;
    public string $__mgdschema_class_name__ = 'org_openpsa_relatedto_journal_entry';

    public bool $_use_rcs = false;
}
