<?php
/**
 * @package midcom.baseclasses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Base class used for writing pure code components, retrieves a few common variables
 * from the component's current environment.
 *
 * Note, that the request data, for ease of use, already contains the L10n
 * Databases of the Component and MidCOM itself located in this class. They are stored
 * as 'l10n' and 'l10n_midcom'. Also available as 'config' is the current component
 * configuration.
 *
 * @package midcom.baseclasses
 */
abstract class midcom_baseclasses_components_purecode
{
    use midcom_baseclasses_components_base;
}
