<?php
$parameter_toolbar = new midcom_helper_toolbar();
$parameter_toolbar->add_item
(
    array
    (
        MIDCOM_TOOLBAR_URL => "__mfa/asgard/object/create/midgard_parameter/{$data['object']->guid}/",
        MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('add parameter', 'midgard.admin.asgard'),
        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-text.png',
    )
);
echo $parameter_toolbar->render();
    
if (count($data['parameters']) > 0)
{
    echo "<table class=\"midgard_admin_asgard_object_parameters\">\n";
    echo "    <thead>\n";
    echo "        <tr>\n";
    echo "            <th>" . $_MIDCOM->i18n->get_string('name', 'midcom') . "</th>\n";
    echo "            <th>" . $_MIDCOM->i18n->get_string('value', 'midcom') . "</th>\n";
    echo "            <th style=\"width: 80px;\">" . $_MIDCOM->i18n->get_string('actions', 'midcom') . "</th>\n";    
    echo "        </tr>\n";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    $shown_domains = array();
    foreach ($data['parameters'] as $parameter)
    {
        $parameter_toolbar = new midcom_helper_toolbar();
        $parameter_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "__mfa/asgard/object/view/{$parameter->guid}/",
                MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('view', 'midcom'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/view.png',
            )
        );
        $parameter_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "__mfa/asgard/object/edit/{$parameter->guid}/",
                MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('edit', 'midcom'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
            )
        );
        $parameter_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "__mfa/asgard/object/delete/{$parameter->guid}/",
                MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('delete', 'midcom'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
            )
        );

        if (!in_array($parameter->domain, $shown_domains))
        {
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
?>