<?php
/**
 * @package org.openpsa.directmarketing
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Simle Email Importer for subscribers
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_importer_simpleemails extends org_openpsa_directmarketing_importer
{
    public function parse($input)
    {
        $parsed = [];

        // Make sure we only have NL linebreaks
        $contacts_raw = preg_replace("/\n\r|\r\n|\r/", "\n", $input);
        $contacts = explode($this->_settings['separator'], $contacts_raw);
        $contacts = array_filter(array_map('trim', $contacts));

        foreach ($contacts as $contact) {
            $parsed[] = [
                'person' => [
                    'email' => strtolower($contact),
                ]
            ];
        }
        return $parsed;
    }
}
