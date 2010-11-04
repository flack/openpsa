<?php
/**
 * Library for escaping user provided values displayed for example in input etc
 *
 * Backorted from MidCOM3 (http://repo.or.cz/w/midcom.git?a=tree;f=midcom_helper_xsspreventer;h=24b6e322e416d3d11b13a17e4b6dab2b83c99276;hb=HEAD)
 * 
 * @package midcom.helper.xsspreventer
 */
class midcom_helper_xsspreventer extends midcom_baseclasses_components_purecode
{
    function __construct()
    {
        $this->_component = 'midcom.helper.xsspreventer';
        return parent::__construct();
    }

    /**
     * Escape value of an XML attribute, also adds quotes around it
     *
     * @param string $input Attribute value to escape
     * @return string escaped $input (with added quotes)
     */
    function escape_attribute($input)
    {
        $output = str_replace('"', '&quot;', $input);
        return '"' . $output . '"';
    }

    /**
     * Escape contents of an XML element 
     * (basically prevents early closure of the element)
     *
     * @param string $element XML element to close
     * @param string $input Element content to escape
     * @return string $input with $element closing tags escaped
     */
    function escape_element($element, $input)
    {
        return preg_replace_callback
        (
            "%(<\s*)+(/\s*)+{$element}%i", 
            create_function
            (
                '$matches',
                'return htmlentities($matches[0]);'
            ),
            $input
        );
    }
}
?>