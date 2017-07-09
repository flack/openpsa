<?php
/**
 * @package midcom.admin.folder
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\extension\type\select;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

/**
 * Datamanager component selection widget.
 *
 * @package midcom.admin.folder
 */
class midcom_admin_folder_selectcomponent extends select
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('choice_attr', function($val) {
            if (midcom::get()->componentloader->get_component_icon($val, false)) {
                return ['style' => 'background-image: url(' . MIDCOM_STATIC_URL . '/' . midcom::get()->componentloader->get_component_icon($val) . ')'];
            }
            return [];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['attr']['class'] = 'selectcomponent';
    }
}
