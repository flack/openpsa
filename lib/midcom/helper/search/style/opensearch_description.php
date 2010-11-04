<?php
$node =& $data['node'];
$prefix = $_MIDCOM->get_host_name() . $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/"
                       xmlns:moz="http://www.mozilla.org/2006/browser/search/">
<ShortName><(title)></ShortName>
<Description><(title)> &(node.extra);</Description>
<InputEncoding>UTF-8</InputEncoding>
<Image height="16" width="16" type="image/x-icon"><?php echo $_MIDCOM->get_host_name() . MIDCOM_STATIC_URL . '/midcom.helper.search/search.ico'; ?></Image>
<Url type="text/html" method="get" template="&(prefix);result/?query={searchTerms}"/>
</OpenSearchDescription>
