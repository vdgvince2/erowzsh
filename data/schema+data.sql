-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Oct 11, 2025 at 12:21 PM
-- Server version: 5.7.39
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sh`
--

-- --------------------------------------------------------

--
-- Table structure for table `ads`
--

CREATE TABLE `ads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `keyword_id` bigint(20) UNSIGNED NOT NULL,
  `title_original` text,
  `photo` text,
  `price` decimal(12,2) DEFAULT NULL,
  `url` text,
  `category_name_path` text,
  `category_level1` varchar(255) DEFAULT NULL,
  `category_level2` varchar(255) DEFAULT NULL,
  `category_level3` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `ads`
--

INSERT INTO `ads` (`id`, `keyword_id`, `title_original`, `photo`, `price`, `url`, `category_name_path`, `category_level1`, `category_level2`, `category_level3`) VALUES
(1, 158905, 'Nightmaster Venom Strike IR Scope Lamp LED Flashlight Torch with Battery', 'https://i.ebayimg.com/images/g/RTcAAeSwRuVo4pCn/s-l225.jpg', '78.68', 'https://www.ebay.co.uk/itm/157368525958?_skw=lamping+torch&hash=item24a3e52486%3Ag%3ARTcAAeSwRuVo4pCn&mkevt=1&mkcid=1&mkrid=710-53481-19255-0&campid=5338464579&customid=SI_lamping%2Btorch&toolid=10049', ':Sporting Goods:Camping & Hiking:', 'Sporting Goods', 'Camping & Hiking', NULL),
(2, 158905, 'Sievert Vintage Brass Blow Lamp Torch 1 Pint Petrol Tool Engineering', 'https://i.ebayimg.com/images/g/zckAAeSwfJpo5ryn/s-l225.jpg', '8.64', 'https://www.ebay.co.uk/itm/336223103790?_skw=lamping+torch&hash=item4e48756b2e%3Ag%3AzckAAeSwfJpo5ryn&mkevt=1&mkcid=1&mkrid=710-53481-19255-0&campid=5338464579&customid=SI_lamping%2Btorch&toolid=10049', ':Collectables::Tools & Collectable Hardware', 'Collectables', NULL, 'Tools & Collectable Hardware'),
(3, 120956, 'Makita DML806 18V LI-ION Cordless Torch/ Lamp', 'https://i.ebayimg.com/images/g/IN0AAeSwTHJoP06q/s-l225.jpg', '21.47', 'https://www.ebay.co.uk/itm/357704094371?_skw=lamping+torch&hash=item5348d352a3%3Ag%3AIN0AAeSwTHJoP06q&mkevt=1&mkcid=1&mkrid=710-53481-19255-0&campid=5338464579&customid=SI_lamping%2Btorch&toolid=10049', ':DIY Tools & Workshop Equipment:Home, Furniture & DIY:', 'DIY Tools & Workshop Equipment', 'Home, Furniture & DIY', NULL),
(4, 120956, '1920s rare Pifco torch', 'https://i.ebayimg.com/images/g/~qoAAeSwlX5onFDG/s-l225.jpg', '20.42', 'https://www.ebay.co.uk/itm/406259038375?_skw=lamping+torch&hash=item5e96ecf8a7%3Ag%3A%7EqoAAeSwlX5onFDG&mkevt=1&mkcid=1&mkrid=710-53481-19255-0&campid=5338464579&customid=SI_lamping%2Btorch&toolid=10049', ':Collectables:Lamps, Lighting', 'Collectables', 'Lamps, Lighting', 'Lamps, Lighting'),
(5, 1, '2PCS Rechargeable LED Magnetic Work Light Cordless COB Inspection Lamp Torch USB', 'https://i.ebayimg.com/images/g/jLcAAOSwnv9nH6X9/s-l225.jpg', '10.99', 'https://www.ebay.co.uk/itm/405313622392?_skw=lamping+torch&hash=item5e5e931178%3Ag%3AjLcAAOSwnv9nH6X9&mkevt=1&mkcid=1&mkrid=710-53481-19255-0&campid=5338464578&customid=SI_lamping%2Btorch&toolid=10049', '::DIY Tools & Workshop Equipment:Sporting Goods:Home, Furniture & DIY:Camping & Hiking::', NULL, 'DIY Tools & Workshop Equipment', 'Sporting Goods'),
(6, 1, 'White/Green/Red Tactical LED Flashlight Torch Hunting Lamp Air Rifle Scope Mount', 'https://i.ebayimg.com/images/g/NRIAAOSwSptoJrQP/s-l225.jpg', '11.99', 'https://www.ebay.co.uk/itm/388423189766?_skw=lamping+torch&hash=item5a6fd38506%3Ag%3ANRIAAOSwSptoJrQP&amdata=enc%3AAQAKAAAA8PeG5RIuIyokJHJy903%2F5UbWwvh%2B7%2Bc7FG%2BV7a%2B8lN120IyXP3arbO0W3y8JJx7h7gOFQXIK1rBBzxHoPtpDDPTbwHHY29u1R16y5xG5tcr1IbHeNsTz0qqDRS6dkJng8WZOnY1Z%2ByV1HvpcX4ZA6K7cTovQt5Xw1ocIJT66lamfo94%2Fc26P7FU9RYn1q6Uocwwu%2F7gVIU2xlIAI208A0a7VYeT1vEPnDEHthc6JfHlfgr0LraZ6CNAiTR4JxwSzoOVelTADB4yZkR8oUNevB3hJF0H8Ja9NP9Ika2LygfjXfJMZ8v7i3AK6QqyStpkJIg%3D%3D&mkevt=1&mkcid=1&mkrid=710-53481-19255-0&campid=5338464578&customid=SI_lamping%2Btorch&toolid=10049', ':Sporting Goods:Camping & Hiking:', 'Sporting Goods', 'Camping & Hiking', NULL),
(7, 1, 'Burton Ultraviolet Woods Exam Lamp Light', 'https://i.ebayimg.com/images/g/8v0AAeSwWL1oXr-F/s-l225.jpg', '125.00', 'https://www.ebay.co.uk/itm/187370079317?_skw=lamping+torch&hash=item2ba0208455%3Ag%3A8v0AAeSwWL1oXr-F&amdata=enc%3AAQAKAAAA8PeG5RIuIyokJHJy903%2F5UbLS%2Brgy6AcchVmLzicMRfvQONzx%2FBMQFjU7TrCDFjeVuwNyE8lcgVX8N4fzRGMEYlx0jwpWw%2FgYTfW4evaOMgx%2B%2FWG8PSUKtOVWcDkjs%2BfTLvMuPF68r2jLuJW1Omc0d0HaEC6QlnbDWGQybaqbdnlGzX4IQlADj3k%2F01Lmm8ces2%2B3AiO%2F4aAxQRVaN9jGWzft5pAz2soaOECVcT2%2F1iyxHi%2Bj9vfxn9be8%2FnDelaij484TiA7mD71t76m%2FzimyapagKSKWBRg6I6DPdgZxU72bQF75r5ArkadVBkskYk9Q%3D%3D&mkevt=1&mkcid=1&mkrid=710-53481-19255-0&campid=5338464578&customid=SI_lamping%2Btorch&toolid=10049', ':Sporting Goods:Camping & Hiking:', 'Sporting Goods', 'Camping & Hiking', NULL),
(8, 1, 'Rechargeable LED Work Light Torch 1 Million Candle Power Spotlight Hand Lamp', 'https://i.ebayimg.com/images/g/QvUAAeSwVBBomRdD/s-l225.jpg', '15.80', 'https://www.ebay.co.uk/itm/357440549760?_skw=lamping+torch&hash=item53391df380%3Ag%3AQvUAAeSwVBBomRdD&amdata=enc%3AAQAKAAAA8PeG5RIuIyokJHJy903%2F5UY6wB3TnP5nxnQiOe4IV0s5XPsPGM0Lg%2Ft0hTX6bKKarWNhyowym2LkKYlNXFDn5y0irmTFK1yEbg3fv6FaKHeslqWlzcJyQZl4fQHaSPtLseL64jFYFA4wDdhoCdEQtyM4lRXffRsg4l8Q7utXn5vUC0tc9wlmBEcZJ2p42KOOFC4MEw8C1SdfyAai3OZD1u64JQ8fhrLhnSz2bm26vJqyrv2I9wcfIDtzq100DkLRPmqbreWGmqwb1ISuQeI7oMMgz5YwDqXNWxMx7Sh8KkpNIKwfQrZjlRuAw2b8rW%2B%2BmQ%3D%3D&mkevt=1&mkcid=1&mkrid=710-53481-19255-0&campid=5338464578&customid=SI_lamping%2Btorch&toolid=10049', ':Sporting Goods:Camping & Hiking:', 'Sporting Goods', 'Camping & Hiking', NULL),
(9, 1, 'Nebo Einstein 400 Lumens Rechargeable LED Headlamp Head Torch Lamp TATTY BOX', 'https://i.ebayimg.com/images/g/gcEAAOSwvEpjvVI3/s-l225.jpg', '12.99', 'https://www.ebay.co.uk/itm/195552397243?_skw=lamping+torch&hash=item2d87d4c7bb%3Ag%3AgcEAAOSwvEpjvVI3&mkevt=1&mkcid=1&mkrid=710-53481-19255-0&campid=5338464578&customid=SI_lamping%2Btorch&toolid=10049', ':Sporting Goods:Camping & Hiking:', 'Sporting Goods', 'Camping & Hiking', NULL),
(10, 1, 'USB Charging White+Yellow LED Headlamp Head Light Torch Zooma Motion Sensor Lamp', 'https://i.ebayimg.com/images/g/cHkAAOSwff9oI8Ec/s-l225.jpg', '5.99', 'https://www.ebay.co.uk/itm/317246564116?_skw=lamping+torch&hash=item49dd5e6714%3Ag%3AcHkAAOSwff9oI8Ec&mkevt=1&mkcid=1&mkrid=710-53481-19255-0&campid=5338464578&customid=SI_lamping%2Btorch&toolid=10049', ':Sporting Goods:Camping & Hiking:', 'Sporting Goods', 'Camping & Hiking', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(512) NOT NULL,
  `level` int(4) NOT NULL DEFAULT '0',
  `parentid` int(4) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `url`, `level`, `parentid`) VALUES
