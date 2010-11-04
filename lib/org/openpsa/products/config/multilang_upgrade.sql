CREATE TABLE org_openpsa_products_product_i (
  id int(11) NOT NULL auto_increment,
  sid int(11) NOT NULL default 0,
  title varchar(255) NOT NULL default '',
  description longtext NOT NULL,
  created datetime NOT NULL default '0000-00-00 00:00:00',
  lang int(11) NOT NULL default 0,
  sitegroup int(11) NOT NULL default 0,
  PRIMARY KEY  (id),
  KEY org_openpsa_products_product_i_sitegroup_idx (sitegroup),
  KEY org_openpsa_products_product_i_sid_idx (sid,lang),
  KEY org_openpsa_products_product_i_lang_idx (lang)
);
INSERT INTO org_openpsa_products_product_i (sid, title, description, sitegroup) SELECT id, title, description, sitegroup FROM org_openpsa_products_product;
ALTER TABLE org_openpsa_products_product DROP title;
ALTER TABLE org_openpsa_products_product DROP description;

CREATE TABLE org_openpsa_products_product_group_i (
  id int(11) NOT NULL auto_increment,
  sid int(11) NOT NULL default 0,
  title varchar(255) NOT NULL default '',
  description longtext NOT NULL,
  created datetime NOT NULL default '0000-00-00 00:00:00',
  lang int(11) NOT NULL default 0,
  sitegroup int(11) NOT NULL default 0,
  PRIMARY KEY  (id),
  KEY org_openpsa_products_product_group_i_sitegroup_idx (sitegroup),
  KEY org_openpsa_products_product_group_i_sid_idx (sid,lang),
  KEY org_openpsa_products_product_group_i_lang_idx (lang)
);
INSERT INTO org_openpsa_products_product_group_i (sid, title, description, sitegroup) SELECT id, title, description, sitegroup FROM org_openpsa_products_product_group;
ALTER TABLE org_openpsa_products_product_group DROP title;
ALTER TABLE org_openpsa_products_product_group DROP description;

