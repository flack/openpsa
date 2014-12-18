<?php
/**
 * @package org.openpsa.core
* @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
* @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
* @license http://www.gnu.org/licenses/gpl.html GNU General Public License
*/

/**
 * Workflow base class
 *
 * @package org.openpsa.core
 */
abstract class org_openpsa_core_workflow
{
    /**
     *
     * @var midcom_core_dbaobject
     */
    protected $object;

    /**
     * @var string
     */
    protected $object_title;

    /**
     *
     * @var midcom_services_i18n_l10n
     */
    protected $l10n;

    /**
     *
     * @var midcom_services_i18n_l10n
     */
    protected $l10n_midcom;

    /**
     *
     * @param midcom_core_dbaobject $object
     */
    public function __construct(midcom_core_dbaobject $object)
    {
        $this->object = $object;
        $this->l10n = midcom::get()->i18n->get_l10n('org.openpsa.core');
        $this->l10n_midcom = midcom::get()->i18n->get_l10n('midcom');
    }

    public function get_object_title()
    {
        if ($this->object_title === null)
        {
            $this->object_title = midcom_helper_reflector::get_object_title($this->object);
        }
        return $this->object_title;
    }

    public function set_object_title($title)
    {
        $this->object_title = $title;
    }

    /**
     *
     * @param midcom_helper_toolbar $toolbar
     * @param string $url
     */
    abstract public function add_button(midcom_helper_toolbar $toolbar, $url);

    /**
     * @return boolean
     */
    abstract public function is_active();

    /**
     * @return boolean
     */
    abstract public function run();
}