<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is the loader class of the cron service.
 *
 * <b>Cron Job configuration</b>
 *
 * Each cron job is defined by an associative array containing the following keys:
 *
 * - <i>string handler</i> holds the full class name which should handle the cron job invocation,
 *   it will be defined by the responsible component.
 * - <i>int recurrence</i> must be one of MIDCOM_CRON_* constants.
 * - <i>string component (INTERNAL)</i> holds the name of the component this Cron job is associated with.
 *   This key is created automatically.
 *
 * The Cron service uses <i>customdata</i> section of the manifest, using the key <i>midcom.services.cron</i>
 * as you might have guessed. So, an example cron entry could look like this:
 *
 * <code>
 * 'customdata' => [
 *     'midcom.services.cron' => [
 *         [
 *             'handler' => 'net_nehmer_static_cron_test',
 *             'recurrence' => MIDCOM_CRON_MINUTE,
 *         ]
 *     ],
 * ],
 * </code>
 *
 * @see midcom\console\command\cron
 * @package midcom.services
 */
class midcom_services_cron
{
    /**
     * The recurrence rule to use. Set in the constructor
     *
     * MIDCOM_CRON_MINUTE, MIDCOM_CRON_HOUR, or MIDCOM_CRON_DAY
     *
     * @var int
     */
    private $_recurrence = MIDCOM_CRON_MINUTE;

    /**
     * Constructor.
     */
    public function __construct($recurrence = MIDCOM_CRON_MINUTE)
    {
        $this->_recurrence = $recurrence;
    }

    /**
     * Load and validate all registered jobs.
     * After this call, all required handler classes will be available.
     *
     * @param array $data The job configurations
     */
    public function load_jobs(array $data)
    {
        $ret = [];
        foreach ($data as $component => $jobs) {
            foreach ($jobs as $job) {
                if ($this->_validate_job($job)) {
                    $job['component'] = $component;
                    $ret[] = $job;
                }
            }
        }
        return $ret;
    }

    /**
     * Check a jobs definition for validity.
     *
     * @param array $job The job to register.
     * @return boolean Indicating validity.
     */
    private function _validate_job(array $job)
    {
        if (!array_key_exists('handler', $job)) {
            throw new midcom_error("No handler declaration.");
        }
        if (!array_key_exists('recurrence', $job)) {
            throw new midcom_error("No recurrence declaration.");
        }
        if (!class_exists($job['handler'])) {
            throw new midcom_error("Handler class {$job['handler']} is not available.");
        }
        if (!in_array($job['recurrence'], [MIDCOM_CRON_MINUTE, MIDCOM_CRON_HOUR, MIDCOM_CRON_DAY])) {
            throw new midcom_error("Invalid recurrence.");
        }

        return $job['recurrence'] == $this->_recurrence;
    }
}
