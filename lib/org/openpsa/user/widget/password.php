<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @package org.openpsa.user
 */

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use midcom\datamanager\extension\type\radiocheckselectType;
use Symfony\Component\Validator\Constraints\Length;

/**
 * OpenPSA password widget
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_widget_password extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('compound', true);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $config = midcom_baseclasses_components_configuration::get('org.openpsa.user', 'config');
        $builder->add('switch', radiocheckselectType::class, [
            'type_config' => ['options' => ['generate_password', 'own_password']],
            'data' => 0,
            'label_attr' => ['style' => 'display: none']
        ]);
        $builder->add('password', PasswordType::class, [
            'label_attr' => ['style' => 'display: none'],
            'attr' => ['autocomplete' => 'new-password'],
            'constraints' => [new Length(min: $config->get('min_password_length'))]
        ]);

        $l10n = midcom::get()->i18n->get_l10n('org.openpsa.user');
        self::jsinit('input[type="password"]', $l10n, $config, false);
    }

    public static function jsinit(string $name, midcom_services_i18n_l10n $l10n, midcom_helper_configuration $config, $userid_required)
    {
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.user/password.js');
        $conf = [
            'strings' => [
                'shortPass' => $l10n->get("password too short"),
                'badPass' => $l10n->get("password weak"),
                'goodPass' => $l10n->get("password good"),
                'strongPass' => $l10n->get("password strong"),
                'samePassword' => $l10n->get("username and password identical"),
            ],
            'password_rules' => $config->get('password_score_rules'),
            'min_length' => $config->get('min_password_length'),
            'min_score' => $config->get('min_password_score'),
            'unique_character_score' => $config->get('unique_character_score'),
            'userid_required' => $userid_required
        ];
        $conf = json_encode($conf);
        midcom::get()->head->add_jquery_state_script("$('" . $name . "').password_widget(" . $conf . ");");
    }
}
