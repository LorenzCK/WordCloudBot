SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE `status` (
  `user_id` int(11) NOT NULL,
  `state` smallint(6) NOT NULL DEFAULT '0',
  `lat` float DEFAULT NULL,
  `lng` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `word_votes` (
  `user_id` int(11) NOT NULL,
  `word` varchar(255) NOT NULL,
  `lat` float NOT NULL,
  `lng` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `status`
  ADD PRIMARY KEY (`user_id`);

ALTER TABLE `word_votes`
  ADD KEY `position` (`lat`,`lng`),
  ADD KEY `user_id` (`user_id`);
