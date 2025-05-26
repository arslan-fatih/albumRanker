-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 26 May 2025, 22:04:36
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `album_ranker`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `albums`
--

CREATE TABLE `albums` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `artist` varchar(255) NOT NULL,
  `cover_image` varchar(255) NOT NULL,
  `wiki_url` varchar(255) DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `genre` varchar(50) NOT NULL DEFAULT 'Diğer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `albums`
--

INSERT INTO `albums` (`id`, `title`, `artist`, `cover_image`, `wiki_url`, `release_date`, `description`, `rating`, `user_id`, `created_at`, `updated_at`, `genre`) VALUES
(2, 'ExampleRecord', 'Tester123', 'https://cdn.pixabay.com/photo/2024/02/26/19/51/guitar-8598823_1280.jpg', '', '0001-01-01', 'This is a test album', NULL, 1, '2025-05-24 15:35:48', '2025-05-24 15:39:28', 'Diğer');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `album_genres`
--

CREATE TABLE `album_genres` (
  `album_id` int(11) NOT NULL,
  `genre_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `album_genres`
--

INSERT INTO `album_genres` (`album_id`, `genre_id`) VALUES
(2, 97),
(2, 116),
(2, 179);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `favorites`
--

CREATE TABLE `favorites` (
  `user_id` int(11) NOT NULL,
  `album_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `favorites`
--

INSERT INTO `favorites` (`user_id`, `album_id`, `created_at`) VALUES
(3, 2, '2025-05-25 15:38:19');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `followers`
--

CREATE TABLE `followers` (
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `genres`
--

CREATE TABLE `genres` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `genres`
--

INSERT INTO `genres` (`id`, `name`) VALUES
(192, 'A cappella'),
(97, 'Acid Jazz'),
(197, 'Acoustic'),
(22, 'Acoustic Blues'),
(151, 'Afrobeat'),
(1, 'Alternative'),
(136, 'Alternative Country'),
(89, 'Alternative Hip Hop'),
(116, 'Alternative Metal'),
(3, 'Alternative Rock'),
(73, 'Ambient'),
(137, 'Americana'),
(179, 'Anime'),
(2, 'Art Punk'),
(42, 'Avant-Garde'),
(98, 'Avant-Garde Jazz'),
(193, 'Barbershop'),
(43, 'Baroque'),
(99, 'Bebop'),
(100, 'Big Band'),
(117, 'Black Metal'),
(138, 'Bluegrass'),
(21, 'Blues'),
(171, 'Blues Gospel'),
(23, 'Blues Rock'),
(163, 'Bolero'),
(101, 'Bossa Nova'),
(57, 'Breakbeat'),
(4, 'Britpunk'),
(58, 'Brostep'),
(152, 'Celtic'),
(44, 'Chamber Music'),
(194, 'Chant'),
(24, 'Chicago Blues'),
(173, 'Children's Music'),
(59, 'Chillstep'),
(45, 'Choral'),
(172, 'Christian'),
(182, 'Christmas'),
(25, 'Classic Blues'),
(41, 'Classical'),
(46, 'Classical Crossover'),
(56, 'Club Dance'),
(5, 'College Rock'),
(174, 'Comedy'),
(26, 'Contemporary Blues'),
(47, 'Contemporary Classical'),
(139, 'Contemporary Country'),
(146, 'Contemporary Folk'),
(165, 'Contemporary R&B'),
(102, 'Cool Jazz'),
(135, 'Country'),
(27, 'Country Blues'),
(140, 'Country Pop'),
(141, 'Country Rock'),
(103, 'Crossover Jazz'),
(6, 'Crossover Thrash'),
(7, 'Crust Punk'),
(162, 'Cumbia'),
(55, 'Dance'),
(80, 'Dance Pop'),
(118, 'Death Metal'),
(60, 'Deep House'),
(28, 'Delta Blues'),
(169, 'Disco'),
(104, 'Dixieland'),
(119, 'Doom Metal'),
(74, 'Downtempo'),
(71, 'Drum and Bass'),
(61, 'Dubstep'),
(90, 'East Coast Hip Hop'),
(185, 'Easter'),
(189, 'Easy Listening'),
(29, 'Electric Blues'),
(62, 'Electro House'),
(72, 'Electronic'),
(81, 'Electropop'),
(63, 'Electroswing'),
(8, 'Emotional Hardcore'),
(9, 'Experimental Rock'),
(153, 'Flamenco'),
(145, 'Folk'),
(30, 'Folk Blues'),
(120, 'Folk Metal'),
(10, 'Folk Punk'),
(147, 'Folk Rock'),
(105, 'Free Jazz'),
(168, 'Funk'),
(106, 'Fusion'),
(91, 'Gangsta Rap'),
(64, 'Garage'),
(121, 'Glam Metal'),
(65, 'Glitch Hop'),
(170, 'Gospel'),
(31, 'Gospel Blues'),
(11, 'Goth Rock'),
(122, 'Gothic Metal'),
(66, 'Grime'),
(123, 'Grindcore'),
(124, 'Groove Metal'),
(12, 'Grunge'),
(107, 'Gypsy Jazz'),
(184, 'Halloween'),
(183, 'Hanukkah'),
(108, 'Hard Bop'),
(67, 'Hard Dance'),
(14, 'Hard Rock'),
(92, 'Hardcore Hip Hop'),
(13, 'Hardcore Punk'),
(125, 'Heavy Metal'),
(48, 'High Classical'),
(88, 'Hip Hop'),
(181, 'Holiday'),
(142, 'Honky Tonk'),
(68, 'House'),
(75, 'IDM'),
(148, 'Indie Folk'),
(82, 'Indie Pop'),
(15, 'Indie Rock'),
(76, 'Industrial'),
(126, 'Industrial Metal'),
(196, 'Instrumental'),
(84, 'J-Pop'),
(96, 'Jazz'),
(32, 'Jazz Blues'),
(33, 'Jump Blues'),
(83, 'K-Pop'),
(34, 'Kansas City Blues'),
(180, 'Karaoke'),
(156, 'Latin'),
(109, 'Latin Jazz'),
(16, 'Lo-fi'),
(190, 'Lounge'),
(187, 'March'),
(35, 'Memphis Blues'),
(161, 'Merengue'),
(115, 'Metal'),
(127, 'Metalcore'),
(188, 'Military'),
(110, 'Modal Jazz'),
(36, 'Modern Blues'),
(176, 'Musical'),
(166, 'Neo Soul'),
(17, 'New Wave'),
(200, 'Noise'),
(128, 'Nu Metal'),
(93, 'Old School Hip Hop'),
(49, 'Opera'),
(50, 'Orchestral'),
(143, 'Outlaw Country'),
(186, 'Patriotic'),
(198, 'Piano'),
(37, 'Piano Blues'),
(79, 'Pop'),
(86, 'Pop Rock'),
(129, 'Power Metal'),
(87, 'Power Pop'),
(130, 'Progressive Metal'),
(18, 'Progressive Rock'),
(19, 'Punk'),
(164, 'R&B'),
(111, 'Ragtime'),
(94, 'Rap'),
(154, 'Reggae'),
(159, 'Reggaeton'),
(51, 'Renaissance'),
(52, 'Romantic'),
(160, 'Salsa'),
(157, 'Samba'),
(20, 'Shoegaze'),
(149, 'Singer-Songwriter'),
(155, 'Ska'),
(131, 'Sludge Metal'),
(112, 'Smooth Jazz'),
(167, 'Soul'),
(38, 'Soul Blues'),
(201, 'Sound Art'),
(175, 'Soundtrack'),
(132, 'Speed Metal'),
(177, 'Stage & Screen'),
(199, 'String Quartet'),
(113, 'Swing'),
(53, 'Symphonic'),
(133, 'Symphonic Metal'),
(54, 'Symphony'),
(77, 'Synthpop'),
(158, 'Tango'),
(69, 'Techno'),
(85, 'Teen Pop'),
(39, 'Texas Blues'),
(134, 'Thrash Metal'),
(144, 'Traditional Country'),
(70, 'Trance'),
(78, 'Trap'),
(40, 'Urban Blues'),
(178, 'Video Game Music'),
(191, 'Vocal'),
(114, 'Vocal Jazz'),
(195, 'Vocal Pop'),
(95, 'West Coast Hip Hop'),
(150, 'World');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `album_id` int(11) DEFAULT NULL,
  `rating` decimal(3,1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `ratings`
--

INSERT INTO `ratings` (`id`, `user_id`, `album_id`, `rating`, `created_at`, `updated_at`) VALUES
(3, 1, 2, 10.0, '2025-05-24 15:35:48', '2025-05-24 15:35:48'),
(4, 3, 2, 1.2, '2025-05-24 15:40:20', '2025-05-24 15:40:20');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `album_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `album_id`, `content`, `created_at`, `updated_at`) VALUES
(2, 3, 2, 'This is a test comment', '2025-05-24 15:40:20', '2025-05-24 15:40:20'),
(3, 1, 2, 'asdsdas', '2025-05-25 11:34:24', '2025-05-25 11:34:24'),
(4, 1, 2, 'asdasdasd', '2025-05-25 11:35:36', '2025-05-25 11:35:36'),
(5, 1, 2, '123123', '2025-05-25 11:35:43', '2025-05-25 11:35:43'),
(6, 3, 2, 'asdadasd', '2025-05-25 11:36:55', '2025-05-25 11:36:55'),
(7, 1, 2, 'asdasd', '2025-05-25 14:16:12', '2025-05-25 14:16:12');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `review_likes`
--

CREATE TABLE `review_likes` (
  `user_id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `review_likes`
--

INSERT INTO `review_likes` (`user_id`, `review_id`, `created_at`) VALUES
(1, 2, '2025-05-26 18:35:06'),
(1, 3, '2025-05-26 18:35:06'),
(1, 4, '2025-05-26 18:35:27'),
(1, 5, '2025-05-26 18:35:27'),
(1, 6, '2025-05-26 18:35:28'),
(1, 7, '2025-05-26 18:35:25'),
(3, 2, '2025-05-25 14:23:34'),
(3, 3, '2025-05-25 15:38:16'),
(3, 4, '2025-05-25 14:24:42'),
(3, 5, '2025-05-25 14:26:00'),
(3, 6, '2025-05-25 15:36:28'),
(3, 7, '2025-05-25 15:36:27');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tracks`
--

CREATE TABLE `tracks` (
  `id` int(11) NOT NULL,
  `album_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `duration` int(11) NOT NULL,
  `track_number` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_pic` varchar(255) DEFAULT 'default.jpg',
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `profile_pic`, `bio`, `created_at`, `updated_at`) VALUES
(1, 'TesterDude', 'test@example.com', '$2y$10$wYpV7GRjVgz7ZMMurjJLvenH1WTlVC5KiPNKW1ibXKjNjKmdgXMMS', 'b554b8e9354a5530.jpg', 'asdasd', '2025-05-24 14:23:09', '2025-05-25 15:44:53'),
(3, 'Tester2', 'test2@example.com', '$2y$10$eR5LhySJJ8J9wRrhzmAS/e1AE4SAAef6ukyBrSx/JJq3O9e.iVfMG', 'default_profile.jpg', NULL, '2025-05-24 15:39:56', '2025-05-24 15:54:00');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `albums`
--
ALTER TABLE `albums`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_albums_user` (`user_id`);

--
-- Tablo için indeksler `album_genres`
--
ALTER TABLE `album_genres`
  ADD PRIMARY KEY (`album_id`,`genre_id`),
  ADD KEY `genre_id` (`genre_id`);

--
-- Tablo için indeksler `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`user_id`,`album_id`),
  ADD KEY `idx_favorites_user` (`user_id`),
  ADD KEY `idx_favorites_album` (`album_id`);

--
-- Tablo için indeksler `followers`
--
ALTER TABLE `followers`
  ADD PRIMARY KEY (`follower_id`,`following_id`),
  ADD KEY `following_id` (`following_id`);

--
-- Tablo için indeksler `genres`
--
ALTER TABLE `genres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Tablo için indeksler `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `album_id` (`album_id`);

--
-- Tablo için indeksler `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `album_id` (`album_id`);

--
-- Tablo için indeksler `review_likes`
--
ALTER TABLE `review_likes`
  ADD PRIMARY KEY (`user_id`,`review_id`),
  ADD KEY `review_id` (`review_id`);

--
-- Tablo için indeksler `tracks`
--
ALTER TABLE `tracks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `album_id` (`album_id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `albums`
--
ALTER TABLE `albums`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `genres`
--
ALTER TABLE `genres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202;

--
-- Tablo için AUTO_INCREMENT değeri `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `tracks`
--
ALTER TABLE `tracks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `albums`
--
ALTER TABLE `albums`
  ADD CONSTRAINT `fk_albums_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `album_genres`
--
ALTER TABLE `album_genres`
  ADD CONSTRAINT `fk_album_genres_album` FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_album_genres_genre` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `fk_favorites_album` FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_favorites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `followers`
--
ALTER TABLE `followers`
  ADD CONSTRAINT `fk_followers_follower` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_followers_following` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `fk_ratings_album` FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ratings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_album` FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `review_likes`
--
ALTER TABLE `review_likes`
  ADD CONSTRAINT `review_likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_likes_ibfk_2` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `tracks`
--
ALTER TABLE `tracks`
  ADD CONSTRAINT `fk_tracks_album` FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
