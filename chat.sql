-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3307
-- Время создания: Авг 05 2023 г., 01:21
-- Версия сервера: 8.0.30
-- Версия PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `chat`
--

-- --------------------------------------------------------

--
-- Структура таблицы `groupChat`
--

CREATE TABLE `groupChat` (
  `id` int NOT NULL,
  `isAdmin` int NOT NULL,
  `nameGroup` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `usersId` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Дамп данных таблицы `groupChat`
--

INSERT INTO `groupChat` (`id`, `isAdmin`, `nameGroup`, `avatar`, `usersId`) VALUES
(1, 1, 'Беседка', '', '1,2,3');

-- --------------------------------------------------------

--
-- Структура таблицы `message`
--

CREATE TABLE `message` (
  `id` int NOT NULL,
  `userIdSend` int NOT NULL,
  `message` text COLLATE utf8mb3_unicode_ci NOT NULL,
  `userIdReceive` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Дамп данных таблицы `message`
--

INSERT INTO `message` (`id`, `userIdSend`, `message`, `userIdReceive`, `datetime`) VALUES
(1, 2, 'Привет', '1', '2023-08-01 18:43:57');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

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

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `name`, `token`, `showemail`, `avatar`, `chatId`, `active`, `friends`) VALUES
(1, 'ilya_ru_94@mail.ru', '$2y$10$HlMyIxIUllVasYxrfXI71.gBKCMBXbqp7nXGisaqvb4tnpzd0VnGu', 'Илья', '6d9b73219c0058e023b823c4aad4d97b570b5ce3ab9fbb37b54cb7a65d1c9834', 1, './uploads/1690907345.jpg', 'Resource id #26', 1, '3,ch1,2'),
(2, 'ilya-mlt@yandex.ru', '$2y$10$MBDfxfi.BonvQmBkJPfGH.1aTQCe94i2taIs0Qn2EN70vBI0fEScC', 'Работа', '64af44fce73761410be719b065bf982dd4874f8b122d824e09a7d0a0b70cf0b0', 0, './uploads/1691105763.jpg', 'Resource id #25', 1, 'ch1,1'),
(3, 'admin@mlt33.ru', '$2y$10$rjrc.iCQyPA3n2xcfqBK6.uHMcTSURFryxJLB0TXJfvXCq.a2qYRy', 'admin', '0c5a59eb8f672ea80d4ff2bd1f0a37b8846fd894b54a64b2db8abb73c1be33c3', 0, './uploads/1691031476.jpg', 'Resource id #27', 1, '1,ch1');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `groupChat`
--
ALTER TABLE `groupChat`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `groupChat`
--
ALTER TABLE `groupChat`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `message`
--
ALTER TABLE `message`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
