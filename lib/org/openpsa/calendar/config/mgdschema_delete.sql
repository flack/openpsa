#Removing columns created for org.openpsa.contacts
## org_openpsa_event
ALTER TABLE event DROP COLUMN location;
ALTER TABLE event DROP COLUMN tentative;
ALTER TABLE event DROP COLUMN externalGuid;
ALTER TABLE event DROP COLUMN vCalSerialized;
ALTER TABLE event DROP COLUMN orgOpenpsaObtype;
ALTER TABLE event DROP COLUMN orgOpenpsaWgtype;
ALTER TABLE event DROP COLUMN orgOpenpsaAccesstype;
ALTER TABLE event DROP COLUMN orgOpenpsaOwnerWg;
ALTER TABLE eventmember DROP COLUMN orgOpenpsaObtype;
ALTER TABLE eventmember DROP COLUMN sendNotes;
ALTER TABLE eventmember DROP COLUMN hoursReported;


