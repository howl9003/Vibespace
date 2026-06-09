-- $Horde: horde/scripts/db/sessionhandler_sapdb.sql,v 1.1.2.1 2003/01/16 20:14:49 slusarz Exp $

CREATE TABLE horde_sessionhandler (
    session_id             VARCHAR(32) NOT NULL,
    session_lastmodified   INT NOT NULL,
    session_data           LONG BYTE,
    PRIMARY KEY (session_id)
)
