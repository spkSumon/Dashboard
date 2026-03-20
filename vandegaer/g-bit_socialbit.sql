-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Gegenereerd op: 06 feb 2026 om 08:53
-- Serverversie: 10.5.22-MariaDB-1:10.5.22+maria~ubu1804
-- PHP-versie: 8.4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `g-bit_socialbit`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `content_planning`
--

CREATE TABLE `content_planning` (
  `id` int(11) NOT NULL,
  `platform` varchar(50) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `scheduled_at` datetime DEFAULT NULL,
  `content_type` varchar(50) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `hashtags` text DEFAULT NULL,
  `topic` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `post_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structuur voor view `hashtag_leaderboard`
-- (Zie onder voor de actuele view)
--
CREATE TABLE `hashtag_leaderboard` (
`hashtag` varchar(100)
,`platform` enum('tiktok','instagram','facebook')
,`total_posts` int(11)
,`avg_engagement_rate` decimal(5,2)
,`total_views` int(11)
);

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `hashtag_performance`
--

CREATE TABLE `hashtag_performance` (
  `id` int(11) NOT NULL,
  `hashtag` varchar(100) NOT NULL,
  `platform` enum('tiktok','instagram','facebook') DEFAULT NULL,
  `total_posts` int(11) DEFAULT 0,
  `total_views` int(11) DEFAULT 0,
  `total_likes` int(11) DEFAULT 0,
  `avg_engagement_rate` decimal(5,2) DEFAULT NULL,
  `last_used` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Gegevens worden geëxporteerd voor tabel `hashtag_performance`
--

INSERT INTO `hashtag_performance` (`id`, `hashtag`, `platform`, `total_posts`, `total_views`, `total_likes`, `avg_engagement_rate`, `last_used`) VALUES
(1, 'leuven', 'instagram', 1, 0, 30, NULL, '2025-12-13'),
(2, 'leuvencity', 'instagram', 7, 0, 753, NULL, '2025-12-13'),
(3, 'leuveneats', 'instagram', 2, 0, 369, NULL, '2025-12-13'),
(4, 'pizzalovers', 'instagram', 2, 0, 259, NULL, '2025-12-13'),
(5, 'giuditta', 'instagram', 8, 0, 982, NULL, '2025-12-13'),
(7, 'leuvenfoodies', 'instagram', 2, 0, 326, NULL, '2025-12-13'),
(9, 'italianvibes', 'instagram', 1, 0, 229, NULL, '2025-12-13'),
(10, 'friendsandpizza', 'instagram', 1, 0, 229, NULL, '2025-12-13'),
(14, 'pizzaparty', 'instagram', 1, 0, 339, NULL, '2025-12-13'),
(17, 'ItaliaansePizza', 'instagram', 2, 0, 203, NULL, '2025-12-13'),
(18, 'DPProjects', 'instagram', 1, 0, 97, NULL, '2025-12-13'),
(19, 'Peccati', 'instagram', 1, 0, 97, NULL, '2025-12-13'),
(21, 'behindthescenes', 'instagram', 2, 0, 203, NULL, '2025-12-13'),
(26, 'Verbouwingen', 'instagram', 1, 0, 106, NULL, '2025-12-13'),
(27, '16september', 'instagram', 1, 0, 106, NULL, '2025-12-13'),
(29, 'mozzarella', 'instagram', 1, 0, 54, NULL, '2025-12-13'),
(30, 'buffelardenne', 'instagram', 1, 0, 54, NULL, '2025-12-13'),
(32, 'vanboernaarbord', 'instagram', 1, 0, 54, NULL, '2025-12-13'),
(34, 'echtepizza', 'instagram', 1, 0, 79, NULL, '2025-12-13'),
(35, 'napolitaans', 'instagram', 1, 0, 79, NULL, '2025-12-13'),
(36, 'houtoven', 'instagram', 1, 0, 79, NULL, '2025-12-13'),
(38, 'geencompromis', 'instagram', 1, 0, 79, NULL, '2025-12-13'),
(41, 'restaurantinterieur', 'instagram', 1, 0, 48, NULL, '2025-12-13'),
(42, 'kiesmee', 'instagram', 1, 0, 48, NULL, '2025-12-13'),
(43, 'meedenkenmag', 'instagram', 1, 0, 48, NULL, '2025-12-13'),
(44, 'interieurtwijfels', 'instagram', 1, 0, 48, NULL, '2025-12-13'),
(45, 'Giuditta', 'tiktok', 11, 829712, 28802, NULL, '2025-12-13'),
(46, 'LeuvenCity', 'tiktok', 9, 749214, 27278, NULL, '2025-12-13'),
(47, 'FreePizza', 'tiktok', 1, 377491, 14925, NULL, '2025-12-13'),
(48, 'OpeningDay', 'tiktok', 1, 377491, 14925, NULL, '2025-12-13'),
(49, 'Pizzalovers', 'tiktok', 4, 665562, 25194, NULL, '2025-12-13'),
(50, 'leuven', 'tiktok', 4, 312050, 10082, NULL, '2025-12-13'),
(52, 'leuveneats', 'tiktok', 4, 312948, 10844, NULL, '2025-12-13'),
(55, 'G', 'tiktok', 1, 64806, 2782, NULL, '2025-12-13'),
(56, 'GiudittaLeuvenI', 'tiktok', 1, 64806, 2782, NULL, '2025-12-13'),
(57, 'ItaliaansInLeuvenP', 'tiktok', 1, 64806, 2782, NULL, '2025-12-13'),
(58, 'PizzaloversB', 'tiktok', 1, 64806, 2782, NULL, '2025-12-13'),
(59, 'BehindTheScenesC', 'tiktok', 1, 64806, 2782, NULL, '2025-12-13'),
(61, 'LeuvenJobs', 'tiktok', 1, 61957, 1271, NULL, '2025-12-13'),
(62, 'FlexiJob', 'tiktok', 1, 61957, 1271, NULL, '2025-12-13'),
(63, 'Jobstudent', 'tiktok', 1, 61957, 1271, NULL, '2025-12-13'),
(64, 'ItaliaansePizza', 'tiktok', 3, 82151, 1498, NULL, '2025-12-13'),
(68, 'pizzaparty', 'tiktok', 1, 36147, 721, NULL, '2025-12-13'),
(70, 'houtoven', 'tiktok', 2, 40720, 1296, NULL, '2025-12-13'),
(71, 'handgemaakt', 'tiktok', 1, 35762, 1253, NULL, '2025-12-13'),
(72, 'ambacht', 'tiktok', 1, 35762, 1253, NULL, '2025-12-13'),
(75, 'leuvenfoodies', 'tiktok', 2, 31933, 429, NULL, '2025-12-13'),
(77, 'italianvibes', 'tiktok', 1, 18541, 253, NULL, '2025-12-13'),
(78, 'friendsandpizza', 'tiktok', 1, 18541, 253, NULL, '2025-12-13'),
(79, 'christmas', 'tiktok', 2, 26463, 240, NULL, '2025-12-13'),
(80, 'giudittaleuven', 'tiktok', 4, 54994, 380, NULL, '2025-12-13'),
(81, 'shoppin', 'tiktok', 1, 14607, 147, NULL, '2025-12-13'),
(82, 'vancranenbroek', 'tiktok', 1, 14607, 147, NULL, '2025-12-13'),
(83, 'terrasvibes', 'tiktok', 1, 13392, 176, NULL, '2025-12-13'),
(84, 'zomerdrankjes', 'tiktok', 1, 13392, 176, NULL, '2025-12-13'),
(85, 'parijsestraat', 'tiktok', 1, 13392, 176, NULL, '2025-12-13'),
(87, 'drinklocal', 'tiktok', 1, 13392, 176, NULL, '2025-12-13'),
(91, 'BehindTheScenes', 'tiktok', 1, 10239, 120, NULL, '2025-12-13'),
(95, 'DPProjects', 'tiktok', 1, 9955, 107, NULL, '2025-12-13'),
(96, 'Peccati', 'tiktok', 1, 9955, 107, NULL, '2025-12-13'),
(98, 'Binnenkijker', 'tiktok', 1, 7271, 107, NULL, '2025-12-13'),
(99, 'ItaliaansMetEenTwist', 'tiktok', 1, 7271, 107, NULL, '2025-12-13'),
(101, 'WorkInProgress', 'tiktok', 1, 7271, 107, NULL, '2025-12-13'),
(103, 'mozzarella', 'tiktok', 1, 5132, 93, NULL, '2025-12-13'),
(104, 'buffelardenne', 'tiktok', 1, 5132, 93, NULL, '2025-12-13'),
(106, 'vanboernaarbord', 'tiktok', 1, 5132, 93, NULL, '2025-12-13'),
(113, 'echtepizza', 'tiktok', 1, 4958, 43, NULL, '2025-12-13'),
(114, 'napolitaans', 'tiktok', 1, 4958, 43, NULL, '2025-12-13'),
(119, 'pizza', 'tiktok', 1, 21260, 33, NULL, '2025-12-13'),
(120, 'pizzalover', 'tiktok', 1, 21260, 33, NULL, '2025-12-13'),
(122, 'decorating', 'tiktok', 1, 11856, 93, NULL, '2025-12-13');

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `import_history`
--

CREATE TABLE `import_history` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `platform` enum('tiktok','instagram','facebook') DEFAULT NULL,
  `import_type` enum('csv','api','scraping','manual') DEFAULT NULL,
  `rows_imported` int(11) DEFAULT 0,
  `rows_skipped` int(11) DEFAULT 0,
  `rows_failed` int(11) DEFAULT 0,
  `imported_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `metrics_history`
--

CREATE TABLE `metrics_history` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `views` int(11) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `comments` int(11) DEFAULT 0,
  `shares` int(11) DEFAULT 0,
  `saves` int(11) DEFAULT 0,
  `reach` int(11) DEFAULT 0,
  `impressions` int(11) DEFAULT 0,
  `snapshot_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Gegevens worden geëxporteerd voor tabel `metrics_history`
--

INSERT INTO `metrics_history` (`id`, `post_id`, `views`, `likes`, `comments`, `shares`, `saves`, `reach`, `impressions`, `snapshot_date`) VALUES
(1, 1, 0, 30, 0, 0, 0, 0, 0, '2025-12-12 23:00:00'),
(2, 2, 0, 229, 3, 0, 0, 0, 0, '2025-12-12 23:00:00'),
(3, 3, 0, 339, 5, 0, 0, 0, 0, '2025-12-12 23:00:00'),
(4, 4, 0, 97, 1, 0, 0, 0, 0, '2025-12-12 23:00:00'),
(5, 5, 0, 106, 6, 0, 0, 0, 0, '2025-12-12 23:00:00'),
(6, 6, 0, 54, 2, 0, 0, 0, 0, '2025-12-12 23:00:00'),
(7, 7, 0, 79, 4, 0, 0, 0, 0, '2025-12-12 23:00:00'),
(8, 8, 0, 48, 10, 0, 0, 0, 0, '2025-12-12 23:00:00'),
(9, 9, 377491, 14925, 176, 5699, 0, 0, 0, '2025-12-12 23:00:00'),
(10, 10, 264240, 9904, 90, 2319, 0, 0, 0, '2025-12-12 23:00:00'),
(11, 11, 64806, 2782, 34, 745, 0, 0, 0, '2025-12-12 23:00:00'),
(12, 12, 61957, 1271, 7, 569, 0, 0, 0, '2025-12-12 23:00:00'),
(13, 13, 36147, 721, 6, 396, 0, 0, 0, '2025-12-12 23:00:00'),
(14, 14, 35762, 1253, 8, 8, 0, 0, 0, '2025-12-12 23:00:00'),
(15, 15, 18541, 253, 2, 86, 0, 0, 0, '2025-12-12 23:00:00'),
(16, 16, 14607, 147, 3, 35, 16, 13000, 14607, '2025-12-12 23:00:00'),
(17, 17, 13392, 176, 10, 45, 0, 0, 0, '2025-12-12 23:00:00'),
(18, 18, 10239, 120, 4, 6, 0, 0, 0, '2025-12-12 23:00:00'),
(19, 19, 9955, 107, 1, 7, 0, 0, 0, '2025-12-12 23:00:00'),
(20, 20, 7271, 107, 11, 9, 0, 0, 0, '2025-12-12 23:00:00'),
(21, 21, 5132, 93, 0, 8, 0, 0, 0, '2025-12-12 23:00:00'),
(22, 22, 5290, 112, 4, 35, 0, 0, 0, '2025-12-12 23:00:00'),
(23, 23, 4958, 43, 1, 2, 0, 0, 0, '2025-12-12 23:00:00'),
(24, 24, 21260, 33, 0, 3, 1, 640, 881, '2025-12-12 23:00:00'),
(25, 25, 11856, 93, 7, 2, 4, 9200, 11856, '2025-12-12 23:00:00');

-- --------------------------------------------------------

--
-- Stand-in structuur voor view `platform_comparison`
-- (Zie onder voor de actuele view)
--
CREATE TABLE `platform_comparison` (
`platform` enum('tiktok','instagram','facebook')
,`total_posts` bigint(21)
,`avg_views` decimal(14,4)
,`avg_likes` decimal(14,4)
,`avg_engagement_rate` decimal(9,6)
,`total_views` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `platform` enum('tiktok','instagram','facebook') NOT NULL,
  `platform_post_id` varchar(100) DEFAULT NULL,
  `post_url` text DEFAULT NULL,
  `post_type` varchar(50) DEFAULT NULL,
  `topic` varchar(100) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `hashtags` text DEFAULT NULL,
  `posted_date` date NOT NULL,
  `posted_time` time DEFAULT NULL,
  `internal_caption` text DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `comments` int(11) DEFAULT 0,
  `shares` int(11) DEFAULT 0,
  `saves` int(11) DEFAULT 0,
  `reach` int(11) DEFAULT 0,
  `impressions` int(11) DEFAULT 0,
  `engagement_rate` decimal(5,2) DEFAULT NULL,
  `import_source` enum('csv','api','scraping','manual') DEFAULT 'csv',
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Gegevens worden geëxporteerd voor tabel `posts`
--

INSERT INTO `posts` (`id`, `platform`, `platform_post_id`, `post_url`, `post_type`, `topic`, `caption`, `hashtags`, `posted_date`, `posted_time`, `internal_caption`, `internal_notes`, `views`, `likes`, `comments`, `shares`, `saves`, `reach`, `impressions`, `engagement_rate`, `import_source`, `last_updated`, `created_at`) VALUES
(1, 'instagram', 'AUTO_IG_003', NULL, 'Reel', 'Auto-imported Reel', 'Heel erg bedankt voor te komen iedereen dit hadden...', '#leuven #leuvencity #leuveneats #pizzalovers #giuditta', '2025-09-23', '10:29:00', NULL, 'Automatisch geïmporteerd', 0, 30, 0, 0, 0, 0, 0, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(2, 'instagram', 'AUTO_IG_005', NULL, 'Reel', 'Auto-imported Reel', 'Onze eerste pizza’s zijn de oven uitgekomen… en na...', '#Giuditta #leuvenfoodies #pizzalovers #italianvibes #friendsandpizza', '2025-09-21', '16:53:00', NULL, 'Automatisch geïmporteerd', 0, 229, 3, 0, 0, 0, 0, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(3, 'instagram', 'AUTO_IG_006', NULL, 'Reel', 'Auto-imported Reel', '23 september is de dag! Noteer het alvast in je ag...', '#leuvencity #giuditta #leuveneats #pizzaparty', '2025-09-15', '16:17:00', NULL, 'Automatisch geïmporteerd', 0, 339, 5, 0, 0, 0, 0, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(4, 'instagram', 'AUTO_IG_007', NULL, 'Reel', 'Auto-imported Reel', 'Van lege muren naar een warm Italiaans pizzarestau...', '#Giuditta #LeuvenCity #ItaliaansePizza #DPProjects #Peccati #LeuvenFoodies #behindthescenes', '2025-08-22', '17:30:00', NULL, 'Automatisch geïmporteerd', 0, 97, 1, 0, 0, 0, 0, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(5, 'instagram', 'AUTO_IG_009', NULL, 'Reel', 'Auto-imported Reel', '🇧🇪 We zijn terug in België en vliegen er opnieuw i...', '#Giuditta #LeuvenCity #ItaliaansePizza #BehindTheScenes #Verbouwingen #16september', '2025-08-20', '17:00:00', NULL, 'Automatisch geïmporteerd', 0, 106, 6, 0, 0, 0, 0, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(6, 'instagram', 'AUTO_IG_011', NULL, 'Reel', 'Auto-imported Reel', 'Op bezoek bij de buffels van Buffel’Ardenne 🐃🇮🇹 Hi...', '#Giuditta #mozzarella #buffelardenne #leuvencity #vanboernaarbord', '2025-06-15', '13:54:00', NULL, 'Automatisch geïmporteerd', 0, 54, 2, 0, 0, 0, 0, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(7, 'instagram', 'AUTO_IG_012', NULL, 'Reel', 'Auto-imported Reel', 'Iedereen zegt dat ze ‘echte’ Napolitaanse pizza ma...', '#Giuditta #echtepizza #napolitaans #houtoven #leuvencity #geencompromis', '2025-06-07', '16:05:00', NULL, 'Automatisch geïmporteerd', 0, 79, 4, 0, 0, 0, 0, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(8, 'instagram', 'AUTO_IG_013', NULL, 'Reel', 'Auto-imported Reel', 'Nieuwe stoelen kiezen voor ons nieuw restaurant… m...', '#Giuditta #leuvencity #restaurantinterieur #kiesmee #meedenkenmag #interieurtwijfels', '2025-06-06', '12:46:00', NULL, 'Automatisch geïmporteerd', 0, 48, 10, 0, 0, 0, 0, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(9, 'tiktok', 'AUTO_TT_001', NULL, 'Video', '🎉 Vandaag is het zover: onze opening! En we vieren dat met een cadeautje voor jullie 👉 morgen 23 sep', '🎉 Vandaag is het zover: onze opening! En we vieren dat met een cadeautje voor jullie 👉 morgen 23 september, tussen 11u en 12u, kan je GRATIS een pizza komen afhalen 🍕💨 Kom langs, proef de sfeer en de', 'Giuditta, LeuvenCity, FreePizza, OpeningDay, Pizzalovers', '2025-09-22', NULL, NULL, 'TikTok Official Import - Video ID: 7552882123242343712', 377491, 14925, 176, 5699, 0, 0, 0, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(10, 'tiktok', 'AUTO_TT_002', NULL, 'Video', 'Heel erg bedankt voor te komen iedereen  ❤️❤️❤️ WE ZIJN OFFICIEEL OPEN 🥳🥳🥳 #leuven #leuvencity #leuv', 'Heel erg bedankt voor te komen iedereen  ❤️❤️❤️ WE ZIJN OFFICIEEL OPEN 🥳🥳🥳 #leuven #leuvencity #leuveneats #pizzalovers #giuditta', 'leuven, leuvencity, leuveneats, pizzalovers, giuditta', '2025-09-23', NULL, NULL, 'TikTok Official Import - Video ID: 7553230096908307745', 264240, 9904, 90, 2319, 0, 0, 0, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(11, 'tiktok', 'AUTO_TT_003', NULL, 'Video', 'Van lege muren tot geurige pizza’s. 🍕✨ Welkom bij Giuditta. Hier bouwen ik en mijn man Claudio aan e', 'Van lege muren tot geurige pizza’s. 🍕✨ Welkom bij Giuditta. Hier bouwen ik en mijn man Claudio aan een modern Italiaans pizzarestaurant — en jij mag meekijken. We tonen je alles: de oveninstallatie, d', 'G, GiudittaLeuvenI, ItaliaansInLeuvenP, PizzaloversB, BehindTheScenesC', '2025-05-04', NULL, NULL, 'TikTok Official Import - Video ID: 7500556681252867330', 64806, 2782, 34, 745, 0, 0, 0, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(12, 'tiktok', 'AUTO_TT_004', NULL, 'Video', '🍕✨ Giuditta opent binnenkort in hartje Leuven, en wij zoeken nog enthousiaste flexi-jobbers & jobstu', '🍕✨ Giuditta opent binnenkort in hartje Leuven, en wij zoeken nog enthousiaste flexi-jobbers & jobstudenten om ons team te versterken! 👉 Zin om mee te draaien in een gloednieuw Italiaans pizzarestauran', 'Giuditta, LeuvenJobs, FlexiJob, Jobstudent, ItaliaansePizza', '2025-08-22', NULL, NULL, 'TikTok Official Import - Video ID: 7541325970872093985', 61957, 1271, 7, 569, 0, 0, 0, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(13, 'tiktok', 'AUTO_TT_005', NULL, 'Video', '23 september is de dag! Noteer het alvast in je agenda want de eerste 100 margarita’s zijn gratis ;)', '23 september is de dag! Noteer het alvast in je agenda want de eerste 100 margarita’s zijn gratis ;) Adres: Parijsstraat 44, 3000 Leuven #leuvencity #giuditta #leuveneats #pizzaparty', 'leuvencity, giuditta, leuveneats, pizzaparty', '2025-09-15', NULL, NULL, 'TikTok Official Import - Video ID: 7550283709476277536', 36147, 721, 6, 396, 0, 0, 0, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(14, 'tiktok', 'AUTO_TT_006', NULL, 'Video', 'Daar is ie. Onze houtoven. Met de hand betegeld, steen per steen. 🇮🇹 Hier bakken we binnenkort onze', 'Daar is ie. Onze houtoven. Met de hand betegeld, steen per steen. 🇮🇹 Hier bakken we binnenkort onze pizza’s zoals ze in Napels zijn. 🔥🍕 #Giuditta #houtoven #handgemaakt #ambacht #leuvencity #napolitaa', 'Giuditta, houtoven, handgemaakt, ambacht, leuvencity', '2025-06-17', NULL, NULL, 'TikTok Official Import - Video ID: 7516954853873175840', 35762, 1253, 8, 8, 0, 0, 0, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(15, 'tiktok', 'AUTO_TT_007', NULL, 'Video', 'Onze eerste pizza’s zijn de oven uitgekomen… en natuurlijk moesten onze vrienden de eersten zijn om', 'Onze eerste pizza’s zijn de oven uitgekomen… en natuurlijk moesten onze vrienden de eersten zijn om te proeven 🍕💬 Hun verdict? Alleen maar smiles en volle monden 😅 Binnenkort mag jij mee oordelen 👉 sa', 'Giuditta, leuvenfoodies, pizzalovers, italianvibes, friendsandpizza', '2025-09-21', NULL, NULL, 'TikTok Official Import - Video ID: 7552586928344943905', 18541, 253, 2, 86, 0, 0, 0, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(16, 'tiktok', 'AUTO_TT_008', NULL, 'Video', 'Kerst shoppen voor giuditta #christmas #giudittaleuven #shoppin #vancranenbroek', 'Kerst shoppen voor giuditta #christmas #giudittaleuven #shoppin #vancranenbroek', 'christmas, giudittaleuven, shoppin, vancranenbroek', '2025-11-13', NULL, NULL, 'TikTok Official Import - Video ID: 7572240449906150689', 14607, 147, 3, 35, 16, 13000, 14607, 1.28, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(17, 'tiktok', 'AUTO_TT_009', NULL, 'Video', 'Yes, binnenkort kan je ook op ons terras in de Parijsestraat zitten! Wat is jouw favoriete zomerdran', 'Yes, binnenkort kan je ook op ons terras in de Parijsestraat zitten! Wat is jouw favoriete zomerdrankje? Laat het weten in de comments. Het drankje met de meeste likes komt op onze kaart! #terrasvibes', 'terrasvibes, zomerdrankjes, parijsestraat, leuvenfoodies, drinklocal', '2025-05-25', NULL, NULL, 'TikTok Official Import - Video ID: 7508345523523308822', 13392, 176, 10, 45, 0, 0, 0, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(18, 'tiktok', 'AUTO_TT_010', NULL, 'Video', '🇧🇪 We zijn terug in België en vliegen er opnieuw in! De verbouwingen bij Giuditta lopen volop, alles', '🇧🇪 We zijn terug in België en vliegen er opnieuw in! De verbouwingen bij Giuditta lopen volop, alles in de hoop dat we jullie op 16 september kunnen verwelkomen 🍕✨ Nog geen grote updates… behalve onze', 'Giuditta, LeuvenCity, ItaliaansePizza, BehindTheScenes', '2025-08-19', NULL, NULL, 'TikTok Official Import - Video ID: 7540362203703151905', 10239, 120, 4, 6, 0, 0, 0, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(19, 'tiktok', 'AUTO_TT_011', NULL, 'Video', 'Van lege muren naar een warm Italiaans pizzarestaurant 🍕✨ We nemen jullie mee in hoe Giuditta er nu', 'Van lege muren naar een warm Italiaans pizzarestaurant 🍕✨ We nemen jullie mee in hoe Giuditta er nu uitziet én wat het binnenkort zal worden. We zijn ook heel trots om dit avontuur te bouwen samen met', 'Giuditta, LeuvenCity, ItaliaansePizza, DPProjects, Peccati', '2025-08-20', NULL, NULL, 'TikTok Official Import - Video ID: 7540654135620717856', 9955, 107, 1, 7, 0, 0, 0, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(20, 'tiktok', 'AUTO_TT_012', NULL, 'Video', 'Onze blije fee 🧚‍♀️ Davinia stapt voor de eerste keer écht binnen bij onze nieuwe zaak Giuditta. All', 'Onze blije fee 🧚‍♀️ Davinia stapt voor de eerste keer écht binnen bij onze nieuwe zaak Giuditta. Alles is nog ruw, maar de sfeer voel je al. We willen een plek maken waar je blijft hangen — voor de pi', 'GiudittaLeuven, Binnenkijker, ItaliaansMetEenTwist, LeuvenEats, WorkInProgress', '2025-05-16', NULL, NULL, 'TikTok Official Import - Video ID: 7504959163152272673', 7271, 107, 11, 9, 0, 0, 0, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(21, 'tiktok', 'AUTO_TT_013', NULL, 'Video', 'Op bezoek bij de buffels van Buffel’Ardenne 🐃🇮🇹 Hier begint het. Verse melk, pure ambacht, en mozzar', 'Op bezoek bij de buffels van Buffel’Ardenne 🐃🇮🇹 Hier begint het. Verse melk, pure ambacht, en mozzarella zoals ze in Italië maken. Want zonder goeie mozzarella, geen goeie pizza. 🍕 #Giuditta #mozzarel', 'Giuditta, mozzarella, buffelardenne, leuvencity, vanboernaarbord', '2025-06-15', NULL, NULL, 'TikTok Official Import - Video ID: 7516173724387773718', 5132, 93, 0, 8, 0, 0, 0, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(22, 'tiktok', 'AUTO_TT_014', NULL, 'Video', 'Wat een opening! 🤩  Dankjewel aan iedereen die er gisteren bij was 🙏 Vanaf nu kan je reserveren, dus', 'Wat een opening! 🤩  Dankjewel aan iedereen die er gisteren bij was 🙏 Vanaf nu kan je reserveren, dus claim je spot en kom ontdekken waarom dit een echte must-try is.  Leuven, we hopen dat jullie je hi', 'leuvencity, giuditta, leuveneats, leuven, pizzalovers', '2025-09-24', NULL, NULL, 'TikTok Official Import - Video ID: 7553658701777767713', 5290, 112, 4, 35, 0, 0, 0, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(23, 'tiktok', 'AUTO_TT_015', NULL, 'Video', 'Iedereen zegt dat ze ‘echte’ Napolitaanse pizza maken… Maar laten we eerlijk zijn: zonder houtoven?', 'Iedereen zegt dat ze ‘echte’ Napolitaanse pizza maken… Maar laten we eerlijk zijn: zonder houtoven? Zonder Claudio? Komaan hé 😏🔥 #Giuditta #echtepizza #napolitaans #houtoven #leuvencity #geencompromis', 'Giuditta, echtepizza, napolitaans, houtoven, leuvencity', '2025-06-07', NULL, NULL, 'TikTok Official Import - Video ID: 7513248504177773846', 4958, 43, 1, 2, 0, 0, 0, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(24, 'tiktok', 'AUTO_TT_016', NULL, 'Video', 'Filip?? #giudittaleuven #leuven #pizza #pizzalover #leuven', 'Filip?? #giudittaleuven #leuven #pizza #pizzalover #leuven', 'giudittaleuven, leuven, pizza, pizzalover, leuven', '2025-12-07', NULL, NULL, 'TikTok Official Import - Video ID: 7581138010406784288', 21260, 33, 0, 3, 1, 640, 881, 0.00, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46'),
(25, 'tiktok', 'AUTO_TT_017', NULL, 'Video', 'Giuditta versieren!🎄 #decorating #christmas #giudittaleuven', 'Giuditta versieren!🎄 #decorating #christmas #giudittaleuven', 'decorating, christmas, giudittaleuven', '2025-11-27', NULL, NULL, 'TikTok Official Import - Video ID: 7577460325415505185', 11856, 93, 7, 2, 4, 9200, 11856, 1.13, 'csv', '2025-12-13 13:21:46', '2025-12-13 13:21:46');

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `post_hashtags`
--

CREATE TABLE `post_hashtags` (
  `post_id` int(11) NOT NULL,
  `hashtag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Gegevens worden geëxporteerd voor tabel `post_hashtags`
--

INSERT INTO `post_hashtags` (`post_id`, `hashtag_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(2, 4),
(2, 5),
(2, 7),
(2, 9),
(2, 10),
(3, 2),
(3, 3),
(3, 5),
(3, 14),
(4, 2),
(4, 5),
(4, 7),
(4, 17),
(4, 18),
(4, 19),
(4, 21),
(5, 2),
(5, 5),
(5, 17),
(5, 21),
(5, 26),
(5, 27),
(6, 2),
(6, 5),
(6, 29),
(6, 30),
(6, 32),
(7, 2),
(7, 5),
(7, 34),
(7, 35),
(7, 36),
(7, 38),
(8, 2),
(8, 5),
(8, 41),
(8, 42),
(8, 43),
(8, 44),
(9, 45),
(9, 46),
(9, 47),
(9, 48),
(9, 49),
(10, 45),
(10, 46),
(10, 49),
(10, 50),
(10, 52),
(11, 55),
(11, 56),
(11, 57),
(11, 58),
(11, 59),
(12, 45),
(12, 61),
(12, 62),
(12, 63),
(12, 64),
(13, 45),
(13, 46),
(13, 52),
(13, 68),
(14, 45),
(14, 46),
(14, 70),
(14, 71),
(14, 72),
(15, 45),
(15, 49),
(15, 75),
(15, 77),
(15, 78),
(16, 79),
(16, 80),
(16, 81),
(16, 82),
(17, 75),
(17, 83),
(17, 84),
(17, 85),
(17, 87),
(18, 45),
(18, 46),
(18, 64),
(18, 91),
(19, 45),
(19, 46),
(19, 64),
(19, 95),
(19, 96),
(20, 52),
(20, 80),
(20, 98),
(20, 99),
(20, 101),
(21, 45),
(21, 46),
(21, 103),
(21, 104),
(21, 106),
(22, 45),
(22, 46),
(22, 49),
(22, 50),
(22, 52),
(23, 45),
(23, 46),
(23, 70),
(23, 113),
(23, 114),
(24, 50),
(24, 80),
(24, 119),
(24, 120),
(25, 79),
(25, 80),
(25, 122);

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `settings`
--

CREATE TABLE `settings` (
  `key` varchar(190) NOT NULL,
  `value` text NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `tiktok_demographics`
--

CREATE TABLE `tiktok_demographics` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `gender_male_pct` decimal(5,2) DEFAULT NULL,
  `gender_female_pct` decimal(5,2) DEFAULT NULL,
  `age_13_17_pct` decimal(5,2) DEFAULT NULL,
  `age_18_24_pct` decimal(5,2) DEFAULT NULL,
  `age_25_34_pct` decimal(5,2) DEFAULT NULL,
  `age_35_44_pct` decimal(5,2) DEFAULT NULL,
  `age_45_54_pct` decimal(5,2) DEFAULT NULL,
  `age_55_plus_pct` decimal(5,2) DEFAULT NULL,
  `top_countries` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`top_countries`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `tiktok_tokens`
--

CREATE TABLE `tiktok_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `access_token` text NOT NULL,
  `refresh_token` text DEFAULT NULL,
  `token_type` varchar(20) DEFAULT 'Bearer',
  `expires_at` datetime NOT NULL,
  `scope` text DEFAULT NULL,
  `open_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Gegevens worden geëxporteerd voor tabel `tiktok_tokens`
--

INSERT INTO `tiktok_tokens` (`id`, `user_id`, `access_token`, `refresh_token`, `token_type`, `expires_at`, `scope`, `open_id`, `created_at`, `updated_at`) VALUES
(1, NULL, 'act.fqyESbDgvoXNb0KeNsh2vXea4Jrrp87hdf2RSqaqfa5S1FPZeyOXT9B6itoQ!4457.e1', 'rft.ANgG4JFrB7rzjroW0jkSk48pWhcDK4cRL3VVhPA4G9GeQ8Urmpe0xQfQqXIF!4492.e1', 'Bearer', '2025-12-16 14:48:41', 'user.info.basic,video.upload,user.info.profile,user.info.stats,video.list', '-000KWbYRwcGHf13vnqZjLk6TD8UXqtpWIf1', '2025-12-15 14:48:41', '2025-12-15 14:48:41'),
(2, NULL, 'act.fqyESbDgvoXNb0KeNsh2vXea4Jrrp87hdf2RSqaqfa5S1FPZeyOXT9B6itoQ!4457.e1', 'rft.ANgG4JFrB7rzjroW0jkSk48pWhcDK4cRL3VVhPA4G9GeQ8Urmpe0xQfQqXIF!4492.e1', 'Bearer', '2025-12-16 14:51:49', 'user.info.basic,user.info.profile,user.info.stats,video.list,video.upload', '-000KWbYRwcGHf13vnqZjLk6TD8UXqtpWIf1', '2025-12-15 14:51:49', '2025-12-15 14:51:49'),
(3, NULL, 'act.fqyESbDgvoXNb0KeNsh2vXea4Jrrp87hdf2RSqaqfa5S1FPZeyOXT9B6itoQ!4457.e1', 'rft.ANgG4JFrB7rzjroW0jkSk48pWhcDK4cRL3VVhPA4G9GeQ8Urmpe0xQfQqXIF!4492.e1', 'Bearer', '2025-12-16 15:03:46', 'user.info.stats,video.list,video.upload,user.info.profile,user.info.basic', '-000KWbYRwcGHf13vnqZjLk6TD8UXqtpWIf1', '2025-12-15 15:03:46', '2025-12-15 15:03:46'),
(4, NULL, 'act.fqyESbDgvoXNb0KeNsh2vXea4Jrrp87hdf2RSqaqfa5S1FPZeyOXT9B6itoQ!4457.e1', 'rft.ANgG4JFrB7rzjroW0jkSk48pWhcDK4cRL3VVhPA4G9GeQ8Urmpe0xQfQqXIF!4492.e1', 'Bearer', '2025-12-16 15:05:57', 'user.info.basic,user.info.stats,video.list,video.upload,user.info.profile', '-000KWbYRwcGHf13vnqZjLk6TD8UXqtpWIf1', '2025-12-15 15:05:57', '2025-12-15 15:05:57'),
(5, NULL, 'act.fqyESbDgvoXNb0KeNsh2vXea4Jrrp87hdf2RSqaqfa5S1FPZeyOXT9B6itoQ!4457.e1', 'rft.ANgG4JFrB7rzjroW0jkSk48pWhcDK4cRL3VVhPA4G9GeQ8Urmpe0xQfQqXIF!4492.e1', 'Bearer', '2025-12-16 15:12:06', 'user.info.profile,user.info.stats,video.list,video.upload,user.info.basic', '-000KWbYRwcGHf13vnqZjLk6TD8UXqtpWIf1', '2025-12-15 15:12:06', '2025-12-15 15:12:06'),
(6, NULL, 'act.fqyESbDgvoXNb0KeNsh2vXea4Jrrp87hdf2RSqaqfa5S1FPZeyOXT9B6itoQ!4457.e1', 'rft.ANgG4JFrB7rzjroW0jkSk48pWhcDK4cRL3VVhPA4G9GeQ8Urmpe0xQfQqXIF!4492.e1', 'Bearer', '2025-12-17 12:20:05', 'user.info.basic,user.info.stats,video.list,video.upload,user.info.profile', '-000KWbYRwcGHf13vnqZjLk6TD8UXqtpWIf1', '2025-12-16 12:20:06', '2025-12-16 12:20:06');

-- --------------------------------------------------------

--
-- Stand-in structuur voor view `top_posts`
-- (Zie onder voor de actuele view)
--
CREATE TABLE `top_posts` (
`id` int(11)
,`platform` enum('tiktok','instagram','facebook')
,`post_type` varchar(50)
,`topic` varchar(100)
,`caption` text
,`posted_date` date
,`views` int(11)
,`likes` int(11)
,`comments` int(11)
,`shares` int(11)
,`saves` int(11)
,`engagement_rate` decimal(5,2)
,`total_engagement` bigint(13)
);

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Gegevens worden geëxporteerd voor tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-12-13 12:43:32');

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `content_planning`
--
ALTER TABLE `content_planning`
  ADD PRIMARY KEY (`id`),
  ADD KEY `platform` (`platform`),
  ADD KEY `status` (`status`),
  ADD KEY `scheduled_at` (`scheduled_at`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexen voor tabel `hashtag_performance`
--
ALTER TABLE `hashtag_performance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_hashtag_platform` (`hashtag`,`platform`),
  ADD KEY `idx_hashtag` (`hashtag`),
  ADD KEY `idx_performance` (`avg_engagement_rate`);

--
-- Indexen voor tabel `import_history`
--
ALTER TABLE `import_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_filename` (`filename`),
  ADD KEY `idx_imported_at` (`imported_at`);

--
-- Indexen voor tabel `metrics_history`
--
ALTER TABLE `metrics_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_post_date` (`post_id`,`snapshot_date`);

--
-- Indexen voor tabel `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_platform` (`platform`),
  ADD KEY `idx_posted_date` (`posted_date`),
  ADD KEY `idx_platform_date` (`platform`,`posted_date`),
  ADD KEY `idx_engagement` (`engagement_rate`),
  ADD KEY `idx_post_type` (`post_type`),
  ADD KEY `idx_topic` (`topic`);

--
-- Indexen voor tabel `post_hashtags`
--
ALTER TABLE `post_hashtags`
  ADD PRIMARY KEY (`post_id`,`hashtag_id`),
  ADD KEY `hashtag_id` (`hashtag_id`);

--
-- Indexen voor tabel `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`key`);

--
-- Indexen voor tabel `tiktok_demographics`
--
ALTER TABLE `tiktok_demographics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_post` (`post_id`);

--
-- Indexen voor tabel `tiktok_tokens`
--
ALTER TABLE `tiktok_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexen voor tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `content_planning`
--
ALTER TABLE `content_planning`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT voor een tabel `hashtag_performance`
--
ALTER TABLE `hashtag_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT voor een tabel `import_history`
--
ALTER TABLE `import_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT voor een tabel `metrics_history`
--
ALTER TABLE `metrics_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT voor een tabel `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT voor een tabel `tiktok_demographics`
--
ALTER TABLE `tiktok_demographics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT voor een tabel `tiktok_tokens`
--
ALTER TABLE `tiktok_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT voor een tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

-- --------------------------------------------------------

--
-- Structuur voor de view `hashtag_leaderboard`
--
DROP TABLE IF EXISTS `hashtag_leaderboard`;

CREATE ALGORITHM=UNDEFINED DEFINER=`g-bit_socialbit`@`%` SQL SECURITY DEFINER VIEW `hashtag_leaderboard`  AS SELECT `hashtag_performance`.`hashtag` AS `hashtag`, `hashtag_performance`.`platform` AS `platform`, `hashtag_performance`.`total_posts` AS `total_posts`, `hashtag_performance`.`avg_engagement_rate` AS `avg_engagement_rate`, `hashtag_performance`.`total_views` AS `total_views` FROM `hashtag_performance` ORDER BY `hashtag_performance`.`avg_engagement_rate` DESC LIMIT 0, 50 ;

-- --------------------------------------------------------

--
-- Structuur voor de view `platform_comparison`
--
DROP TABLE IF EXISTS `platform_comparison`;

CREATE ALGORITHM=UNDEFINED DEFINER=`g-bit_socialbit`@`%` SQL SECURITY DEFINER VIEW `platform_comparison`  AS SELECT `posts`.`platform` AS `platform`, count(0) AS `total_posts`, avg(`posts`.`views`) AS `avg_views`, avg(`posts`.`likes`) AS `avg_likes`, avg(`posts`.`engagement_rate`) AS `avg_engagement_rate`, sum(`posts`.`views`) AS `total_views` FROM `posts` WHERE `posts`.`posted_date` is not null GROUP BY `posts`.`platform` ;

-- --------------------------------------------------------

--
-- Structuur voor de view `top_posts`
--
DROP TABLE IF EXISTS `top_posts`;

CREATE ALGORITHM=UNDEFINED DEFINER=`g-bit_socialbit`@`%` SQL SECURITY DEFINER VIEW `top_posts`  AS SELECT `posts`.`id` AS `id`, `posts`.`platform` AS `platform`, `posts`.`post_type` AS `post_type`, `posts`.`topic` AS `topic`, `posts`.`caption` AS `caption`, `posts`.`posted_date` AS `posted_date`, `posts`.`views` AS `views`, `posts`.`likes` AS `likes`, `posts`.`comments` AS `comments`, `posts`.`shares` AS `shares`, `posts`.`saves` AS `saves`, `posts`.`engagement_rate` AS `engagement_rate`, `posts`.`likes`+ `posts`.`comments` + `posts`.`shares` AS `total_engagement` FROM `posts` WHERE `posts`.`posted_date` is not null ORDER BY `posts`.`engagement_rate` DESC LIMIT 0, 100 ;

--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `content_planning`
--
ALTER TABLE `content_planning`
  ADD CONSTRAINT `fk_planning_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL;

--
-- Beperkingen voor tabel `metrics_history`
--
ALTER TABLE `metrics_history`
  ADD CONSTRAINT `metrics_history_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Beperkingen voor tabel `post_hashtags`
--
ALTER TABLE `post_hashtags`
  ADD CONSTRAINT `post_hashtags_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_hashtags_ibfk_2` FOREIGN KEY (`hashtag_id`) REFERENCES `hashtag_performance` (`id`) ON DELETE CASCADE;

--
-- Beperkingen voor tabel `tiktok_demographics`
--
ALTER TABLE `tiktok_demographics`
  ADD CONSTRAINT `tiktok_demographics_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Beperkingen voor tabel `tiktok_tokens`
--
ALTER TABLE `tiktok_tokens`
  ADD CONSTRAINT `tiktok_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
