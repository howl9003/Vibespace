-- $Horde: horde/scripts/db/auth.sql,v 1.5.2.2 2002/04/18 22:12:25 jan Exp $

CREATE TABLE horde_users (
    user_uid       varchar(255) not null,
    user_pass      varchar(32) not null,
    primary key (user_uid)
);

GRANT SELECT, INSERT, UPDATE, DELETE ON horde_users TO horde;
