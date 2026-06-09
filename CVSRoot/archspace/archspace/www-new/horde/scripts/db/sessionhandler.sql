-- $Horde: horde/scripts/db/sessionhandler.sql,v 1.2.2.1 2003/01/16 20:14:49 slusarz Exp $

CREATE TABLE horde_sessionhandler (
    session_id             VARCHAR(32) NOT NULL,
    session_lastmodified   INT NOT NULL,
    session_data           LONGBLOB,
-- Or, on some DBMS systems:
--  session_data           IMAGE,

    PRIMARY KEY (session_id)
);

GRANT SELECT, INSERT, UPDATE, DELETE ON horde_sessionhandler TO horde;
