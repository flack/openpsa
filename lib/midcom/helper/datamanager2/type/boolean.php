<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Simple boolean datatype.
 *
 * Storage is done in number format, where 0 is false and 1 is true. When reading
 * the storage, the PHP boolean conversion is used, so other representations (for legacy
 * compatibility) are recognized as well.
 *
 * <b>Available configuration options:</b>
 *
 * - <i>string true_text:</i> The text displayed if the value of the type is true. This defaults
 *   to a graphic "checked" icon. Must be valid for usage in convert_to_html.
 * - <i>string false_text:</i> The text displayed if the value of the type is false. This defaults
 *   to a graphic "not checked" icon. Must be valid for usage in convert_to_html.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_type_boolean extends midcom_helper_datamanager2_type
{
    /**
     * The current value encapsulated by this type.
     *
     * @var boolean
     */
    public $value;

    /**
     * The text displayed if the value of the type is true. This defaults to
     * a graphic "checked" icon. Must be valid for usage in convert_to_html.
     *
     * @var string
     */
    public $true_text = null;

    /**
     * The text displayed if the value of the type is false. This defaults to
     * a graphic "not checked" icon. Must be valid for usage in convert_to_html.
     *
     * @var string
     */
    public $false_text = null;

    public function convert_from_storage ($source)
    {
        if ($source === null)
        {
            $this->value = null;
        }
        else
        {
            $this->value = (boolean) $source;
        }
    }

    public function convert_to_storage()
    {
        return ($this->value) ? 1 : 0;
    }

    public function convert_from_csv ($source)
    {
        $this->value = (boolean) $source;
    }

    public function convert_to_csv()
    {
        return ($this->value) ? '1' : '0';
    }

    public function convert_to_email()
    {
        return $this->_l10n->get(($this->value) ? 'yes' : 'no');
    }

    /**
     * The HTML representation returns either the configured texts or a
     * checked / unchecked icon if left on defaults.
     */
    public function convert_to_html()
    {
        if ($this->value)
        {
            if ($this->true_text)
            {
                return $this->true_text;
            }
            $src = MIDCOM_STATIC_URL . '/stock-icons/16x16/ok.png';
            return "<img src='{$src}' alt='selected' />";
        }
        if ($this->false_text)
        {
            return $this->false_text;
        }
        $src = MIDCOM_STATIC_URL . '/stock-icons/16x16/cancel.png';
        return "<img src='{$src}' alt='not selected' />";
    }
}
