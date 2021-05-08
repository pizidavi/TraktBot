--
-- Database TraktBot
--

-- ----------------------------------

--
-- Structure of the table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `intTelegramId` int(11) NOT NULL,
  `intStartMsgId` int(11) DEFAULT NULL,
  `strLanguageCode` varchar(5) DEFAULT 'en',
  `strTimeZone` varchar(5) NOT NULL DEFAULT '0',
  `dtaLastAction` datetime DEFAULT CURRENT_TIMESTAMP,
  `strAccessToken` varchar(70) NOT NULL,
  `strRefreshToken` varchar(70) NOT NULL,
  `intTimeAccessExpires` int(11) DEFAULT NULL,
  `boolDelete` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`intTelegramId`),
  UNIQUE KEY `strAccessToken` (`strAccessToken`),
  UNIQUE KEY `strRefreshToken` (`strRefreshToken`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Structure of the table `elements`
--

CREATE TABLE IF NOT EXISTS `elements` (
  `intTelegramId` int(11) NOT NULL,
  `intTraktId` int(11) NOT NULL,
  `strTraktType` varchar(10) NOT NULL,
  `strTraktTitle` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`intTelegramId`,`intTraktId`,`strTraktType`),
  FOREIGN KEY (`intTelegramId`) REFERENCES users(`intTelegramId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------------
