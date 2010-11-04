<?php
/**
 * Handler for searching duplicate groups and persons
 *
 * @package org.openpsa.contacts
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: mark_duplicates.php 22916 2009-07-15 09:53:28Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
$_MIDCOM->auth->require_valid_user();

// Get rid of output buffers (we need to make output to keep browser happy)
while(@ob_end_flush());

echo "<p>\n";

$_MIDCOM->auth->request_sudo('org.openpsa.contacts');

$dfinder = new org_openpsa_contacts_duplicates();
/* TODO: Get component configuration if possible
$dfinder->config = ;
*/
$dfinder->mark_all(true);

$_MIDCOM->auth->drop_sudo();
echo " ** ALL DONE<br/>\n";
echo "</p>\n";
?>