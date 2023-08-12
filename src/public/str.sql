SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
CREATE TABLE `groupChat` (
  `id` int NOT NULL,
  `isAdmin` int NOT NULL,
  `nameGroup` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `usersId` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
CREATE TABLE `message` (
  `id` int NOT NULL,
  `userIdSend` int NOT NULL,
  `message` text COLLATE utf8mb3_unicode_ci NOT NULL,
  `userIdReceive` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
CREATE TABLE `users` (
  `id` int NOT NULL,
  `email` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `token` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `showemail` int DEFAULT '0',
  `avatar` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `chatId` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `active` int DEFAULT '0',
  `friends` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
ALTER TABLE `groupChat`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `message`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);
ALTER TABLE `groupChat`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `message`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
COMMIT;
