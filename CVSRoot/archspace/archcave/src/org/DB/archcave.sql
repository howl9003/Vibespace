
table users (
	id unsigned mediumint NOT NULL,
	player_id unsigned mediumint NOT NULL,
	email varchar(30) NOT NULL,
	name varchar(20) NOT NULL,
	password char(20) NOT NULL,
	verification int unsigned NOT NULL,
	admin_hash char(1) NOT NULL,
	preference_hash char(3) NOT NULL,
	
	PRIMARY KEY (id)
);

table players (
	id unsigned mediumint NOT NULL,
	name varchar(20) NOT NULL,
	
	ruler_id unsigned mediumint NOT NULL,
	
	choices_hash char(5) NOT NULL,
	policies_hash char(2) NOT NULL,
	tech_hash char(50) NOT NULL,
	abilities_hash char(3) NOT NULL,
	era tinyint(3) NOT NULL,
	
	currency int(10) unsigned NOT NULL,
	research_invest int(9) NOT NULL,
	current_research_id smallint(5) NOT NULL,
	research_points int(9) NOT NULL,
	
	honor tinyint(3) DEFAULT '50' NOT NULL,
	security_level tinyint(3) DEFAULT '1' NOT NULL,
	alertness tinyint(3) DEFAULT '0' NOT NULL,
	
	turn int(9) NOT NULL,
	news_turn int(9) DEFAULT '0' NOT NULL,
	news_production int(9) DEFAULT '0' NOT NULL,
	news_research int(9) DEFAULT '0' NOT NULL,
	news_population int(9) DEFAULT '0' NOT NULL,
	
	PRIMARY KEY (id)
);

table territories (
	id int(9) NOT NULL,
	
	type tinyint(3) NOT NULL,
	resources_hash char(2) NOT NULL,
	improvements_hash char(1) NOT NULL,
	
	PRIMARY KEY (id)
);

table inhabitants (
	id int(9) NOT NULL,
	territory_id int(9) NOT NULL,
	owner_id unsigned mediumint NOT NULL,
	old_owner_id unsigned mediumint NOT NULL,
	loyalty tinyint(3) NOT NULL,
	
	name varchar(30) NOT NULL,
	population smallint(5) NOT NULL,
	grain smallint(5) NOT NULL,
	production_points smallint(5) NOT NULL,
	domestic_production_id smallint(5) NOT NULL,
	military_production_id smallint(5) NOT NULL,
	governor_can_build_units tinyint(3) NOT NULL,
	investment int(9) NOT NULL,
	vitality tinyint(3) NOT NULL,
	
	improvements_hash char(4) NOT NULL,
	
	PRIMARY KEY (id)
);

table designs (
	id int(9) NOT NULL,
	owner_id unsigned mediumint NOT NULL,
	name varchar(20) NOT NULL,
	
	chasis tinyint(3) NOT NULL,
	
	weapon1_id smallint(5) NOT NULL,
	weapon2_id smallint(5) NOT NULL,
	weapon3_id smallint(5) NOT NULL,
	
	armor1_id smallint(5) NOT NULL,
	armor2_id smallint(5) NOT NULL,
	armor3_id smallint(5) NOT NULL,
	
	special1_id smallint(5) NOT NULL,
	special2_id smallint(5) NOT NULL,
	special3_id smallint(5) NOT NULL,
	
	PRIMARY KEY (id)
);

table units (
	id int(9) NOT NULL,
	inhabitant_id int(9) NOT NULL,
	owner_id unsigned mediumint NOT NULL,
	
	design_id int(9) NOT NULL,
	quantity tinyint(3) NOT NULL,
	
	PRIMARY KEY (id)
);

table squads (
	id int(9) NOT NULL,
	army_id int(9) NOT NULL,
	name varchar(20) NOT NULL,
	
	design_id int(9) NOT NULL,
	quantity tinyint(3) NOT NULL,
	vitality tinyint(3) NOT NULL,
	hp unsigned mediumint NOT NULL,
	commander_id int(9) NOT NULL,
	experience tinyint(3) NOT NULL,
	moves tinyint(3) NOT NULL,
	
	PRIMARY KEY (id)
);

table heros (
	id int(9) NOT NULL,
	army_id int(9) NOT NULL,
	hero_id unsigned mediumint NOT NULL,
	
	vitality tinyint(3) NOT NULL,
	hp unsigned mediumint NOT NULL,
	moves tinyint(3) NOT NULL,
	
	PRIMARY KEY (id)
);

table armies (
	id int(9) NOT NULL,
	name varchar(20) NOT NULL,
	territory_id int(9) NOT NULL,
	owner_id unsigned mediumint NOT NULL,
	commanding_squad_id int(9) NOT NULL,
	
	PRIMARY KEY (id)
);

table messages (
	id int(9) NOT NULL,
	
	sender_id unsigned mediumint NOT NULL,
	reciever_id unsigned mediumint NOT NULL,
	type tinyint(3) NOT NULL,
	
	content tinytext NOT NULL,
	
	PRIMARY KEY (id)
);

table relations (
	id int(9) NOT NULL,
	
	player1_id unsigned mediumint NOT NULL,
	player2_id unsigned mediumint NOT NULL,
	type tinyint(3) NOT NULL,
	
	PRIMARY KEY (id)
);

table commanders (
	id int(9) NOT NULL,
	owner_id unsigned mediumint NOT NULL,
	name varchar(20) NOT NULL,
	
	level tinyint(3) NOT NULL,
	abilities_hash char(4) NOT NULL,
	experience unsigned mediumint NOT NULL,
	max_command tinyint(3) NOT NULL,
	
	melee tinyint(3) NOT NULL,
	range tinyint(3) NOT NULL,
	barrage tinyint(3) NOT NULL,
	detection tinyint(3) NOT NULL,
	defense tinyint(3) NOT NULL,
	mobility tinyint(3) NOT NULL,
	pillage tinyint(3) NOT NULL,
	plunder tinyint(3) NOT NULL,
	blockade tinyint(3) NOT NULL,
	morale_boost tinyint(3) NOT NULL,
	
	PRIMARY KEY (id)
);

table events (
	id int(9) NOT NULL,
	event_id unsigned mediumint NOT NULL,
	owner_id unsigned mediumint NOT NULL,
	start_time int(9) NOT NULL,
	
	PRIMARY KEY (id)
);

table player_action (
	id int(9) NOT NULL,
	type tinyint(3) NOT NULL,
	owner_id unsigned mediumint NOT NULL,
	
	PRIMARY KEY (id)
);

table battles (
	id int(9) NOT NULL,
	attacker unsigned mediumint NOT NULL,
	defender unsigned mediumint NOT NULL,
	outcome tinyint(3) NOT NULL,
	time int(9) NOT NULL,
	
	PRIMARY KEY (id)
);