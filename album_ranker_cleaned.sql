
DROP DATABASE IF EXISTS album_ranker;
CREATE DATABASE album_ranker;
USE album_ranker;
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 23 May 2025, 20:30:57
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
(143, 'Olsun', 'Pilli Bebek', 'https://upload.wikimedia.org/wikipedia/tr/thumb/d/d6/Pilli_Bebek_-_Olsun.jpg/500px-Pilli_Bebek_-_Olsun.jpg', 'https://tr.wikipedia.org/wiki/Olsun_(alb%C3%BCm)', NULL, 'Çok beğendik', NULL, 3, '2025-05-21 23:01:19', '2025-05-21 23:01:19', 'Diğer'),
(144, 'Müptezhel', 'Ezhel', 'https://cdn-images.dzcdn.net/images/cover/35d715873d3e8ae76a998f6bf38e1fa8/1900x1900-000000-80-0-0.jpg', 'https://tr.wikipedia.org/wiki/M%C3%BCptezhel', NULL, 'ais ezhel abimizin 2017 yılında yayınamış olduğuı bilmem ne öelliklere sahip bilmem ne albümü', NULL, 5, '2025-05-22 13:42:11', '2025-05-22 13:42:11', 'Diğer'),
(145, 'Around the Fur', 'Deftones', 'https://www.revolvermag.com/wp-content/uploads/2017/08/18/aroundthefur.jpg', 'https://en.wikipedia.org/wiki/Around_the_Fur', '1997-10-28', 'Romantik albüm, özel anlar için önerilir.', NULL, 4, '2025-05-22 14:03:56', '2025-05-22 14:03:56', 'Diğer');

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
(143, 5200),
(143, 5211),
(144, 5275),
(144, 5285),
(144, 5291),
(144, 5351),
(145, 5217),
(145, 5322),
(145, 5325);

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
(3, 144, '2025-05-22 13:43:57'),
(4, 143, '2025-05-22 13:48:26');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `followers`
--

