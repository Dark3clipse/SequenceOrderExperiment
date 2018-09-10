-- phpMyAdmin SQL Dump
-- version 4.7.7
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Gegenereerd op: 10 sep 2018 om 12:56
-- Serverversie: 10.1.35-MariaDB-cll-lve
-- PHP-versie: 5.6.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sophiaha_experiment`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `participants`
--

CREATE TABLE `participants` (
  `id` int(10) UNSIGNED NOT NULL,
  `spotify_id` text NOT NULL,
  `access_token` text NOT NULL,
  `refresh_token` text NOT NULL,
  `top_track_1` int(11) DEFAULT NULL,
  `top_track_2` int(11) DEFAULT NULL,
  `top_track_3` int(11) DEFAULT NULL,
  `seeds_needed` int(11) NOT NULL,
  `invalid_recs` int(11) DEFAULT NULL COMMENT 'nr. of recommendations received from Spotify that didn&#039;t match the request.',
  `experiment_version` int(11) NOT NULL DEFAULT '0' COMMENT 'version number'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `questionnaire`
--

CREATE TABLE `questionnaire` (
  `id` int(11) NOT NULL COMMENT 'row identifier',
  `participant_id` int(11) NOT NULL,
  `msi0` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `msi1` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `msi2` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `msi3` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `msi4` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `msi5` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `msi6` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `msi7` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `msi8` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `msi9` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `msi10` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `msi11` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `msi12` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `msi13` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `msi14` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `msi15` int(11) DEFAULT NULL COMMENT '1 to 7, custom scale',
  `msi16` int(11) DEFAULT NULL COMMENT '1 to 7, custom scale',
  `msi17` int(11) DEFAULT NULL COMMENT '1 to 7, custom scale',
  `persona0` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `persona1` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `persona2` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `persona3` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `persona4` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `persona5` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `persona6` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `persona7` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `persona8` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `persona9` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `persona10` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `persona11` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `persona12` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `persona13` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `persona14` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `bfi0` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `bfi1` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `bfi2` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `bfi3` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `bfi4` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `bfi5` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `bfi6` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `bfi7` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `bfi8` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `bfi9` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree',
  `gender` int(11) DEFAULT NULL COMMENT '1=male, 2=female, 3=na',
  `age` int(11) DEFAULT NULL COMMENT 'in years',
  `spotifyhours` int(11) DEFAULT NULL COMMENT '1 to 7, custom scale',
  `perceive_personalized` int(11) DEFAULT NULL COMMENT '1 to 5, disagree to agree'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `recommendations`
--

CREATE TABLE `recommendations` (
  `id` int(11) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `track_id` text NOT NULL,
  `name` text NOT NULL,
  `preview_url` text NOT NULL,
  `cover_url` text NOT NULL,
  `artists` text NOT NULL,
  `valence` float NOT NULL,
  `energy` float NOT NULL,
  `tempo` int(11) NOT NULL,
  `key_tone` int(11) NOT NULL,
  `start` float NOT NULL DEFAULT '0',
  `section_used` tinyint(1) NOT NULL DEFAULT '0',
  `mood_group` set('low_valence','high_valence','','') NOT NULL,
  `tempo_group` set('low','high','','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `recommendations_unlimited`
--

CREATE TABLE `recommendations_unlimited` (
  `id` int(11) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `track_id` text NOT NULL,
  `name` text NOT NULL,
  `preview_url` text NOT NULL,
  `cover_url` text NOT NULL,
  `artists` text NOT NULL,
  `valence` float NOT NULL,
  `energy` float NOT NULL,
  `tempo` int(11) NOT NULL,
  `key_tone` int(11) NOT NULL,
  `mood_group` set('low_valence','high_valence','','') NOT NULL,
  `tempo_group` set('low','high','','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `spotify_premium`
--

CREATE TABLE `spotify_premium` (
  `id` int(11) NOT NULL,
  `name` text,
  `email` text,
  `password` text,
  `spotify_id` text,
  `access_token` text,
  `refresh_token` text NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `top_tracks`
--

CREATE TABLE `top_tracks` (
  `id` int(11) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `pos` int(11) NOT NULL,
  `track_id` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `top_tracks_prev`
--

CREATE TABLE `top_tracks_prev` (
  `id` int(5) NOT NULL,
  `userid` varchar(32) DEFAULT NULL,
  `trackid` varchar(22) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `transitions`
--

CREATE TABLE `transitions` (
  `id` int(11) NOT NULL COMMENT 'key',
  `participant_id` int(11) NOT NULL,
  `rec_from` int(11) NOT NULL COMMENT 'recommendation id from',
  `rec_to` int(11) NOT NULL COMMENT 'recommendation id to',
  `mood_from` set('low_valence','high_valence','','') NOT NULL,
  `mood_to` set('low_valence','high_valence','','') NOT NULL,
  `tempo_from` set('low','high','','') NOT NULL,
  `tempo_to` set('low','high','','') NOT NULL,
  `d_energy` float NOT NULL,
  `d_valence` float NOT NULL,
  `d_tempo` float NOT NULL,
  `d_key` float NOT NULL,
  `survey_1` int(11) DEFAULT NULL,
  `survey_2` int(11) DEFAULT NULL,
  `survey_3` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `participants`
--
ALTER TABLE `participants`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `questionnaire`
--
ALTER TABLE `questionnaire`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `recommendations`
--
ALTER TABLE `recommendations`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `recommendations_unlimited`
--
ALTER TABLE `recommendations_unlimited`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `spotify_premium`
--
ALTER TABLE `spotify_premium`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `top_tracks`
--
ALTER TABLE `top_tracks`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `top_tracks_prev`
--
ALTER TABLE `top_tracks_prev`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `transitions`
--
ALTER TABLE `transitions`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `participants`
--
ALTER TABLE `participants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT voor een tabel `questionnaire`
--
ALTER TABLE `questionnaire`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'row identifier', AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT voor een tabel `recommendations`
--
ALTER TABLE `recommendations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2269;

--
-- AUTO_INCREMENT voor een tabel `recommendations_unlimited`
--
ALTER TABLE `recommendations_unlimited`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2413;

--
-- AUTO_INCREMENT voor een tabel `spotify_premium`
--
ALTER TABLE `spotify_premium`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT voor een tabel `top_tracks`
--
ALTER TABLE `top_tracks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=387;

--
-- AUTO_INCREMENT voor een tabel `transitions`
--
ALTER TABLE `transitions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'key', AUTO_INCREMENT=1945;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
