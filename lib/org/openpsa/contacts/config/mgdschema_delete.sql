#Removing columns created for org.openpsa.contacts
## org_openpsa_organization
ALTER TABLE grp DROP COLUMN country;
ALTER TABLE grp DROP COLUMN phone;
ALTER TABLE grp DROP COLUMN fax;
ALTER TABLE grp DROP COLUMN postalStreet;
ALTER TABLE grp DROP COLUMN postalPostcode;
ALTER TABLE grp DROP COLUMN postalCity;
ALTER TABLE grp DROP COLUMN postalCountry;
ALTER TABLE grp DROP COLUMN invoiceStreet;
ALTER TABLE grp DROP COLUMN invoicePostcode;
ALTER TABLE grp DROP COLUMN invoiceCity;
ALTER TABLE grp DROP COLUMN invoiceCountry;
ALTER TABLE grp DROP COLUMN keywords;
ALTER TABLE grp DROP COLUMN customerId;
ALTER TABLE grp DROP COLUMN orgOpenpsaObtype;
ALTER TABLE grp DROP COLUMN orgOpenpsaWgtype;
ALTER TABLE grp DROP COLUMN orgOpenpsaAccesstype;

## org_openpsa_person
ALTER TABLE person DROP COLUMN country;
ALTER TABLE person DROP COLUMN fax;
ALTER TABLE person DROP COLUMN orgOpenpsaObtype;
ALTER TABLE person DROP COLUMN orgOpenpsaWgtype;
ALTER TABLE person DROP COLUMN orgOpenpsaAccesstype;


