CREATE TABLE `news` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
    `short_text` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
    `full_text` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
    `is_deleted` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `news`
    ADD PRIMARY KEY (`id`);
