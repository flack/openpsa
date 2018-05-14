<?php
$parameter_toolbar = new midcom_helper_toolbar();
$parameter_toolbar->add_item([
    MIDCOM_TOOLBAR_URL => "__mfa/asgard/object/create/midgard_parameter/{$data['object']->guid}/",
    MIDCOM_TOOLBAR_LABEL => $data['l10n']->get('add parameter'),
    MIDCOM_TOOLBAR_GLYPHICON => 'plus',
]);
echo $parameter_toolbar->render();

if (count($data['parameters']) > 0) {
    echo "<table class=\"midgard_admin_asgard_object_parameters\">\n";
    echo "    <thead>\n";
    echo "        <tr>\n";
    echo "            <th>" . $data['l10n_midcom']->get('name') . "</th>\n";
    echo "            <th>" . $data['l10n_midcom']->get('value') . "</th>\n";
    echo "            <th style=\"width: 80px;\">" . $data['l10n_midcom']->get('actions') . "</th>\n";
    echo "        </tr>\n";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    $shown_domains = [];
    foreach ($data['parameters'] as $parameter) {
        $parameter_toolbar = new midcom_helper_toolbar();
        $buttons = [
            [
                MIDCOM_TOOLBAR_URL => "__mfa/asgard/object/view/{$parameter->guid}/",
                MIDCOM_TOOLBAR_LABEL => $data['l10n_midcom']->get('view'),
                MIDCOM_TOOLBAR_GLYPHICON => 'eye',
            ],
            [
                MIDCOM_TOOLBAR_URL => "__mfa/asgard/object/edit/{$parameter->guid}/",
                MIDCOM_TOOLBAR_LABEL => $data['l10n_midcom']->get('edit'),
                MIDCOM_TOOLBAR_GLYPHICON => 'pencil',
            ],
            [
                MIDCOM_TOOLBAR_URL => "__mfa/asgard/object/delete/{$parameter->guid}/",
                MIDCOM_TOOLBAR_LABEL => $data['l10n_midcom']->get('delete'),
                MIDCOM_TOOLBAR_GLYPHICON => 'trash',
            ]
        ];
        $parameter_toolbar->add_items($buttons);
        if (!in_array($parameter->domain, $shown_domains)) {
            echo "        <tr>\n";
            echo "            <th colspan=\"3\">{$parameter->domain}</th>\n";
            echo "        </tr>\n";
            $shown_domains[] = $parameter->domain;
        }

        echo "        <tr>\n";
        echo "            <td style=\"vertical-align: top;\">{$parameter->name}</td>\n";
        echo "            <td style=\"vertical-align: top;\">" . nl2br(htmlentities($parameter->value)) . "</td>\n";
        echo "            <td style=\"vertical-align: top;\">" . $parameter_toolbar->render() . "</td>\n";
        echo "        </tr>\n";
    }
    echo "    </tbody>\n";
    echo "</table>\n";
}
