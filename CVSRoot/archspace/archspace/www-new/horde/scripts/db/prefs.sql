-- $Horde: horde/scripts/db/prefs.sql,v 1.3.2.3 2002/03/11 19:31:36 chuck Exp $

create table horde_prefs (
    pref_uid        char(255) not null,
    pref_scope      char(16) not null default '',
    pref_name       char(32) not null,
    pref_value      text,
    primary key (pref_uid, pref_scope, pref_name)
);

grant select, insert, update, delete on horde_prefs to horde;
