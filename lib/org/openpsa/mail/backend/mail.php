<?php
/**
 * @package org.openpsa.mail
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Send backend for org_openpsa_mail, using PHPs mail() function
 *
 * @package org.openpsa.mail
 */
class org_openpsa_mail_backend_mail extends org_openpsa_mail_backend
{
    public function __construct(array $params)
    {
    }

    public function mail($recipients, array $headers, $body)
    {
        $hdr = '';
        $subject = '';
        reset($headers);
        foreach ($headers as $key => $value)
        {
            if (strtolower($key) == 'to')
            {
                continue;
            }
            else if (strtolower($key) == 'subject')
            {
                $subject = $value;
                continue;
            }
            $hdr .= "{$key}: {$value}\n";
        }

        $additional_parameters = midcom_baseclasses_components_configuration::get('org.openpsa.mail', 'config')->get('mail_additional_params');

        return mail($recipients, $subject, $body, $hdr, $additional_parameters);
    }
}
?>