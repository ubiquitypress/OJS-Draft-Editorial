CREATE TABLE `draft_decisions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_val` varchar(45) NOT NULL,
  `senior_editor_id` int(11) NOT NULL,
  `junior_editor_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `decision` int(11) NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `body` text,
  `note` text,
  `attatchment` text,
  `status` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
