CREATE DATABASE wfido;
GRANT ALL PRIVILEGES ON wfido.* TO wfido@localhost IDENTIFIED BY 'PASSWORD';

USE wfido;


CREATE TABLE `area_groups` (
  `area` varchar(128) NOT NULL DEFAULT '',
  `group` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `area` (`area`),
  KEY `group` (`group`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `areas` (
  `area` varchar(128) NOT NULL DEFAULT '',
  `recieved` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `messages` bigint(20) NOT NULL DEFAULT '0',
  `description` varchar(256) DEFAULT NULL,
  UNIQUE KEY `area` (`area`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `default` (
  `key` varchar(64) DEFAULT NULL,
  `value` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `default_perm` (
  `group` bigint(20) NOT NULL DEFAULT '0',
  `perm` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `default_subscribe` (
  `group` bigint(20) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `favorites` (
  `id` int(255) NOT NULL DEFAULT '0',
  `point` int(64) NOT NULL DEFAULT '0',
  `message` varchar(64) NOT NULL DEFAULT '',
  `uniq_index` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`uniq_index`),
  KEY `point` (`point`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;

CREATE TABLE `messages` (
  `id` bigint(64) NOT NULL AUTO_INCREMENT,
  `fromname` varchar(255) NOT NULL DEFAULT '',
  `fromaddr` text NOT NULL,
  `toname` varchar(200) DEFAULT NULL,
  `toaddr` text NOT NULL,
  `area` varchar(128) NOT NULL DEFAULT '',
  `subject` text NOT NULL,
  `text` longtext NOT NULL,
  `pktfrom` text NOT NULL,
  `date` text NOT NULL,
  `attr` blob NOT NULL,
  `secure` text NOT NULL,
  `msgid` varchar(128) NOT NULL DEFAULT '',
  `reply` varchar(128) NOT NULL DEFAULT '',
  `hash` varchar(64) NOT NULL DEFAULT '',
  `recieved` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `thread` varchar(128) NOT NULL DEFAULT '',
  `level` bigint(20) DEFAULT NULL,
  `inthread` bigint(20) DEFAULT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `hash` (`hash`),
  UNIQUE KEY `area_id_index` (`area`,`id`),
  KEY `thread` (`thread`),
  KEY `area` (`area`),
  KEY `fromname` (`fromname`),
  KEY `toname` (`toname`),
  KEY `inthread` (`inthread`),
  KEY `msgid` (`msgid`),
  KEY `reply` (`reply`),
  KEY `area_toname` (`area`,`toname`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE `outbox` (
  `id` bigint(64) NOT NULL AUTO_INCREMENT,
  `fromname` text NOT NULL,
  `toname` text NOT NULL,
  `subject` text NOT NULL,
  `text` longtext NOT NULL,
  `fromaddr` text NOT NULL,
  `toaddr` text NOT NULL,
  `origin` text NOT NULL,
  `area` text NOT NULL,
  `reply` varchar(128) NOT NULL DEFAULT '',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `hash` varchar(64) NOT NULL DEFAULT '',
  `sent` tinyint(1) DEFAULT '0',
  `aprove` binary(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `hash` (`hash`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE `sessions` (
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `ip` varchar(100) NOT NULL DEFAULT '',
  `id` int(128) NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `point` bigint(64) NOT NULL DEFAULT '0',
  `sessionid` varchar(100) NOT NULL DEFAULT '',
  `browser` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE `sphinx_counter` (
  `counter_id` int(11) NOT NULL,
  `max_id` int(11) NOT NULL,
  PRIMARY KEY (`counter_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `subscribe` (
  `point` bigint(20) NOT NULL DEFAULT '0',
  `area` varchar(128) NOT NULL DEFAULT '',
  `subscribed` tinyint(1) NOT NULL DEFAULT '0',
  KEY `point` (`point`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `threads` (
  `area` varchar(128) NOT NULL DEFAULT '',
  `thread` varchar(128) NOT NULL DEFAULT '',
  `hash` varchar(128) NOT NULL DEFAULT '',
  `subject` text NOT NULL,
  `author` varchar(128) NOT NULL DEFAULT '',
  `author_address` varchar(128) NOT NULL DEFAULT '',
  `author_date` varchar(128) NOT NULL DEFAULT '',
  `last_author` varchar(128) NOT NULL DEFAULT '',
  `last_author_address` varchar(128) NOT NULL DEFAULT '',
  `last_author_date` varchar(128) NOT NULL DEFAULT '',
  `num` bigint(20) NOT NULL DEFAULT '0',
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `area_2` (`area`,`thread`),
  KEY `area` (`area`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `user_groups` (
  `point` int(64) NOT NULL DEFAULT '0',
  `group` int(11) NOT NULL DEFAULT '0',
  `perm` int(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `point_2` (`point`,`group`),
  KEY `point` (`point`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `users` (
  `point` int(64) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `email` text NOT NULL,
  `password` text NOT NULL,
  `origin` text NOT NULL,
  `limit` bigint(20) NOT NULL DEFAULT '0',
  `close_old_session` tinyint(1) NOT NULL DEFAULT '1',
  `ajax` tinyint(1) NOT NULL DEFAULT '0',
  `registred` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastlog` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `jid` varchar(255) NOT NULL DEFAULT '',
  `confirm` varchar(64) NOT NULL DEFAULT '',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `scale_img` tinyint(1) NOT NULL DEFAULT '0',
  `scale_value` bigint(20) NOT NULL DEFAULT '1000',
  `media_disabled` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`point`)
) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=utf8;

CREATE TABLE `view` (
  `point` int(64) NOT NULL DEFAULT '0',
  `area` varchar(128) NOT NULL DEFAULT '',
  `last_view_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_view_message` varchar(64) NOT NULL DEFAULT '',
  UNIQUE KEY `point` (`point`,`area`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `view_thread` (
  `point` bigint(20) NOT NULL DEFAULT '0',
  `area` varchar(128) NOT NULL DEFAULT '',
  `thread` varchar(128) NOT NULL DEFAULT '',
  `last_view_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  UNIQUE KEY `point` (`point`,`area`,`thread`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `area_groups` SET `group` = '1', `area` = 'NETMAIL';
INSERT INTO `default` VALUES ('origin','default origin');
INSERT INTO `default_perm` VALUES (1,3);
INSERT INTO `groups` VALUES (1,'NETMAIL');
INSERT INTO `users` (`point`, `name`, `email`, `password`, `origin`, `limit`, `close_old_session`, `ajax`, `registred`, `lastlog`, `jid`, `confirm`, `active`) VALUES ('1', 'Sysop', 'sysop@localhost', 'PASSWORD', 'Мой оригинальный ориджин', '100', '0', '1', '2013-01-30 00:00:00', '0000-00-00 00:00:00', '', '', '1');
insert into `user_groups` set `point`=1, `group`=1, `perm`=3;
insert into `sphinx_counter` SET `counter_id`=0;

-- ALTER TABLE `messages` ADD FULLTEXT KEY `text` (`text`);