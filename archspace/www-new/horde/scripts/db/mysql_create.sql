# $Horde: horde/scripts/db/mysql_create.sql,v 1.1.2.9 2002/09/26 21:19:26 jan Exp $
#
# If you are installing Horde for the first time, you can simply
# direct this file to mysql as STDIN:
#
# $ mysql --user=root --password=<MySQL-root-password> < mysql_create.sql
#
# If you are upgrading from a previous version, you will need to comment
# out the the user creation steps below, as well as the schemas for any
# tables that already exist.
#
# If you are upgrading from Horde 1.x, the Horde tables you have from
# that version are no longer used; you may wish to either delete those
# tables or simply recreate the database anew.

USE mysql;

REPLACE INTO user (host, user, password)
    VALUES (
        'localhost',
        'horde',
  -- IMPORTANT: Change this password!
        PASSWORD('horde')
    );

REPLACE INTO db (host, db, user, select_priv, insert_priv, update_priv,
                 delete_priv, create_priv, drop_priv)
    VALUES (
        'localhost',
        'horde',
        'horde',
        'Y', 'Y', 'Y', 'Y',
        'Y', 'Y'
    );

FLUSH PRIVILEGES;

# MySQL 3.23.x appears to have "CREATE DATABASE IF NOT EXISTS" and
# "CREATE TABLE IF NOT EXISTS" which would be a nice way to handle
# reinstalls gracefully (someday).  For now, use mysql_drop.sql first
# to avoid CREATE errors.

CREATE DATABASE horde;

USE horde;

CREATE TABLE horde_users (
    user_uid       VARCHAR(255) NOT NULL,
    user_pass      VARCHAR(32) NOT NULL,
    PRIMARY KEY (user_uid)
);

GRANT SELECT, INSERT, UPDATE, DELETE ON horde_users TO horde@localhost;

CREATE TABLE horde_prefs (
    pref_uid        CHAR(255) NOT NULL,
    pref_scope      CHAR(16) NOT NULL DEFAULT '',
    pref_name       CHAR(32) NOT NULL,
    pref_value      LONGTEXT NULL,
    PRIMARY KEY (pref_uid, pref_scope, pref_name)
);

GRANT SELECT, INSERT, UPDATE, DELETE ON horde_prefs TO horde@localhost;

CREATE TABLE horde_categories (
       category_id INT NOT NULL,
       group_uid VARCHAR(255) NOT NULL,
       user_uid VARCHAR(255) NOT NULL,
       category_name VARCHAR(255) NOT NULL,
       category_parents VARCHAR(255) NOT NULL,
       category_data TEXT,
       category_serialized SMALLINT DEFAULT 0 NOT NULL,
       category_updated TIMESTAMP,
       PRIMARY KEY (category_id)
);

CREATE INDEX category_category_name_idx ON horde_categories (category_name);
CREATE INDEX category_group_idx ON horde_categories (group_uid);
CREATE INDEX category_user_idx ON horde_categories (user_uid);
CREATE INDEX category_serialized_idx ON horde_categories (category_serialized);

GRANT SELECT, INSERT, UPDATE, DELETE ON horde_categories TO horde@localhost;

FLUSH PRIVILEGES;

# Done!
