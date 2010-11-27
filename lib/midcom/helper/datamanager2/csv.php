<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: csv.php 26505 2010-07-06 12:26:42Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 CSV Export helper class.
 *
 * It brings all necessary tools together to convert a series of DM2 instances to
 * a CSV listing. It uses a datamanager instance set to a given schema as base to
 * work on and provides convenience methods of converting full QB resultsets to CSV.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_csv extends midcom_baseclasses_components_purecode
{
    /**
     * Separator to use between fields, this defaults to ",".
     *
     * @var string
     */
    var $separator = ',';

    /**
     * Newline character sequence to use, this defaults to "\r\n".
     *
     * @var string
     */
    var $newline = "\r\n";

    /**
     * The datamanager instance to use for processing. This is set during object
     * construction and will be taken by reference (where it will not be associated
     * with any storage object yet).
     *
     * If you want to encode multiple objects, all have to have the same signature,
     * so changing the schema configuration of this instance during processing is not
     * allowed (unless you move to another type entirely).
     *
     * @var midcom_helper_datamanager2_datamanager
     */
    var $datamanager = null;

    /**
     * Initializes the class. The real startup is done by the initialize() call.
     *
     * @param midcom_helper_datamanager2_datamanager The DM instance to work with. This instance must
     *     already be schema-initialized, but should not have any storage object loaded yet.
     */
    function __construct($datamanager)
    {
         $this->_component = 'midcom.helper.datamanager2';
         $this->datamanager = $datamanager;
         parent::__construct();
    }

    /**
     * Encodes the given string into CSV according to these rules: Any appearance
     * of the separator or one of the two newline characters \n and \r will trigger
     * quoting. In quoting mode, the entire string will be enclosed in double-quotes.
     * Any occurrence of a double quote in the original string will be transformed
     * into two double quotes. Any leading or trailing whitespace around the data
     * will be eliminated.
     *
     * @param string $string The string to encode.
     * @return string The encoded string.
     * @access private
     */
    function _csv_encode ($string)
    {
        // Quote the whole line if the separator or any of the
        // newline characters \n or \r is within the data.

        $pattern='/[\n\r]|' . str_replace(Array("/","|"), Array("\/","\|"), $this->separator) . '/';
        $string = trim($string);

        if (preg_match($pattern, $string) != 0)
        {
            // Quoted operation required: Escape quotes
            return '"' . str_replace('"','""',$string) . '"';
        }
        else
        {
            // Unquoted operation required
            return $string;
        }
    }

    /**
     * This will yield a line containing all the field descriptions to
     * be used as column headers.
     *
     * This may trigger generate_error in case of critical errors (like an undefined
     * DM instance).
     *
     * @return string CSV header line.
     */
    function get_header_line ()
    {
        if (! $this->datamanager)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Cannot operate on CSV files while no DM2 instance is set.");
            // This will exit.
        }

        $result = '';
        $first = true;

        foreach ($this->datamanager->schema->field_order as $name)
        {
            $data = $this->_csv_encode($this->datamanager->schema->fields[$name]["title"]);
            if ($first)
            {
                $result .= $data;
                $first = false;
            }
            else
            {
                $result .= $this->separator . $data;
            }
        }
        $result .= $this->newline;
        return $result;
    }

    /**
     * This function will set the storage object to the one passed to this
     * function and will transform it then into a CSV compatible form.
     * The actual CSV representation of the data is determined by the individual
     * datatypes.
     *
     * This may trigger generate_error in case of critical errors (like an undefined
     * DM instance).
     *
     * @param MidCOMDBAObject &$object A reference to the object that should be dumped.
     * @return string CSV line.
     */
    function get_line (&$object)
    {
        if (! $this->datamanager)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Cannot operate on CSV files while no DM2 instance is set.");
            // This will exit.
        }

        // Setup the DM instance accordingly
        $this->datamanager->set_storage($object);

        $result = '';
        $first = true;

        foreach ($this->datamanager->schema->field_order as $name)
        {
            $data = $this->_csv_encode($this->datamanager->types[$name]->convert_to_csv());
            if ($first)
            {
                $result .= $data;
                $first = false;
            }
            else
            {
                $result .= $this->separator . $data;
            }
        }
        $result .= $this->newline;
        return $result;
    }

    /**
     * Converts the given list of objects (usually a QB resultset) to CSV using the
     * configuration currently set up in the component.
     *
     * This function will automatically enter live-mode and will set text/plain
     * as output encoding and gracefully shut the request down after successful
     * serving.
     *
     * Usage example (given a correctly prepared instance in $csv and a prepared
     * querybuilder in $qb:
     *
     * <code>
     * $result = $qb->execute();
     * $csv->convert_to_stdout($result);
     * // This will exit.
     * </code>
     *
     * @param Array list A list of objects to convert, all matchable by the current DM2 instance.
     */
    function convert_list_to_stdout(&$list)
    {
        _midcom_header('Content-Type: text/plain');
        $_MIDCOM->cache->content->enable_live_mode();

        echo $this->get_header_line();
        foreach ($list as $id => $copy)
        {
            echo $this->get_line($list[$id]);
        }

        $_MIDCOM->finish();
        _midcom_stop_request();
    }
}
?>