<?php
/**
 * @package midcom.helper.datamanager2
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Helper class for managing QuickForm rules
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_qfrule_manager
{
    /**
     * L10n DB
     *
     * @var midcom_services_i18n_l10n
     */
    private $_l10n;

    /**
     * The form we're working on
     *
     * @var HTML_QuickForm
     */
    private $_form;

    /**
     * Tracks which rules have already been registered
     *
     * @var array
     */
    private $_loaded = array();

    private $_available = array
    (
        'date' => array('checkjsdate', 'checksimpledate'),
        'select' => array('requiremultiselect')
    );

    public function __construct(HTML_QuickForm &$form, midcom_services_i18n_l10n $l10n)
    {
        $this->_form = $form;
        $this->_l10n = $l10n;
    }

    public function load_type_rules($type)
    {
        if (isset($this->_loaded[$type]))
        {
            // We have already initialized rules for this type
            return;
        }
        if (empty($this->_available[$type]))
        {
            // no file for this type found, skip silently
            return;
        }

        foreach ($this->_available[$type] as $rule_name)
        {
            $rule_class = "midcom_helper_datamanager2_qfrule_{$rule_name}";
            if (!class_exists($rule_class))
            {
                continue;
            }

            $stat = $this->_form->registerRule($rule_name, null, new $rule_class);
            if (is_a($stat, 'pear_error'))
            {
                $msg = $stat->getMessage();
                debug_add("Got PEAR error '{$msg}' from form->registerRule(), when adding {$type} rule '{$rule_name}'", MIDCOM_LOG_WARN);
            }
        }
    }

    /**
     * Add form-wide validation rules
     *
     * @param array $rules The schema to use
     */
    public function add_validation_rules(array $rules)
    {
        foreach ($rules as $config)
        {
            if (! is_callable($config['callback']))
            {
                // Try autoload:
                if (array_key_exists('autoload_snippet', $config))
                {
                    midcom_helper_misc::include_snippet_php($config['autoload_snippet']);
                }
                if (array_key_exists('autoload_file', $config))
                {
                    require_once($config['autoload_file']);
                }

                if (! function_exists($config['callback']))
                {
                    debug_add("Failed to register the callback {$config['callback']} for validation, the function is not defined.", MIDCOM_LOG_CRIT);
                    continue;
                }
            }
            $this->_form->addFormRule($config['callback']);
        }
    }

    /**
     * This helper function adds all rules which are deducible from the schema
     * to the form. It recognizes the following schema options:
     *
     * - required: Adds a required rule to the form, bound to the given element.
     *
     * @param midcom_helper_datamanager2_type $type
     * @param array $config
     */
    public function add_type_rules(midcom_helper_datamanager2_type $type, array $config)
    {
        if ($config['required'])
        {
            $message = sprintf
            (
                $this->_l10n->get('field %s is required'),
                $type->translate($config['title'])
            );
            switch (true)
            {
                // Match single image types (image & photo ATM)
                case (   is_a($type, 'midcom_helper_datamanager2_type_image')
                      && !is_a($type, 'midcom_helper_datamanager2_type_images')):
                    // 'required' does not work for uploads -> use 'uploadedfile'
                    // OTOH: Does this mean it requires new upload each time ?? TODO: Test
                    $this->_form->addRule("{$type->name}_file", $message, 'uploadedfile', '');
                    break;
                // Match all other blobs types (those allow multiple uploads which are kind of hard to validate)
                case (is_a($type, 'midcom_helper_datamanager2_type_blobs')):
                    // PONDER: How will you require-validate N uploads ?? (also see the point about existing files above)
                    debug_add("types with multiple files cannot have required validation (field name: {$type->name})", MIDCOM_LOG_ERROR);
                    break;
                // Other types should be fine with the default string validation offered by 'required'
                default:
                    $this->_form->addRule($type->name, $message, 'required', '');
                    break;
            }
        }

        foreach ($config['validation'] as $rule)
        {
            $message = $type->translate($rule['message']);
            switch ($rule['type'])
            {
                case 'compare':
                    $this->_form->addRule(array($rule['compare_with'], $type->name), $message, $rule['type'], $rule['format']);
                    break;

                default:
                    $this->_form->addRule($type->name, $message, $rule['type'], $rule['format']);
                    break;
            }
        }
    }
}