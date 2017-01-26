<?php
/**
 * Handler for searching duplicate groups and persons
 *
 * @package org.openpsa.contacts
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
midcom::get()->auth->require_valid_user();

// Get rid of output buffers (we need to make output to keep browser happy)
while (@ob_end_flush());

echo "<p>\n";

midcom::get()->auth->request_sudo('org.openpsa.contacts');

$dfinder = new org_openpsa_contacts_duplicates_check();
/* TODO: Get component configuration if possible
$dfinder->config = ;
*/
$dfinder->mark_all(true);

midcom::get()->auth->drop_sudo();
echo " ** ALL DONE<br/>\n";
echo "</p>\n";
