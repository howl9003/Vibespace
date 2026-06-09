-- $Horde: horde/scripts/db/category.sql,v 1.2.2.9 2002/08/23 00:30:11 jan Exp $

CREATE TABLE horde_categories (
       category_id INT NOT NULL,
       group_uid VARCHAR(255) NOT NULL,
       user_uid VARCHAR(255) NOT NULL,
       category_name VARCHAR(255) NOT NULL,
       category_parents VARCHAR(255) NOT NULL,
-- There is no portable way to do this apparently. If your db doesn't allow varchars
-- greater than 255 characters, then maybe it allows TEXT columns, so try the second
-- line.
       category_data VARCHAR(2048),
--     category_data TEXT,
       category_serialized SMALLINT DEFAULT 0 NOT NULL,
       category_updated TIMESTAMP,
       PRIMARY KEY (category_id)
);

CREATE INDEX category_category_name_idx ON horde_categories (category_name);
CREATE INDEX category_group_idx ON horde_categories (group_uid);
CREATE INDEX category_user_idx ON horde_categories (user_uid);
CREATE INDEX category_serialized_idx ON horde_categories (category_serialized);

GRANT SELECT, INSERT, UPDATE, DELETE ON horde_categories TO horde;