(1, 'Camping & Hiking', 'camping-hiking', 2, 6),
(2, 'Collectables', 'collectables', 1, NULL),
(3, 'DIY Tools & Workshop Equipment', 'diy-tools-workshop-equipment', 1, NULL),
(4, 'Home, Furniture & DIY', 'home-furniture-diy', 2, 3),
(5, 'Lamps, Lighting', 'lamps-lighting', 2, 2),
(6, 'Sporting Goods', 'sporting-goods', 1, NULL),
(7, 'Tools & Collectable Hardware', 'tools-collectable-hardware', 3, 0);

-- --------------------------------------------------------

--
-- Table structure for table `interne_tous`
--

CREATE TABLE `interne_tous` (
  `adresse` varchar(53) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_de_contenu` varchar(34) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code_http` int(11) DEFAULT NULL,
  `statut` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `indexabilit` varchar(19) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut_d_indexabilit` tinyint(1) DEFAULT NULL,
  `title_1` varchar(82) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `longueur_du_title_1` int(11) DEFAULT NULL,
  `largeur_en_pixels_du_title_1` int(11) DEFAULT NULL,
  `meta_description_1` varchar(140) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `longueur_de_la_meta_description_1` int(11) DEFAULT NULL,
  `largeur_en_pixels_de_la_meta_description_1` int(11) DEFAULT NULL,
  `meta_keywords_1` varchar(185) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `longueur_des_meta_keywords_1` int(11) DEFAULT NULL,
  `h1_1` varchar(89) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `longueur_du_h1_1` int(11) DEFAULT NULL,
  `h2_1` tinyint(1) DEFAULT NULL,
  `longueur_du_h2_1` tinyint(1) DEFAULT NULL,
  `meta_robots_1` varchar(23) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `balise_x_robots_1` tinyint(1) DEFAULT NULL,
  `meta_refresh_1` tinyint(1) DEFAULT NULL,
  `l_ment_de_lien_en_version_canonique_1` varchar(34) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rel_next_1` tinyint(1) DEFAULT NULL,
  `rel_prev_1` tinyint(1) DEFAULT NULL,
  `http_rel_next_1` tinyint(1) DEFAULT NULL,
  `http_rel_prev_1` tinyint(1) DEFAULT NULL,
  `l_ment_de_lien_amphtml` tinyint(1) DEFAULT NULL,
  `taille_octets` int(11) DEFAULT NULL,
  `transf_r_octets` int(11) DEFAULT NULL,
  `total_transf_r_en_octets` int(11) DEFAULT NULL,
  `co2_mg` decimal(13,3) DEFAULT NULL,
  `empreinte_carbone` tinyint(1) DEFAULT NULL,
  `nombre_de_mots` int(11) DEFAULT NULL,
  `nombre_de_phrases` int(11) DEFAULT NULL,
  `moyenne_de_mots_par_phrase` decimal(13,3) DEFAULT NULL,
  `score_de_lisibilit_de_flesch` decimal(13,3) DEFAULT NULL,
  `lisibilit` varchar(22) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ratio_texte` decimal(13,3) DEFAULT NULL,
  `crawl_profondeur` tinyint(1) DEFAULT NULL,
  `profondeur_du_dossier` tinyint(1) DEFAULT NULL,
  `link_score` tinyint(1) DEFAULT NULL,
  `liens_entrants` int(11) DEFAULT NULL,
  `liens_entrants_uniques` int(11) DEFAULT NULL,
  `liens_entrants_js_uniques` tinyint(1) DEFAULT NULL,
  `du_total` decimal(12,2) DEFAULT NULL,
  `liens_sortants` int(11) DEFAULT NULL,
  `liens_sortants_uniques` int(11) DEFAULT NULL,
  `liens_sortants_js_uniques` tinyint(1) DEFAULT NULL,
  `liens_sortants_externes` int(11) DEFAULT NULL,
  `liens_sortants_externes_uniques` int(11) DEFAULT NULL,
  `liens_sortants_js_externes_uniques` tinyint(1) DEFAULT NULL,
  `quasi_doublon_le_plus_proche` tinyint(1) DEFAULT NULL,
  `nombre_de_quasi_doublons` tinyint(1) DEFAULT NULL,
  `erreurs_d_orthographe` tinyint(1) DEFAULT NULL,
  `erreurs_de_grammaire` tinyint(1) DEFAULT NULL,
  `hachage` varchar(42) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `temps_de_r_ponse` decimal(13,3) DEFAULT NULL,
  `last_modified` tinyint(1) DEFAULT NULL,
  `url_de_redirection` tinyint(1) DEFAULT NULL,
  `type_de_redirection` tinyint(1) DEFAULT NULL,
  `cookies` tinyint(1) DEFAULT NULL,
  `language` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version_http` decimal(12,1) DEFAULT NULL,
  `producttitles_1` varchar(105) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producttitles_2` varchar(103) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producttitles_3` varchar(104) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producttitles_4` varchar(112) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producttitles_5` varchar(103) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producttitles_6` varchar(109) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producttitles_7` varchar(104) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producttitles_8` varchar(108) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producttitles_9` varchar(109) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producttitles_10` varchar(107) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producttitles_11` varchar(108) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producttitles_12` varchar(108) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producttitles_13` varchar(104) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producttitles_14` varchar(110) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producttitles_15` varchar(113) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producttitles_16` varchar(107) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producttitles_17` varchar(116) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producttitles_18` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producttitles_19` varchar(112) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producttitles_20` varchar(112) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_1` varchar(27) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_2` varchar(33) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_3` varchar(27) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_4` varchar(33) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_5` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_6` varchar(33) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_7` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_8` varchar(33) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_9` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_10` varchar(33) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_11` varchar(26) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_12` varchar(33) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_13` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_14` varchar(33) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_15` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_16` varchar(33) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_17` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_18` varchar(33) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_19` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_20` varchar(33) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_21` varchar(26) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_22` varchar(33) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_23` varchar(26) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_24` varchar(33) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_25` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_26` varchar(33) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_27` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_28` varchar(33) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_29` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_30` varchar(33) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_31` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_32` varchar(33) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_33` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_34` varchar(33) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_35` varchar(26) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_36` varchar(33) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_37` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_38` varchar(33) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_39` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prices_40` varchar(33) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images_1` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images_2` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images_3` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images_4` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images_5` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images_6` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images_7` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images_8` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images_9` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images_10` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images_11` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images_12` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images_13` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images_14` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images_15` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images_16` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images_17` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images_18` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images_19` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images_20` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lien_alternate_mobile` tinyint(1) DEFAULT NULL,
  `adresse_s_mantiquement_similaire_la_plus_proche` tinyint(1) DEFAULT NULL,
  `score_de_similarit_s_mantique` tinyint(1) DEFAULT NULL,
  `nbre_s_mantiquement_similaires` tinyint(1) DEFAULT NULL,
  `score_de_pertinence_s_mantique` tinyint(1) DEFAULT NULL,
  `adresse_cod_e_en_url` varchar(53) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `horodatage_du_crawl` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `interne_tous`
--

INSERT INTO `interne_tous` (`adresse`, `type_de_contenu`, `code_http`, `statut`, `indexabilit`, `statut_d_indexabilit`, `title_1`, `longueur_du_title_1`, `largeur_en_pixels_du_title_1`, `meta_description_1`, `longueur_de_la_meta_description_1`, `largeur_en_pixels_de_la_meta_description_1`, `meta_keywords_1`, `longueur_des_meta_keywords_1`, `h1_1`, `longueur_du_h1_1`, `h2_1`, `longueur_du_h2_1`, `meta_robots_1`, `balise_x_robots_1`, `meta_refresh_1`, `l_ment_de_lien_en_version_canonique_1`, `rel_next_1`, `rel_prev_1`, `http_rel_next_1`, `http_rel_prev_1`, `l_ment_de_lien_amphtml`, `taille_octets`, `transf_r_octets`, `total_transf_r_en_octets`, `co2_mg`, `empreinte_carbone`, `nombre_de_mots`, `nombre_de_phrases`, `moyenne_de_mots_par_phrase`, `score_de_lisibilit_de_flesch`, `lisibilit`, `ratio_texte`, `crawl_profondeur`, `profondeur_du_dossier`, `link_score`, `liens_entrants`, `liens_entrants_uniques`, `liens_entrants_js_uniques`, `du_total`, `liens_sortants`, `liens_sortants_uniques`, `liens_sortants_js_uniques`, `liens_sortants_externes`, `liens_sortants_externes_uniques`, `liens_sortants_js_externes_uniques`, `quasi_doublon_le_plus_proche`, `nombre_de_quasi_doublons`, `erreurs_d_orthographe`, `erreurs_de_grammaire`, `hachage`, `temps_de_r_ponse`, `last_modified`, `url_de_redirection`, `type_de_redirection`, `cookies`, `language`, `version_http`, `producttitles_1`, `producttitles_2`, `producttitles_3`, `producttitles_4`, `producttitles_5`, `producttitles_6`, `producttitles_7`, `producttitles_8`, `producttitles_9`, `producttitles_10`, `producttitles_11`, `producttitles_12`, `producttitles_13`, `producttitles_14`, `producttitles_15`, `producttitles_16`, `producttitles_17`, `producttitles_18`, `producttitles_19`, `producttitles_20`, `prices_1`, `prices_2`, `prices_3`, `prices_4`, `prices_5`, `prices_6`, `prices_7`, `prices_8`, `prices_9`, `prices_10`, `prices_11`, `prices_12`, `prices_13`, `prices_14`, `prices_15`, `prices_16`, `prices_17`, `prices_18`, `prices_19`, `prices_20`, `prices_21`, `prices_22`, `prices_23`, `prices_24`, `prices_25`, `prices_26`, `prices_27`, `prices_28`, `prices_29`, `prices_30`, `prices_31`, `prices_32`, `prices_33`, `prices_34`, `prices_35`, `prices_36`, `prices_37`, `prices_38`, `prices_39`, `prices_40`, `images_1`, `images_2`, `images_3`, `images_4`, `images_5`, `images_6`, `images_7`, `images_8`, `images_9`, `images_10`, `images_11`, `images_12`, `images_13`, `images_14`, `images_15`, `images_16`, `images_17`, `images_18`, `images_19`, `images_20`, `lien_alternate_mobile`, `adresse_s_mantiquement_similaire_la_plus_proche`, `score_de_similarit_s_mantique`, `nbre_s_mantiquement_similaires`, `score_de_pertinence_s_mantique`, `adresse_cod_e_en_url`, `horodatage_du_crawl`) VALUES
('https://www.for-sale.ie/', 'text/html; charset=utf-8', 200, 'OK', 'Indexable', NULL, 'All second hand products for sale in Ireland, on 1 single site', 62, 526, 'Personal free classified ads ie 100% free. Post your ad now and reach over 2 million buyers. Quick and simple.', 110, 672, 'Free online classifieds, classfied ads, free classified ads ie, Ireland online classified sites, free classified ads', 116, 'All second hand products', 24, NULL, 0, 'index, follow', NULL, NULL, 'https://www.for-sale.ie/', NULL, NULL, NULL, NULL, NULL, 31204, 8032, 8032, '3.130', NULL, 621, 167, '3.719', '83.449', 'Facile', '12.046', 0, 0, NULL, 20, 8, 0, '88.89', 109, 104, 0, 10, 9, 0, NULL, NULL, NULL, NULL, '66150715ee05ddc5cdc51eb375f2004b', '0.023', NULL, NULL, NULL, NULL, 'en', '1.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://www.for-sale.ie/', '2025-09-24 11:08:25'),
('https://www.for-sale.ie/s/pet-supplies', 'text/html; charset=utf-8', 200, 'OK', 'Indexable', NULL, 'Pet Supplies used', 17, 160, '23/09/2025 - Pet Supplies used', 30, 195, 'dwarf rabbits, bogwood, racing pigeons, hooded cat litter tray, dog play pen, corner fish tank, personalised pet memorial, bee hives,', 133, 'Pet Supplies used', 17, NULL, 0, 'index, follow', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 57650, 9827, 9827, '3.829', NULL, 754, 321, '2.349', '65.545', 'Normal', '8.979', 1, 1, NULL, 10, 9, 0, '100.00', 68, 64, 0, 0, 0, 0, NULL, NULL, NULL, NULL, '2490f436dc374862ec966cdc57a4e71c', '0.024', NULL, NULL, NULL, NULL, 'en', '1.1', 'Dexas blue large', 'Amazon basics dog', 'Lovpe dog collar', 'Suplutux inflatable collar', 'Panda medium pull', 'Doggiekit dog coat', 'Dog collar vintage', 'Adjustable cat harness', 'Pumyporeity dog costume', 'Dog harness black', 'Heywean waterproof dog', 'Elevant adjustable dog', 'Amazon basics pull', 'Xiaomi smart pet', 'Petsafe ricochet electronic', 'Tre ponti easy', 'Thepetlover tpl1500016 marting', 'Woiil dog coat', 'Waldseemüller dog harness', 'Snocyo dog bed', 'Price:  25 €', 'Product condition: Used', 'Price:  42 €', 'Product condition: Used', 'Price:  21 €', 'Product condition: Used', 'Price:  27 €', 'Product condition: Used', 'Price:  12 €', 'Product condition: Used', 'Price:  22 €', 'Product condition: Used', 'Price:  10 €', 'Product condition: Used', 'Price:  22 €', 'Product condition: Used', 'Price:  18 €', 'Product condition: Used', 'Price:  16 €', 'Product condition: Used', 'Price:  27 €', 'Product condition: Used', 'Price:  21 €', 'Product condition: Used', 'Price:  21 €', 'Product condition: Used', 'Price:  65 €', 'Product condition: Used', 'Price:  35 €', 'Product condition: Used', 'Price:  30 €', 'Product condition: Used', 'Price:  20 €', 'Product condition: Used', 'Price:  23 €', 'Product condition: Used', 'Price:  23 €', 'Product condition: Used', 'Price:  34 €', 'Product condition: Used', 'https://cdn.erowz.com/images/ebay/images/g/sDIAAeSwpUZok9Pk/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/2vsAAeSwHp9oUBaw/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/tT8AAeSwzVpok9Qc/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/Um8AAeSwmQZorHa4/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/E84AAeSwIs5ogocV/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/~wUAAeSwbjdoUBaT/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/ciwAAeSwPiJoswwa/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/EDMAAeSwMnZoUV1z/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/JVgAAeSwt4NoUBY5/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/wvQAAeSwyU1oUV0R/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/Fv0AAeSwP4hoUBX4/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/n24AAeSwkQtok9QV/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/xZIAAeSwWw5ouZgn/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/CwwAAeSwJptophok/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/JmIAAeSwx5ZoUBZ-/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/DdsAAeSwOhNowrEy/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/JUcAAeSwpgJoUBZ8/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/whIAAeSw8Z9otaRL/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/AEYAAeSw3oNoUBZy/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/1sEAAeSwwGRox~63/s-l225.jpg', NULL, NULL, NULL, NULL, NULL, 'https://www.for-sale.ie/s/pet-supplies', '2025-09-24 11:08:26'),
('https://www.for-sale.ie/jukebox', 'text/html; charset=utf-8', 200, 'OK', 'Indexable', NULL, 'Second hand Jukebox in Ireland | 32 used Jukeboxs', 49, 457, 'Jukebox for sale ✅ Itek jukebox player: 70.00 € | Seeburg 3w1 wall: 400.00 € | Jukebox music player: 629.99 €', 109, 673, 'jukebox, Free classifieds jukebox, classfied ads site, free online classified ads uk jukebox, UK online classified sitesjukebox', 127, 'Jukebox for sale on Ireland\'s largest auction and classifieds sites', 67, NULL, 0, 'index, follow', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 113926, 19157, 19157, '7.465', NULL, 1067, 413, '2.584', '77.511', 'Assez facile', '6.171', 1, 0, NULL, 6, 2, 0, '22.22', 81, 71, 0, 0, 0, 0, NULL, NULL, NULL, NULL, '6c50fc28d2be3855504e3b9a99c74751', '0.046', NULL, NULL, NULL, NULL, 'en', '1.1', 'Itek jukebox player', 'Seeburg 3w1 wall', 'Jukebox music player', 'Seeburg teardrop speaker', 'Vinyl jukebox record', 'Itek jukebox player', 'Sony cdp cx100', 'Video slot replacement', 'Thats call jukebox', 'Itek mini jukebox', 'Arafuna player portable', 'Call jukebox classics', 'Bantock nick egyptian', 'Krivine juke box', 'Jukebox gold timeless', 'Krivine juke box', 'Best pub jukebox', 'Xboom ck43n 300w', 'Primitives crash jukebox', 'Bruno mars unorthodox', 'Price:  70 €', 'Product condition: Used', 'Price:  400 €', 'Product condition: Used', 'Price:  630 €', 'Product condition: New', 'Price:  680 €', 'Product condition: Used', 'Price:  743 €', 'Product condition: Used', 'Price:  55 €', 'Product condition: New', 'Price:  180 €', 'Product condition: Used', 'Price:  197 €', 'Product condition: Used', 'Price:  11 €', 'Product condition: New', 'Price:  60 €', 'Product condition: Used', 'Price:  40 €', 'Product condition: New', 'Price: 9.60 €', 'Product condition: Used', 'Price:  43 €', 'Product condition: Used', 'Price:  161 €', 'Product condition: Used', 'Price:  28 €', 'Product condition: New', 'Price:  157 €', 'Product condition: Used', 'Price: 2.05 €', 'Product condition: Used', 'Price:  130 €', 'Product condition: New', 'Price: 2.99 €', 'Product condition: Used', 'Price: 3.99 €', 'Product condition: Used', 'https://cdn.erowz.com/images/ebay/images/g/NgwAAeSwbVhoxuAt/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/1EMAAeSwtkVn9Pw1/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/51s7AIsMeZL._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/lMgAAeSw-VRoCQ6K/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/51MkFjsjHXL._SL240_.jpg', 'https://cdn.erowz.com/images/amazon/images/I/41HYF45ivpL._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/VFQAAOSwcq1nLe~D/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/IFkAAOSww5ZixVhA/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/516G1kR-STL._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/v70AAOSw~FBm-VPF/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/41tic9hpTrL._SL240_.jpg', 'https://cdn.erowz.com/images/amazon/images/I/41GeilTARAL._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/V9EAAeSwu9FomIwX/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/VP0AAeSwT3tomInY/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/51JhSqqC6EL._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/VP0AAeSwT3tomInY/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/41pj3HHIibL._SL240_.jpg', 'https://cdn.erowz.com/images/amazon/images/I/41CUx5acaVL._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/f7wAAeSwnP9n3Z52/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/y~sAAeSwTopoSJQt/s-l225.jpg', NULL, NULL, NULL, NULL, NULL, 'https://www.for-sale.ie/jukebox', '2025-09-24 11:08:27'),
('https://www.for-sale.ie/s/toys-games', 'text/html; charset=utf-8', 200, 'OK', 'Indexable', NULL, 'Toys & Games used', 17, 176, '23/09/2025 - Toys & Games used', 30, 208, 'bburago 1 18, scrabble board game, power rangers dino com, vintage lion king soft toy, mobo rocking horse, madagascar toy, hexbug, furby boom,', 142, 'Toys & Games used', 17, NULL, 0, 'index, follow', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 64055, 11228, 11228, '4.375', NULL, 780, 291, '2.680', '69.188', 'Normal', '8.135', 1, 1, NULL, 10, 9, 0, '100.00', 97, 93, 0, 0, 0, 0, NULL, NULL, NULL, NULL, '27fc77ce51d67fd9304646fe04eefd60', '0.028', NULL, NULL, NULL, NULL, 'en', '1.1', 'Epoch games glutton', 'Play fun imc', 'Genuine lego games', 'Munchkin adventure time', 'Lego joblot', 'Munchkin adventure time', 'Marvel funko pop', 'Rambo black dragon', 'Lego lego bricks', 'Joe stretcher vintage', 'Palitoy action man', 'Joe 1985 lot', 'Joe estrela flash', 'Jacqueline wilson family', 'Batman animated series', 'Lord rings ringwraith', 'Wwe figures', 'Neca 2013 kick', 'Sour apples apples', 'Transformers titans return', 'Price:  50 €', 'Product condition: Used', 'Price:  82 €', 'Product condition: Used', 'Price: 2.00 €', 'Product condition: Used', 'Price:  50 €', 'Product condition: Used', 'Price:  80 €', 'Product condition: Used', 'Price:  50 €', 'Product condition: Used', 'Price:  35 €', 'Product condition: Used', 'Price:  20 €', 'Product condition: Used', 'Price:  40 €', 'Product condition: Used', 'Price:  18 €', 'Product condition: Used', 'Price:  40 €', 'Product condition: Used', 'Price:  50 €', 'Product condition: Used', 'Price:  55 €', 'Product condition: Used', 'Price:  23 €', 'Product condition: Used', 'Price:  20 €', 'Product condition: Used', 'Price:  30 €', 'Product condition: Used', 'Price: 1.00 €', 'Product condition: Used', 'Price:  22 €', 'Product condition: Used', 'Price:  25 €', 'Product condition: Used', 'Price:  45 €', 'Product condition: Used', 'https://cdn.erowz.com/images/ebay/images/g/dXEAAeSwIbBoUOkb/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/Y3wAAeSwMslowC1A/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/e6QAAeSwBUJozn0V/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/t9gAAeSwkAdo0Xtx/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/JDkAAeSwDN5ozBkS/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/qNkAAeSwz-po0Xjd/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/WrAAAeSwkSxoyUw~/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/sf0AAeSwUqVoyHZq/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/RssAAeSwjVFovcyi/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/MxsAAeSwa2NoyHkh/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/a8gAAeSw5Gpo0FIS/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/5r8AAeSwoVloweIn/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/iKkAAOSwRklnlkFs/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/sMkAAOSwg8Bj4hdk/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/NM8AAeSwuDZorLMi/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/2f8AAeSwHLpojlmn/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/IrEAAeSwLF9osxqo/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/yxcAAeSwt69o0UXf/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/RAQAAOSw25djTCcC/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/AtAAAeSw4UBophJ7/s-l225.jpg', NULL, NULL, NULL, NULL, NULL, 'https://www.for-sale.ie/s/toys-games', '2025-09-24 11:08:29'),
('https://www.for-sale.ie/007-memorabilia', 'text/html; charset=utf-8', 200, 'OK', 'Indexable', NULL, 'Second hand 007 Memorabilia in Ireland | 55 used 007 Memorabilias', 65, 607, '007 memorabilia for sale ✅ Mercedes genuine nox: 119.00 € | 007 algerian loveknot: 44.81 € | James bond 007: 6.95 €', 115, 725, '007 memorabilia, Free classifieds 007 memorabilia, classfied ads site, free online classified ads uk 007 memorabilia, UK online classified sites007 memorabilia', 159, '007 Memorabilia for sale on Ireland\'s largest auction and classifieds sites', 75, NULL, 0, 'index, follow', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 96495, 18078, 18078, '7.045', NULL, 826, 352, '2.347', '74.993', 'Assez facile', '5.889', 1, 0, NULL, 7, 2, 0, '22.22', 100, 89, 0, 0, 0, 0, NULL, NULL, NULL, NULL, 'cc8792ccdf3ddae4da85f8510219f080', '0.045', NULL, NULL, NULL, NULL, 'en', '1.1', 'Mercedes genuine nox', '007 algerian loveknot', 'James bond 007', 'Tuvalu 2023 james', 'Spectre unframed spy', 'Pyramid international james', 'Daniel craig signed', 'Sell separately bandai', 'Thor team valkyrie', 'Stray kids 4th', 'Royal mint six', 'Downton abbey signed', 'Goonies signed movie', 'Ive 4th mini', 'Nct dream dreamiez', 'James bond 007', 'Graffiti james bond', 'Coincard pounds 2024', '007 james bond', 'Signed daniel craig', 'Price:  119 €', 'Product condition: Used', 'Price:  45 €', 'Product condition: Used', 'Price: 6.95 €', 'Product condition: New', 'Price:  47 €', 'Product condition: Used', 'Price: 6.74 €', 'Product condition: New', 'Price: 8.99 €', 'Product condition: New', 'Price:  180 €', 'Product condition: Used', 'Price: 6.51 €', 'Product condition: Used', 'Price:  13 €', 'Product condition: New', 'Price:  19 €', 'Product condition: Used', 'Price:  21 €', 'Product condition: Used', 'Price:  156 €', 'Product condition: Used', 'Price:  156 €', 'Product condition: Used', 'Price:  41 €', 'Product condition: Used', 'Price:  96 €', 'Product condition: Used', 'Price:  79 €', 'Product condition: Used', 'Price: 7.73 €', 'Product condition: Used', 'Price:  24 €', 'Product condition: Used', 'Price:  70 €', 'Product condition: New', 'Price:  250 €', 'Product condition: New', 'https://cdn.erowz.com/images/ebay/images/g/c50AAOSw5ppnQeM2/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/4tkAAeSwqYpohCbD/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/51WTb9ZGdXL._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/Up8AAOSwHtxoW8Ka/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/41N08SXw1DL._SL240_.jpg', 'https://cdn.erowz.com/images/amazon/images/I/41TDqhHLJGL._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/MHkAAOSwTWRnE9EG/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/ICgAAOSwwZJn6sQ1/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/41BzkdD-ALL._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/a~wAAeSweYporXRW/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/9jcAAeSwjRJod7us/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/7I0AAeSwJAxo0CLe/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/XkcAAeSwRf9oyIMN/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/bvsAAeSw5b1oryqA/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/4n0AAeSwZ1Noyj6J/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/i3wAAOSwPUVnIRTQ/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/D0QAAOSwJ-FkDdBG/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/mpwAAeSw~7Vn-~Jy/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/FmEAAOSwRCtiDiqW/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/YCsAAOSwL1loWY5K/s-l225.jpg', NULL, NULL, NULL, NULL, NULL, 'https://www.for-sale.ie/007-memorabilia', '2025-09-24 11:08:30'),
('https://www.for-sale.ie/tower-scaffold', 'text/html; charset=utf-8', 200, 'OK', 'Indexable', NULL, 'Second hand Tower Scaffold in Ireland | 57 used Tower Scaffolds', 63, 577, 'Tower scaffold for sale ✅ Aluminium tower scaffold: 97.49 € | Aluminium scaffold tower: 2.86 € | Home master diy: 314.99 €', 122, 762, 'tower scaffold, Free classifieds tower scaffold, classfied ads site, free online classified ads uk tower scaffold, UK online classified sitestower scaffold', 155, 'Tower Scaffold for sale on Ireland\'s largest auction and classifieds sites', 74, NULL, 0, 'index, follow', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100987, 19798, 19798, '7.715', NULL, 1057, 402, '2.629', '74.105', 'Assez facile', '7.186', 1, 0, NULL, 7, 2, 0, '22.22', 101, 90, 0, 0, 0, 0, NULL, NULL, NULL, NULL, '59dfa9dc8f95678b23fca4d72ebbffdf', '0.044', NULL, NULL, NULL, NULL, 'en', '1.1', 'Aluminium tower scaffold', 'Aluminium scaffold tower', 'Home master diy', 'Aluminium tower scaffold', 'Hill top fabrications', 'Diy aluminium scaffold', 'Alluminium podium scaffold', 'Banded unbanded reclaimed', 'Classic 6.3m diy', 'Uts scaffold tower', 'Trade master professional', 'Toptower classic 7.2m', 'Workstation barebone inc', 'Genuine boss youngman', 'Toptower classic 3.8m', 'Reclaimed scaffolding boards', 'Classic 5.5m diy', 'Aluminium tower scaffolding', 'Z240 tower workstation', 'Tower scaffold agr', 'Price:  97 €', 'Product condition: Used', 'Price: 2.86 €', 'Product condition: Used', 'Price:  315 €', 'Product condition: New', 'Price:  115 €', 'Product condition: Used', 'Price:  295 €', 'Product condition: New', 'Price:  390 €', 'Product condition: New', 'Price:  143 €', 'Product condition: Used', 'Price:  41 €', 'Product condition: Used', 'Price:  443 €', 'Product condition: New', 'Price:  72 €', 'Product condition: Used', 'Price: 1 300 €', 'Product condition: New', 'Price:  569 €', 'Product condition: New', 'Price:  549 €', 'Product condition: Used', 'Price:  88 €', 'Product condition: Used', 'Price:  279 €', 'Product condition: New', 'Price:  11 €', 'Product condition: Used', 'Price:  393 €', 'Product condition: New', 'Price:  115 €', 'Product condition: Used', 'Price:  120 €', 'Product condition: Used', 'Price:  34 €', 'Product condition: Used', 'https://cdn.erowz.com/images/ebay/images/g/GygAAeSwualn8~Zs/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/SfMAAOSw8ixhStKa/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/51O5nJDEn0L._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/9RQAAOSwhEBiZZBv/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/313iAn-AxKL._SL240_.jpg', 'https://cdn.erowz.com/images/amazon/images/I/41HpkMQUG-L._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/Nb0AAOSwPwZkAy89/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/J6kAAOSwljhmPJAk/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/31SaEVOHaXL._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/xY0AAOSw26Nks5l8/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/61SvWAXnUnL._SL240_.jpg', 'https://cdn.erowz.com/images/amazon/images/I/31mjD4sxfiL._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/hVoAAeSwCYZozBX5/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/QoAAAOSwWzlhkMv6/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/31iFKQvQs5L._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/vqgAAOSwANZng7lB/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/31gCWLwpjOL._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/jNgAAOSwwxFmeF8r/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/Ft4AAeSww8losfju/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/tOsAAOSwSq5nbael/s-l225.jpg', NULL, NULL, NULL, NULL, NULL, 'https://www.for-sale.ie/tower-scaffold', '2025-09-24 11:08:31'),
('https://www.for-sale.ie/4-stud-alloy-wheels', 'text/html; charset=utf-8', 200, 'OK', 'Indexable', NULL, 'Second hand 4 Stud Alloy Wheels in Ireland | 60 used 4 Stud Alloy Wheels', 72, 649, '4 stud alloy wheels for sale ✅ Black gloss rear: 13.95 € | Pump nozzle unit: 235.95 € | 5mm aluminum alloy: 10.99 €', 115, 705, '4 stud alloy wheels, Free classifieds 4 stud alloy wheels, classfied ads site, free online classified ads uk 4 stud alloy wheels, UK online classified sites4 stud alloy wheels', 175, '4 Stud Alloy Wheels for sale on Ireland\'s largest auction and classifieds sites', 79, NULL, 0, 'index, follow', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 101453, 19232, 19232, '7.494', NULL, 1100, 391, '2.813', '79.848', 'Assez facile', '7.240', 1, 0, NULL, 7, 2, 0, '22.22', 101, 90, 0, 0, 0, 0, NULL, NULL, NULL, NULL, 'b5264694704935843c85eec046210607', '0.046', NULL, NULL, NULL, NULL, 'en', '1.1', 'Black gloss rear', 'Pump nozzle unit', '5mm aluminum alloy', 'Injection nozzle injector', 'Laroal pack 10mm', 'Tbest kart front', 'Shirt personalizzata peugeot', 'Kit forcella frizione', '4pcs wheel spacers', 'Oem 2004 2007', '21x7 atv front', '21x7 front wheel', 'Lot spoke wheels', 'Vdo download key', '10mm shlpdfm wheel', 'Mercedes benz genuine', '10mm wheel spacers', 'Yuchenshlp 4pcs 10mm', 'Nvidia geforce rtx', 'Electric servo steering', 'Price:  14 €', 'Product condition: New', 'Price:  236 €', 'Product condition: Used', 'Price:  11 €', 'Product condition: New', 'Price:  299 €', 'Product condition: Used', 'Price:  17 €', 'Product condition: New', 'Price:  11 €', 'Product condition: New', 'Price:  22 €', 'Product condition: Used', 'Price:  120 €', 'Product condition: Used', 'Price: 9.99 €', 'Product condition: New', 'Price:  201 €', 'Product condition: Used', 'Price:  22 €', 'Product condition: New', 'Price:  22 €', 'Product condition: New', 'Price: 5.00 €', 'Product condition: Used', 'Price:  210 €', 'Product condition: Used', 'Price:  17 €', 'Product condition: Used', 'Price:  249 €', 'Product condition: Used', 'Price:  18 €', 'Product condition: New', 'Price:  10 €', 'Product condition: Used', 'Price:  340 €', 'Product condition: Used', 'Price:  329 €', 'Product condition: Used', 'https://cdn.erowz.com/images/ebay/images/g/GtoAAOSweVNnqIOz/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/2GIAAOSw-KJmFWJX/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/410P23dNmmL._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/Si0AAOSwomZkxprv/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/41WQ4GNSj6L._SL240_.jpg', 'https://cdn.erowz.com/images/amazon/images/I/411tEbNx8NL._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/u6IAAeSwDqZoreZs/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/FiUAAOSwnQdnsKpY/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/41g19ixF2GL._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/1K0AAeSwZ9Foun4H/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/31JjCBeJyjL._SL240_.jpg', 'https://cdn.erowz.com/images/amazon/images/I/3162FZ1KpCL._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/BkEAAeSwGQ9oyAsS/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/ojcAAeSwAvRogHVZ/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/415d5Yhih-L._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/jm4AAOSw0GFms2bx/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/41InapKSvaL._SL240_.jpg', 'https://cdn.erowz.com/images/amazon/images/I/41q4jM403ZL._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/DAUAAeSw0E1ozIbX/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/x2AAAOSw1ZBbpNHA/s-l225.jpg', NULL, NULL, NULL, NULL, NULL, 'https://www.for-sale.ie/4-stud-alloy-wheels', '2025-09-24 11:08:32'),
('https://www.for-sale.ie/s/cookies', 'text/html; charset=utf-8', 200, 'OK', 'Indexable', NULL, 'Cookie and privacy', 18, 167, NULL, 0, 0, NULL, 0, 'Cookies', 7, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 21605, 6744, 6744, '2.628', NULL, 600, 106, '5.660', '63.756', 'Normal', '18.928', 1, 1, NULL, 1, 1, 0, '11.11', 36, 36, 0, 6, 6, 0, NULL, NULL, NULL, NULL, '4f95f6e1800088383fffa76d7a1eeae8', '0.025', NULL, NULL, NULL, NULL, 'en', '1.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'https://www.for-sale.ie/s/cookies', '2025-09-24 11:08:33'),
('https://www.for-sale.ie/log-cabin', 'text/html; charset=utf-8', 200, 'OK', 'Indexable', NULL, 'Second hand Log Cabin in Ireland | 55 used Log Cabins', 53, 485, 'Log cabin for sale ✅ Garden office: 12400.00 € | Residential log cabins: 28550.00 € | Barrettine log cabin: 40.4 €', 114, 686, 'log cabin, Free classifieds log cabin, classfied ads site, free online classified ads uk log cabin, UK online classified siteslog cabin', 135, 'Log Cabin for sale on Ireland\'s largest auction and classifieds sites', 69, NULL, 0, 'index, follow', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 94571, 17938, 17938, '6.990', NULL, 805, 360, '2.236', '79.294', 'Assez facile', '5.897', 1, 0, NULL, 7, 2, 0, '22.22', 73, 62, 0, 0, 0, 0, NULL, NULL, NULL, NULL, 'e8bda915e8f4eeb6e2f9b4a6002af4b6', '0.045', NULL, NULL, NULL, NULL, 'en', '1.1', 'Garden office', 'Residential log cabins', 'Barrettine log cabin', 'Wood stove fan', 'Billyoh bella summer', 'Billyoh petra 20x10', 'Transmission control unit', 'Log cabin spruce', 'Billyoh corner summerhouse', 'Playmobil 3826 5039', 'Tiny homes sale', 'Billyoh tessa 12x10', 'Gen. grants log', 'Haystack lake oregon', 'Log cabin book', 'Log cabin palmer', 'Billyoh 16x8 switch', 'Rowlinson oasis cabin', 'Log cabin postcard', 'Vtg log cabin', 'Price: 12 400 €', 'Product condition: New', 'Price: 28 550 €', 'Product condition: New', 'Price:  40 €', 'Product condition: New', 'Price:  26 €', 'Product condition: New', 'Price:  942 €', 'Product condition: New', 'Price: 1 880 €', 'Product condition: New', 'Price:  314 €', 'Product condition: Used', 'Price: 6.13 €', 'Product condition: Used', 'Price:  992 €', 'Product condition: New', 'Price: 1.82 €', 'Product condition: Used', 'Price: 7 248 €', 'Product condition: New', 'Price: 1 372 €', 'Product condition: New', 'Price: 5.16 €', 'Product condition: Used', 'Price: 7.82 €', 'Product condition: Used', 'Price: 9.44 €', 'Product condition: Used', 'Price: 7.07 €', 'Product condition: Used', 'Price:  961 €', 'Product condition: New', 'Price: 3 172 €', 'Product condition: New', 'Price: 8.01 €', 'Product condition: Used', 'Price:  219 €', 'Product condition: Used', 'https://cdn.erowz.com/images/ebay/images/g/g9MAAeSwjPloO1DZ/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/StcAAOSwNghk4j0R/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/51kMvRuOdwL._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/ogcAAeSwUxpouH21/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/51HFPqv22GL._SL240_.jpg', 'https://cdn.erowz.com/images/amazon/images/I/41AiR72nj4L._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/VEwAAOSwBeRm7UOg/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/r3EAAeSwltlozZpy/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/4199HT6f-HL._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/AlsAAOSw2hhcxffP/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/61tZVjbICJL._SL240_.jpg', 'https://cdn.erowz.com/images/amazon/images/I/414uL72MG8L._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/H44AAOSwITxl1OJ9/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/JAAAAOSw-vlVktcF/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/51AbiMH27OL._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/ajYAAOSwOz9mo-4K/s-l225.jpg', 'https://cdn.erowz.com/images/amazon/images/I/41QXvZP4S6L._SL240_.jpg', 'https://cdn.erowz.com/images/amazon/images/I/516FbxWUU7L._SL240_.jpg', 'https://cdn.erowz.com/images/ebay/images/g/icQAAeSwiHhocmlh/s-l225.jpg', 'https://cdn.erowz.com/images/ebay/images/g/hy4AAeSwGW5oz3wO/s-l225.jpg', NULL, NULL, NULL, NULL, NULL, 'https://www.for-sale.ie/log-cabin', '2025-09-24 11:08:34'),
(NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `keywords`
--

CREATE TABLE `keywords` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `keyword_name` varchar(255) NOT NULL,
  `keywordURL` varchar(1024) NOT NULL,
  `homepage` tinyint(1) NOT NULL DEFAULT '0',
  `main_category` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `keywords`
--

INSERT INTO `keywords` (`id`, `keyword_name`, `keywordURL`, `homepage`, `main_category`) VALUES
(64028, '0 001 scales', '0-001-scales', 1, 0),
(120956, '0 5 carat diamond ring', '0-5-carat-diamond-ring', 0, 5),
(158905, 'microscopes', 'microscopes', 0, 7),
(220941, '0 a6 2 automatic avant audi', '0-a6-2-automatic-avant-audi', 0, 0),
(227340, '0 bundle boys 6 months 3', '0-bundle-boys-6-months-3', 1, 0),
(227841, '0 diesel peugeot 307 cc 2', '0-diesel-peugeot-307-cc-2', 0, 0),
(228988, '0 3 jaguar v6 2004 xj', '0-3-jaguar-v6-2004-xj', 0, 7),
(229216, '0 3 months baby boy clothes', '0-3-months-baby-boy-clothes', 0, 0),
(232522, '&et clothing', 'et-clothing', 0, 0),
(241574, '\"moto c plus\" + tesco', 'moto-c-plus-tesco', 0, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ads`
--
ALTER TABLE `ads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_keyword` (`keyword_id`),
  ADD KEY `idx_price` (`price`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `keywords`
--
ALTER TABLE `keywords`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_keyword_name` (`keyword_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ads`
--
ALTER TABLE `ads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4830756;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `keywords`
--
ALTER TABLE `keywords`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=261884;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ads`
--
ALTER TABLE `ads`
  ADD CONSTRAINT `fk_ads_keyword` FOREIGN KEY (`keyword_id`) REFERENCES `keywords` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
