<?php
/**
 * Really Simple Discoverability format document for net.nehmer.blog
 *
 * RSD is used by several desktop blogging tools for autoprobing blog capabilities.
 *
 * More information about the format can be found in:
 * http://archipelago.phrasewise.com/rsd
 */
$prefix = $_MIDCOM->get_host_name() . midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
echo '<?xml version="1.0" ?>';
?>
<rsd version="1.0" xmlns="http://archipelago.phrasewise.com/rsd" >
    <service>
        <engineName>Midgard CMS/<?php echo substr(mgd_version(), 0, 4); ?></engineName>
        <engineLink>http://www.midgard-project.org/</engineLink>
        <homePageLink><?php echo htmlspecialchars($prefix);?></homePageLink>
        <apis>
            <api name="MetaWeblog" preferred="true" apiLink="<?php echo htmlspecialchars($prefix);?>api/metaweblog/" blogID="<?php echo $data['content_topic']->guid; ?>" />
            <api name="Blogger" preferred="false" apiLink="<?php echo htmlspecialchars($prefix);?>api/metaweblog/" blogID="<?php echo $data['content_topic']->guid; ?>" />
        </apis>
    </service>
</rsd>