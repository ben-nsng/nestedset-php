CREATE TABLE IF NOT EXISTS `tree` (
`id` int(11) NOT NULL,
  `label` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `lft` int(11) NOT NULL,
  `rht` int(11) NOT NULL,
  `lvl` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `tree` (`id`, `label`, `lft`, `rht`, `lvl`, `parent_id`) VALUES
(1, 'root', 1, 48, 0, NULL),
(2, 'new_node', 36, 39, 3, 11),
(3, 'new_node', 5, 6, 4, 7),
(4, 'new_node', 24, 27, 5, 8),
(5, 'new_node', 9, 14, 2, 6),
(6, 'new_node', 2, 45, 1, 1),
(7, 'new_node', 4, 7, 3, 16),
(8, 'new_node', 17, 34, 4, 14),
(9, 'new_node', 10, 11, 3, 5),
(10, 'new_node', 18, 19, 5, 8),
(11, 'new_node', 15, 40, 2, 6),
(12, 'new_node', 32, 33, 5, 8),
(13, 'new_node', 41, 44, 2, 6),
(14, 'new_node', 16, 35, 3, 11),
(15, 'new_node', 21, 22, 6, 17),
(16, 'new_node', 3, 8, 2, 6),
(17, 'new_node', 20, 23, 5, 8),
(18, 'new_node', 46, 47, 1, 1),
(19, 'new_node', 12, 13, 3, 5),
(20, 'new_node', 25, 26, 6, 4),
(21, 'new_node', 28, 31, 5, 8),
(22, 'new_node', 29, 30, 6, 21),
(23, 'new_node', 42, 43, 3, 13),
(24, 'new_node', 37, 38, 4, 2);