CREATE TABLE `followers` (
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `followers`
--

INSERT INTO `followers` (`follower_id`, `following_id`, `created_at`) VALUES
(3, 4, '2025-05-21 19:57:53'),
(4, 3, '2025-05-21 19:35:54'),
(4, 5, '2025-05-22 13:48:38');

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
(5389, 'A cappella'),
(5294, 'Acid Jazz'),
(5394, 'Acoustic'),
(5219, 'Acoustic Blues'),
(5348, 'Afrobeat'),
(5198, 'Alternative'),
(5333, 'Alternative Country'),
(5286, 'Alternative Hip Hop'),
(5313, 'Alternative Metal'),
(5200, 'Alternative Rock'),
(5270, 'Ambient'),
(5334, 'Americana'),
(5376, 'Anime'),
(5199, 'Art Punk'),
(5239, 'Avant-Garde'),
(5295, 'Avant-Garde Jazz'),
(5390, 'Barbershop'),
(5240, 'Baroque'),
(5296, 'Bebop'),
(5297, 'Big Band'),
(5314, 'Black Metal'),
(5335, 'Bluegrass'),
(5218, 'Blues'),
(5368, 'Blues Gospel'),
(5220, 'Blues Rock'),
(5360, 'Bolero'),
(5298, 'Bossa Nova'),
(5254, 'Breakbeat'),
(5201, 'Britpunk'),
(5255, 'Brostep'),
(5349, 'Celtic'),
(5241, 'Chamber Music'),
(5391, 'Chant'),
(5221, 'Chicago Blues'),
(5370, 'Children’s Music'),
(5256, 'Chillstep'),
(5242, 'Choral'),
(5369, 'Christian'),
(5379, 'Christmas'),
(5222, 'Classic Blues'),
(5238, 'Classical'),
(5243, 'Classical Crossover'),
(5253, 'Club Dance'),
(5202, 'College Rock'),
(5371, 'Comedy'),
(5223, 'Contemporary Blues'),
(5244, 'Contemporary Classical'),
(5336, 'Contemporary Country'),
(5343, 'Contemporary Folk'),
(5362, 'Contemporary R&B'),
(5299, 'Cool Jazz'),
(5332, 'Country'),
(5224, 'Country Blues'),
(5337, 'Country Pop'),
(5338, 'Country Rock'),
(5300, 'Crossover Jazz'),
(5203, 'Crossover Thrash'),
(5204, 'Crust Punk'),
(5359, 'Cumbia'),
(5252, 'Dance'),
(5277, 'Dance Pop'),
(5315, 'Death Metal'),
(5257, 'Deep House'),
(5225, 'Delta Blues'),
(5366, 'Disco'),
(5301, 'Dixieland'),
(5316, 'Doom Metal'),
(5271, 'Downtempo'),
(5268, 'Drum and Bass'),
(5258, 'Dubstep'),
(5287, 'East Coast Hip Hop'),
(5382, 'Easter'),
(5386, 'Easy Listening'),
(5226, 'Electric Blues'),
(5259, 'Electro House'),
(5269, 'Electronic'),
(5278, 'Electropop'),
(5260, 'Electroswing'),
(5205, 'Emotional Hardcore'),
(5206, 'Experimental Rock'),
(5350, 'Flamenco'),
(5342, 'Folk'),
(5227, 'Folk Blues'),
(5317, 'Folk Metal'),
(5207, 'Folk Punk'),
(5344, 'Folk Rock'),
(5302, 'Free Jazz'),
(5365, 'Funk'),
(5303, 'Fusion'),
(5288, 'Gangsta Rap'),
(5261, 'Garage'),
(5318, 'Glam Metal'),
(5262, 'Glitch Hop'),
(5367, 'Gospel'),
(5228, 'Gospel Blues'),
(5208, 'Goth Rock'),
(5319, 'Gothic Metal'),
(5263, 'Grime'),
(5320, 'Grindcore'),
(5321, 'Groove Metal'),
(5209, 'Grunge'),
(5304, 'Gypsy Jazz'),
(5381, 'Halloween'),
(5380, 'Hanukkah'),
(5305, 'Hard Bop'),
(5264, 'Hard Dance'),
(5211, 'Hard Rock'),
(5289, 'Hardcore Hip Hop'),
(5210, 'Hardcore Punk'),
(5322, 'Heavy Metal'),
(5245, 'High Classical'),
(5285, 'Hip Hop'),
(5378, 'Holiday'),
(5339, 'Honky Tonk'),
(5265, 'House'),
(5272, 'IDM'),
(5345, 'Indie Folk'),
(5279, 'Indie Pop'),
(5212, 'Indie Rock'),
(5273, 'Industrial'),
(5323, 'Industrial Metal'),
(5393, 'Instrumental'),
(5281, 'J-Pop'),
(5293, 'Jazz'),
(5229, 'Jazz Blues'),
(5230, 'Jump Blues'),
(5280, 'K-Pop'),
(5231, 'Kansas City Blues'),
(5377, 'Karaoke'),
(5353, 'Latin'),
(5306, 'Latin Jazz'),
(5213, 'Lo-fi'),
(5387, 'Lounge'),
(5384, 'March'),
(5232, 'Memphis Blues'),
(5358, 'Merengue'),
(5312, 'Metal'),
(5324, 'Metalcore'),
(5385, 'Military'),
(5307, 'Modal Jazz'),
(5233, 'Modern Blues'),
(5373, 'Musical'),
(5363, 'Neo Soul'),
(5214, 'New Wave'),
(5397, 'Noise'),
(5325, 'Nu Metal'),
(5290, 'Old School Hip Hop'),
(5246, 'Opera'),
(5247, 'Orchestral'),
(5340, 'Outlaw Country'),
(5383, 'Patriotic'),
(5395, 'Piano'),
(5234, 'Piano Blues'),
(5276, 'Pop'),
(5283, 'Pop Rock'),
(5326, 'Power Metal'),
(5284, 'Power Pop'),
(5327, 'Progressive Metal'),
(5215, 'Progressive Rock'),
(5216, 'Punk'),
(5361, 'R&B'),
(5308, 'Ragtime'),
(5291, 'Rap'),
(5351, 'Reggae'),
(5356, 'Reggaeton'),
(5248, 'Renaissance'),
(5249, 'Romantic'),
(5357, 'Salsa'),
(5354, 'Samba'),
(5217, 'Shoegaze'),
(5346, 'Singer-Songwriter'),
(5352, 'Ska'),
(5328, 'Sludge Metal'),
(5309, 'Smooth Jazz'),
(5364, 'Soul'),
(5235, 'Soul Blues'),
(5398, 'Sound Art'),
(5372, 'Soundtrack'),
(5329, 'Speed Metal'),
(5374, 'Stage & Screen'),
(5396, 'String Quartet'),
(5310, 'Swing'),
(5250, 'Symphonic'),
(5330, 'Symphonic Metal'),
(5251, 'Symphony'),
(5274, 'Synthpop'),
(5355, 'Tango'),
(5266, 'Techno'),
(5282, 'Teen Pop'),
(5236, 'Texas Blues'),
(5331, 'Thrash Metal'),
(5341, 'Traditional Country'),
(5267, 'Trance'),
(5275, 'Trap'),
(5237, 'Urban Blues'),
(5375, 'Video Game Music'),
(5388, 'Vocal'),
(5311, 'Vocal Jazz'),
(5392, 'Vocal Pop'),
(5292, 'West Coast Hip Hop'),
(5347, 'World');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `album_id` int(11) DEFAULT NULL,
  `rating` decimal(3,1) NOT NULL ,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `ratings`
--

INSERT INTO `ratings` (`id`, `user_id`, `album_id`, `rating`, `created_at`, `updated_at`) VALUES
(1, 3, 143, 6.6, '2025-05-21 23:01:20', '2025-05-21 23:01:20'),
(2, 4, 143, 7.4, '2025-05-21 23:15:38', '2025-05-21 23:15:38'),
(3, 5, 144, 8.1, '2025-05-22 13:42:11', '2025-05-22 13:42:11'),
(4, 3, 144, 6.7, '2025-05-22 13:43:46', '2025-05-22 13:43:46'),
(5, 4, 145, 9.8, '2025-05-22 14:03:56', '2025-05-22 14:03:56');

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
(1, 4, 143, 'adamsın', '2025-05-21 23:15:37', '2025-05-21 23:15:37'),
(2, 3, 144, 'evet bu albümü ben deçok beğenmiştim falan fialn', '2025-05-22 13:43:46', '2025-05-22 13:43:46');

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
(1, 'demo', 'demo@example.com', 'demo123', 'default.jpg', NULL, '2025-05-21 21:50:39', '2025-05-21 21:50:39'),
(3, 'Tester Sins', 'test@example.com', '$2y$10$oYm4AzCCSQ6dZjK0iNUSnOwhrzpQICjCV4zmKsqLQNWuJr3ZBtSPO', 'default.jpg', NULL, '2025-05-21 17:06:31', '2025-05-21 17:06:31'),
(4, 'Tester Sins2', 'test2@example.com', '$2y$10$DqJJtrL/c5Gtf3kAT0lvtOXepHP5RQxz/9YxivIaXSu.anant1Uau', 'default.jpg', NULL, '2025-05-21 18:08:05', '2025-05-21 18:08:05'),
(5, 'Fatih Arslan', 'fatih@example.com', '$2y$10$O2oNu0CsIWYseTTfUNO2p.kKvEx1YUPymq4yIEJIg4BrNxVjWck4.', 'default.jpg', NULL, '2025-05-22 13:37:51', '2025-05-22 13:37:51');

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
-- Tablo için indeksler `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_messages_sender` (`sender_id`),
  ADD KEY `idx_messages_receiver` (`receiver_id`);

--
-- Tablo için indeksler `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rating` (`user_id`,`album_id`),
  ADD KEY `idx_ratings_user` (`user_id`),
  ADD KEY `idx_ratings_album` (`album_id`);

--
-- Tablo için indeksler `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reviews_user` (`user_id`),
  ADD KEY `idx_reviews_album` (`album_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=146;

--
-- Tablo için AUTO_INCREMENT değeri `genres`
--
ALTER TABLE `genres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5399;

--
-- Tablo için AUTO_INCREMENT değeri `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `tracks`
--
ALTER TABLE `tracks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `albums`
--
ALTER TABLE `albums`
  ADD CONSTRAINT `albums_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `album_genres`
--
ALTER TABLE `album_genres`
  ADD CONSTRAINT `album_genres_ibfk_1` FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `album_genres_ibfk_2` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `followers`
--
ALTER TABLE `followers`
  ADD CONSTRAINT `followers_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `followers_ibfk_2` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `tracks`
--
ALTER TABLE `tracks`
  ADD CONSTRAINT `tracks_ibfk_1` FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
