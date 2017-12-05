-- Create syntax for TABLE 'users'
CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` text DEFAULT NULL,
  `birth` int(11) NOT NULL,
  `first_language` text NOT NULL,
  `other_languages` text DEFAULT NULL,
  `finnish` tinyint(11) NOT NULL,
  `gender` varchar(11) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'words'
CREATE TABLE `words` (
  `user` int(11) NOT NULL,
  `given_word` varchar(25) DEFAULT NULL,
  `recalled_word` varchar(25) DEFAULT NULL,
  `order` int(11) NOT NULL,
  `generation` int(11) NOT NULL,
  `response` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;