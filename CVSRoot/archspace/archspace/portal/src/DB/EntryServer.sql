DROP TABLE IF EXISTS AllowIP;

CREATE TABLE AllowIP (
	ip char(20) NOT NULL
);

INSERT INTO AllowIP SET ip='127.0.0.1';

DROP TABLE IF EXISTS Users;

CREATE TABLE Users (
	id int(10) NOT NULL PRIMARY KEY AUTO_INCREMENT,
	password char(30) NOT NULL,
	is_admin char(5) NOT NULL,
	name char(30) NOT NULL,
	email char(30) NOT NULL,
	age char(3) NOT NULL,
	sex char(15) NOT NULL,
	country char(30) NOT NULL,
	firstlogin char(30) NOT NULL,
	user_level int(6) NOT NULL DEFAULT 0,
	dev_level int(6) NOT NULL DEFAULT 0,
	ip char(30) NOT NULL
);

DROP TABLE IF EXISTS Rusers;

CREATE TABLE Rusers (
	rflag int(10) NOT NULL,
	pid int(10) NOT NULL
);
