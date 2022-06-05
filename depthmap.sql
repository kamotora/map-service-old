SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `depthmap`
--

DELIMITER $$
--
-- Функции
--
CREATE DEFINER=`root`@`127.0.0.1` FUNCTION `parse_dms` (`dms` TEXT) RETURNS DECIMAL(10,6) NO SQL
BEGIN
DECLARE result DECIMAL(10,6) DEFAULT 0.0;
DECLARE degs DECIMAL(10.6) DEFAULT 0.0;
DECLARE mins DECIMAL(10.6) DEFAULT 0.0;
DECLARE secs DECIMAL(10.6) DEFAULT 0.0;
IF REGEXP_SUBSTR(dms, '[0-9]+') IS NOT NULL
THEN
SET degs = CONVERT(REGEXP_SUBSTR(dms, '[0-9]+'), DECIMAL(10,6));
IF REGEXP_SUBSTR(dms, '[0-9]+', 1, 2) IS NOT NULL
THEN
SET mins = CONVERT(REGEXP_SUBSTR(dms, '[0-9]+', 1, 2), DECIMAL(10,6));
IF REGEXP_SUBSTR(dms, '[0-9]+', 1, 3) IS NOT NULL
THEN
SET secs = CONVERT(REGEXP_SUBSTR(dms, '[0-9]+', 1, 3), DECIMAL(10,6));
END IF;
END IF;
SET result = (IFNULL(degs, 0.0) + IFNULL(mins, 0.0) / 60.0 + IFNULL(secs, 0.0) / 3600.0);
ELSE
SET result = NULL;
END IF;
RETURN result;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `images`
--

CREATE TABLE `images` (
  `id` int NOT NULL,
  `image_path` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `uploaded_by` int NOT NULL,
  `sheet_code` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `point_nw_x` int NOT NULL DEFAULT '0',
  `point_nw_y` int NOT NULL DEFAULT '0',
  `point_ne_x` int NOT NULL DEFAULT '0',
  `point_ne_y` int NOT NULL DEFAULT '0',
  `point_sw_x` int NOT NULL DEFAULT '0',
  `point_sw_y` int NOT NULL DEFAULT '0',
  `point_se_x` int NOT NULL DEFAULT '0',
  `point_se_y` int NOT NULL DEFAULT '0',
  `north` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `south` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `west` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `east` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `status` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `created_at` int NOT NULL,
  `updated_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `image_problem`
--

CREATE TABLE `image_problem` (
  `id` int NOT NULL,
  `image_id` int NOT NULL,
  `north` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `south` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `west` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `east` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `image_set`
--

CREATE TABLE `image_set` (
  `id` int NOT NULL,
  `created_by` int NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `image_set_record`
--

CREATE TABLE `image_set_record` (
  `id` int NOT NULL,
  `set_id` int NOT NULL,
  `image_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `user`
--

CREATE TABLE `user` (
  `id` int NOT NULL,
  `username` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `full_name` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `password_hash` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `password_reset_token` text CHARACTER SET utf8 COLLATE utf8_general_ci,
  `auth_key` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `email` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `user_role` int NOT NULL,
  `created_at` int NOT NULL,
  `updated_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `images`
--
ALTER TABLE `images`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `image_path` (`image_path`(255)),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Индексы таблицы `image_problem`
--
ALTER TABLE `image_problem`
  ADD PRIMARY KEY (`id`),
  ADD KEY `image_id` (`image_id`);

--
-- Индексы таблицы `image_set`
--
ALTER TABLE `image_set`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Индексы таблицы `image_set_record`
--
ALTER TABLE `image_set_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `set_id` (`set_id`),
  ADD KEY `image_id` (`image_id`);

--
-- Индексы таблицы `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`(256)),
  ADD UNIQUE KEY `user_email` (`email`(256)),
  ADD UNIQUE KEY `password_reset_token` (`password_reset_token`(32));

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `images`
--
ALTER TABLE `images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT для таблицы `image_problem`
--
ALTER TABLE `image_problem`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT для таблицы `image_set`
--
ALTER TABLE `image_set`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `image_set_record`
--
ALTER TABLE `image_set_record`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT для таблицы `user`
--
ALTER TABLE `user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `images`
--
ALTER TABLE `images`
  ADD CONSTRAINT `images_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `image_problem`
--
ALTER TABLE `image_problem`
  ADD CONSTRAINT `image_problem_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `images` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `image_set`
--
ALTER TABLE `image_set`
  ADD CONSTRAINT `image_set_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `image_set_record`
--
ALTER TABLE `image_set_record`
  ADD CONSTRAINT `image_set_record_ibfk_1` FOREIGN KEY (`set_id`) REFERENCES `image_set` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `image_set_record_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `images` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
