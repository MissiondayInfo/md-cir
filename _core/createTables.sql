CREATE TABLE `agents` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `agentname` varchar(16) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,  
  `faction` varchar(3) COLLATE latin1_general_cs NOT NULL,
  `email` varchar(256) CHARACTER SET utf8 NOT NULL,
  `tgname` varchar(32) CHARACTER SET utf8 NOT NULL,
  `authcode` varchar(8) CHARACTER SET utf8 NOT NULL,
  `reg_time` datetime NOT NULL,
  `edit_time` datetime NOT NULL,
  `md_ciruser` int(3) NOT NULL,
  `md_check` tinyint(2) DEFAULT NULL,
  `md_time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `agentname` (`agentname`),
  UNIQUE KEY `tgname` (`tgname`),
  UNIQUE KEY `authcode` (`authcode`)
) ENGINE=InnoDB AUTO_INCREMENT=1538 DEFAULT CHARSET=latin1 COLLATE=latin1_general_cs;


CREATE TABLE `cirs_users` (
  `uid` int(3) NOT NULL AUTO_INCREMENT,
  `agentname` varchar(15) COLLATE latin1_general_cs NOT NULL,
  `tgname` varchar(32) COLLATE latin1_general_cs NOT NULL,
  `password` varchar(32) COLLATE latin1_general_cs NOT NULL,
  `gid` smallint(2) NOT NULL,
  `sid` varchar(32) COLLATE latin1_general_cs DEFAULT NULL,
  `timestamp` varchar(13) COLLATE latin1_general_cs DEFAULT NULL,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `agent` (`agentname`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1 COLLATE=latin1_general_cs;

INSERT INTO `cirs_users` (`uid`, `agentname`, `tgname`, `password`, `gid`) VALUES
(3, 'TerenceKill', 'TerenceKill', '67e7a6c22399d302b12f9754d0f02dde', 1),
(2, 'dummy', '', '67e7a6c22399d302b12f9754d0f02dde', 1),
(1, 'admin', '', '67e7a6c22399d302b12f9754d0f02dde', 1);

CREATE TABLE `logfile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(3) NOT NULL,
  `task` varchar(32) NOT NULL,
  `note` varchar(1024) NOT NULL,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1307 DEFAULT CHARSET=utf8;
