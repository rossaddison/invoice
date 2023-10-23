-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 16, 2022 at 07:47 AM
-- Server version: 8.0.27
-- PHP Version: 8.0.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `yii-invoice`
--

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

DROP TABLE IF EXISTS `client`;
CREATE TABLE IF NOT EXISTS `client` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `client_date_modified` datetime NOT NULL,
  `client_name` text NOT NULL,
  `client_address_1` text,
  `client_address_2` text,
  `client_city` text,
  `client_state` text,
  `client_zip` text,
  `client_country` text,
  `client_phone` text,
  `client_fax` text,
  `client_mobile` text,
  `client_email` text,
  `client_web` text,
  `client_vat_id` text,
  `client_tax_code` text,
  `client_language` varchar(151) DEFAULT NULL,
  `client_active` tinyint(1) NOT NULL DEFAULT '0',
  `client_surname` varchar(151) DEFAULT NULL,
  `client_avs` varchar(16) DEFAULT NULL,
  `client_insurednumber` varchar(151) DEFAULT NULL,
  `client_veka` varchar(30) DEFAULT NULL,
  `client_gender` tinyint NOT NULL DEFAULT '0',
  `client_birthdate` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_custom`
--

DROP TABLE IF EXISTS `client_custom`;
CREATE TABLE IF NOT EXISTS `client_custom` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `custom_field_id` int NOT NULL,
  `value` text,
  PRIMARY KEY (`id`),
  KEY `client_custom_index_client_id_61ef006c9dde7` (`client_id`),
  KEY `client_custom_index_custom_field_id_61ef006c9de47` (`custom_field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_note`
--

DROP TABLE IF EXISTS `client_note`;
CREATE TABLE IF NOT EXISTS `client_note` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `date` date NOT NULL,
  `note` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `client_note_index_client_id_61ef006c9de9a` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comment`
--

DROP TABLE IF EXISTS `comment`;
CREATE TABLE IF NOT EXISTS `comment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `public` tinyint(1) NOT NULL DEFAULT '0',
  `content` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL,
  `published_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `user_id` int NOT NULL,
  `post_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `comment_index_user_id_61eef7f753bd6` (`user_id`),
  KEY `comment_index_post_id_61eef7f753c28` (`post_id`),
  KEY `comment_index_public_published_at_61eef7f754079` (`public`,`published_at`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `comment`
--

INSERT INTO `comment` (`id`, `public`, `content`, `created_at`, `updated_at`, `published_at`, `deleted_at`, `user_id`, `post_id`) VALUES
(1, 0, 'I get\" is the same year for such dainties would not stoop? Soup of the same solemn tone, \'For the Duchess. \'Everything\'s got a moral, if only you can have no idea what you\'re at!\" You know the song, perhaps?\' \'I\'ve heard something like it,\' said Alice to herself, as she listened, or seemed to be ashamed of yourself for asking such a curious dream, dear, certainly: but now run in to your places!\' shouted the Queen, tossing her head pressing against the.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', NULL, NULL, 4, 1),
(2, 1, 'Forty-two. ALL PERSONS MORE THAN A MILE HIGH TO LEAVE THE COURT.\' Everybody looked at her, and the choking of the room again, no wonder she felt that it was the first minute or two, which gave the Pigeon had finished. \'As if it had VERY long claws and a sad tale!\' said the Dormouse. \'Write that down,\' the King put on her hand, and made believe to worry it; then Alice, thinking it was written to nobody, which.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', '2021-05-06 08:31:40', NULL, 5, 2),
(3, 1, 'White Rabbit, who said in a low voice, \'Why the fact is, you see, because some of the trial.\' \'Stupid things!\'.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', '2022-01-15 20:52:52', NULL, 5, 4),
(4, 1, 'Duchess: \'what a clear way you go,\' said the King. Here one of them can explain it,\' said Alice, \'we learned French and music.\' \'And washing?\' said the King said gravely, \'and go on in the air. This time there could be beheaded, and that he had a bone in his sleep, \'that \"I like what I get\" is the.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', '2021-10-23 22:49:57', NULL, 5, 1),
(5, 1, 'There was nothing on it were nine o\'clock in the schoolroom, and though this was her dream:-- First, she dreamed of little birds and beasts, as well as she had found the fan and gloves. \'How queer it seems,\'.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', '2021-01-26 10:41:41', NULL, 5, 5),
(6, 0, 'For anything tougher than suet; Yet you turned a corner, \'Oh my ears and whiskers, how late it\'s getting!\' She was walking hand in her pocket) till she too began dreaming after a minute or two she walked off, leaving Alice alone with the dream of Wonderland of long ago: and how she.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', NULL, NULL, 5, 5),
(7, 1, 'Alice. \'I\'ve read that in about half no time! Take your choice!\' The Duchess took no notice of them say, \'Look out now, Five! Don\'t go splashing paint over me like a stalk out of sight: then it watched the Queen was in a great interest in questions of eating.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', '2021-09-13 19:43:04', NULL, 6, 2),
(8, 1, 'Alice said nothing; she had found the fan and two or three pairs of tiny white kid gloves in one hand and a fan! Quick, now!\' And Alice was very uncomfortable, and, as the March Hare. \'I didn\'t know how to get through was more hopeless than ever: she sat down again very sadly and quietly, and looked anxiously round, to make out which were the verses the White Rabbit blew three.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', '2021-03-30 16:39:08', NULL, 6, 3),
(9, 1, 'He sent them word I had to stop and untwist it. After a time she saw maps and pictures hung upon pegs. She took down a jar from one of the song. \'What trial is it?\' Alice panted as she.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', '2021-12-13 05:24:50', NULL, 6, 3),
(10, 1, 'I think.\' And she opened the door of which was a queer-shaped little creature, and held out its arms folded, frowning like a serpent. She had not a regular rule: you invented it just now.\' \'It\'s the stupidest tea-party I ever heard!\' \'Yes, I think I should think very likely true.) Down, down, down. Would.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', '2021-08-26 11:33:17', NULL, 6, 4),
(11, 0, 'I like\"!\' \'You might just as she did not seem to put down her anger as well as the Rabbit, and had to be otherwise.\"\' \'I think you might catch a bat, and that\'s very like having a game of croquet she was quite impossible to say when I got up and went down on one knee. \'I\'m a poor man, your.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', NULL, NULL, 6, 5),
(12, 0, 'So she began nursing her child again, singing a sort of use in knocking,\' said the Gryphon. \'It\'s all her fancy, that: he hasn\'t got no sorrow, you know. So you see, Alice had got so close to her full size by this time, sat down at them, and it\'ll sit up and said, very gravely, \'I think, you ought to have lessons to learn! Oh, I shouldn\'t.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', NULL, NULL, 6, 5),
(13, 1, 'Queen was to find any. And yet I don\'t believe you do either!\' And the moral of that dark hall, and close to her full size by this time, and was coming back to the other queer noises, would change to tinkling sheep-bells, and the roof was thatched with fur. It was the White Rabbit put on her toes when they liked, and left off writing on his knee, and looking anxiously round to see how he did with the Queen,\' and she thought it must make me.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', '2021-04-07 23:28:11', NULL, 2, 2),
(14, 1, 'I think--\' (she was so ordered about in the middle, being held up by wild beasts and other unpleasant things, all because they WOULD go with Edgar.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', '2021-04-20 17:42:56', NULL, 2, 1),
(15, 1, 'Alice)--\'and perhaps you haven\'t found it advisable--\"\' \'Found WHAT?\' said the Gryphon. \'Well, I hardly know--No more, thank ye; I\'m better now--but I\'m a hatter.\' Here the Queen put on her face like the look of things at all, at all!\' \'Do as I used--and I don\'t want YOU with us!\"\' \'They were learning to draw,\' the Dormouse followed him: the March Hare said in a low, trembling voice. \'There\'s more evidence to come upon.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', '2021-07-18 19:22:53', NULL, 3, 4),
(16, 1, 'Pray how did you manage on the breeze that followed them, the melancholy words:-- \'Soo--oop of the water, and seemed to think that there was a large cauldron which seemed to be two people! Why, there\'s hardly room for this, and she walked up towards it rather timidly, as she ran; but the Rabbit was still.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', '2021-07-06 05:59:08', NULL, 3, 5),
(17, 0, 'Alice whispered, \'that it\'s done by everybody minding their own business!\' \'Ah, well! It means much the same.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', NULL, NULL, 4, 3),
(18, 0, 'IN the well,\' Alice said nothing: she had someone to listen to her, one on each side, and opened their eyes and mouths so VERY much out of the house, and the blades of.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', NULL, NULL, 4, 3),
(19, 1, 'Alice thought this a very respectful tone, but frowning and making quite a new idea to Alice, they all crowded round her, about four inches deep and reaching half down the hall. After a while she was nine feet high, and was delighted to find quite a crowd of little Alice herself.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', '2021-10-08 09:59:34', NULL, 4, 4),
(20, 1, 'Mock Turtle. \'Very much indeed,\' said Alice. \'That\'s very curious.\' \'It\'s all her riper years, the simple rules their friends had taught them: such as, that a red-hot poker will burn you if you were me?\' \'Well, perhaps your feelings may be different,\' said Alice; \'but a.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', '2021-11-24 15:25:03', NULL, 4, 4),
(21, 1, 'But here, to Alice\'s great surprise, the Duchess\'s voice died away, even in the sun. (IF you don\'t know what it might tell her something about.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', '2021-11-13 02:15:04', NULL, 4, 5);

-- --------------------------------------------------------

--
-- Table structure for table `company`
--

DROP TABLE IF EXISTS `company`;
CREATE TABLE IF NOT EXISTS `company` (
  `id` int NOT NULL AUTO_INCREMENT,
  `current` tinyint NOT NULL DEFAULT '0',
  `name` text,
  `address_1` text,
  `address_2` text,
  `city` text,
  `state` text,
  `zip` text,
  `country` text,
  `phone` text,
  `fax` text,
  `email` text,
  `web` text,
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company_private`
--

DROP TABLE IF EXISTS `company_private`;
CREATE TABLE IF NOT EXISTS `company_private` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `vat_id` text,
  `tax_code` text,
  `iban` varchar(34) DEFAULT NULL,
  `gln` bigint DEFAULT NULL,
  `rcc` varchar(7) DEFAULT NULL,
  `date_created` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `company_private_index_company_id_61ef006c9dee0` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `custom_field`
--

DROP TABLE IF EXISTS `custom_field`;
CREATE TABLE IF NOT EXISTS `custom_field` (
  `id` int NOT NULL AUTO_INCREMENT,
  `table` varchar(50) DEFAULT NULL,
  `label` varchar(50) DEFAULT NULL,
  `type` varchar(151) NOT NULL DEFAULT 'TEXT',
  `location` int DEFAULT '0',
  `order` int DEFAULT '999',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `custom_value`
--

DROP TABLE IF EXISTS `custom_value`;
CREATE TABLE IF NOT EXISTS `custom_value` (
  `id` int NOT NULL AUTO_INCREMENT,
  `custom_field_id` int NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `custom_value_index_custom_field_id_61ef006c9df32` (`custom_field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_template`
--

DROP TABLE IF EXISTS `email_template`;
CREATE TABLE IF NOT EXISTS `email_template` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email_template_title` text,
  `email_template_type` varchar(151) DEFAULT NULL,
  `email_template_body` longtext NOT NULL,
  `email_template_subject` text,
  `email_template_from_name` text,
  `email_template_from_email` text,
  `email_template_cc` text,
  `email_template_bcc` text,
  `email_template_pdf_template` varchar(151) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `family`
--

DROP TABLE IF EXISTS `family`;
CREATE TABLE IF NOT EXISTS `family` (
  `id` int NOT NULL AUTO_INCREMENT,
  `family_name` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `family`
--

INSERT INTO `family` (`id`, `family_name`) VALUES
(1, 'Tangible Physical Products'),
(2, 'Intangible Service Products');

-- --------------------------------------------------------

--
-- Table structure for table `gentor`
--

DROP TABLE IF EXISTS `gentor`;
CREATE TABLE IF NOT EXISTS `gentor` (
  `id` int NOT NULL AUTO_INCREMENT,
  `route_prefix` varchar(20) NOT NULL,
  `route_suffix` varchar(20) NOT NULL,
  `camelcase_capital_name` varchar(20) NOT NULL,
  `small_singular_name` varchar(20) NOT NULL,
  `small_plural_name` varchar(20) NOT NULL,
  `namespace_path` varchar(100) NOT NULL,
  `controller_layout_dir` varchar(100) NOT NULL,
  `controller_layout_dir_dot_path` varchar(100) NOT NULL,
  `repo_extra_camelcase_name` varchar(50) NOT NULL,
  `paginator_next_page_attribute` varchar(50) NOT NULL,
  `constrain_index_field` varchar(50) NOT NULL,
  `filter_field` varchar(20) NOT NULL,
  `filter_field_start_position` tinyint DEFAULT NULL,
  `filter_field_end_position` tinyint DEFAULT NULL,
  `pre_entity_table` varchar(50) NOT NULL,
  `modified_include` tinyint(1) NOT NULL DEFAULT '0',
  `created_include` tinyint(1) NOT NULL DEFAULT '0',
  `updated_include` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_include` tinyint(1) NOT NULL DEFAULT '0',
  `keyset_paginator_include` tinyint(1) NOT NULL DEFAULT '0',
  `offset_paginator_include` tinyint(1) NOT NULL DEFAULT '0',
  `flash_include` tinyint(1) NOT NULL DEFAULT '1',
  `headerline_include` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gentor_relation`
--

DROP TABLE IF EXISTS `gentor_relation`;
CREATE TABLE IF NOT EXISTS `gentor_relation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lowercasename` text,
  `camelcasename` text,
  `view_field_name` text,
  `gentor_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gentor_relation_index_gentor_id_61ef006c9df7c` (`gentor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group`
--

DROP TABLE IF EXISTS `group`;
CREATE TABLE IF NOT EXISTS `group` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `identifier_format` varchar(255) NOT NULL,
  `next_id` int NOT NULL,
  `left_pad` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `group`
--

INSERT INTO `group` (`id`, `name`, `identifier_format`, `next_id`, `left_pad`) VALUES
(3, 'Invoice Default', 'INV{{{id}}}', 1, 0),
(4, 'Quote Default', 'QUO{{{id}}}', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `identity`
--

DROP TABLE IF EXISTS `identity`;
CREATE TABLE IF NOT EXISTS `identity` (
  `id` int NOT NULL AUTO_INCREMENT,
  `auth_key` varchar(32) NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `identity_index_user_id_61eef7f7537e6` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `identity`
--

INSERT INTO `identity` (`id`, `auth_key`, `user_id`) VALUES
(1, 'lG9XumtnPnupVZ5KzYTl8iUavBMKuacx', 1),
(2, 'xVSkfXAIINhhuQaJ99NXRC7EyUhLxQ7r', 2),
(3, 'R0zmG_u7XLs6qk4UEW2-goIe-hRGyl4v', 3),
(4, '10oXoCIEvxuB6CLR8ZVFAvaKqZaNvUzl', 4),
(5, 'nQesXBkBetQHn8_qZvyn7smO9tdzSemz', 5),
(6, 'I-pd4LNcnpB9M50tPjkX5GNeiIW82N_N', 6);

-- --------------------------------------------------------

--
-- Table structure for table `import`
--

DROP TABLE IF EXISTS `import`;
CREATE TABLE IF NOT EXISTS `import` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inv`
--

DROP TABLE IF EXISTS `inv`;
CREATE TABLE IF NOT EXISTS `inv` (
  `date_modified` datetime NOT NULL,
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `group_id` int NOT NULL,
  `user_id` int NOT NULL,
  `status_id` tinyint NOT NULL DEFAULT '1',
  `is_read_only` tinyint(1) DEFAULT NULL,
  `password` varchar(90) DEFAULT NULL,
  `date_due` datetime NOT NULL,
  `number` varchar(100) DEFAULT NULL,
  `discount_amount` decimal(20,2) NOT NULL DEFAULT '0.00',
  `discount_percent` decimal(20,2) NOT NULL DEFAULT '0.00',
  `terms` longtext NOT NULL,
  `url_key` varchar(32) NOT NULL,
  `payment_method` int NOT NULL DEFAULT '0',
  `creditinvoice_parent_id` int DEFAULT NULL,
  `time_created` time NOT NULL DEFAULT '00:00:00',
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `inv_index_user_id_621f9ee300303` (`user_id`),
  KEY `inv_index_group_id_621f9ee300618` (`group_id`),
  KEY `inv_index_client_id_621f9ee3007fb` (`client_id`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inv_amount`
--

DROP TABLE IF EXISTS `inv_amount`;
CREATE TABLE IF NOT EXISTS `inv_amount` (
  `id` int NOT NULL AUTO_INCREMENT,
  `inv_id` int NOT NULL,
  `sign` enum('1','-1') NOT NULL DEFAULT '1',
  `item_subtotal` decimal(20,2) NOT NULL DEFAULT '0.00',
  `item_tax_total` decimal(20,2) NOT NULL DEFAULT '0.00',
  `tax_total` decimal(20,2) NOT NULL DEFAULT '0.00',
  `total` decimal(20,2) NOT NULL DEFAULT '0.00',
  `paid` decimal(20,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(20,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `inv_amount_index_inv_id_623d98b37f61a` (`inv_id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inv_custom`
--

DROP TABLE IF EXISTS `inv_custom`;
CREATE TABLE IF NOT EXISTS `inv_custom` (
  `id` int NOT NULL AUTO_INCREMENT,
  `custom_field_id` int NOT NULL,
  `value` text,
  `inv_id` int NOT NULL,
  `invoice_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_custom_index_inv_id_621be1080cb9c` (`inv_id`),
  KEY `inv_custom_index_custom_field_id_621be1080cd6d` (`custom_field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inv_item`
--

DROP TABLE IF EXISTS `inv_item`;
CREATE TABLE IF NOT EXISTS `inv_item` (
  `id` int NOT NULL AUTO_INCREMENT,
  `inv_id` int NOT NULL,
  `tax_rate_id` int NOT NULL DEFAULT '0',
  `product_id` int DEFAULT NULL,
  `date_added` date NOT NULL,
  `task_id` int DEFAULT NULL,
  `name` text,
  `description` longtext,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `price` decimal(20,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(20,2) NOT NULL DEFAULT '0.00',
  `order` int NOT NULL DEFAULT '0',
  `is_recurring` tinyint(1) DEFAULT NULL,
  `product_unit` varchar(50) DEFAULT NULL,
  `product_unit_id` int DEFAULT NULL,
  `date` date DEFAULT NULL,
  `unit_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_item_index_inv_id_621fa279e8b51` (`inv_id`),
  KEY `inv_item_index_tax_rate_id_621fa279e8d54` (`tax_rate_id`),
  KEY `inv_item_index_product_id_621fa279e8f46` (`product_id`),
  KEY `inv_item_index_task_id_62484c8497c7c` (`task_id`),
  KEY `inv_item_index_unit_id_625809d50e3da` (`unit_id`)
) ENGINE=InnoDB AUTO_INCREMENT=239 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inv_item_amount`
--

DROP TABLE IF EXISTS `inv_item_amount`;
CREATE TABLE IF NOT EXISTS `inv_item_amount` (
  `id` int NOT NULL AUTO_INCREMENT,
  `inv_item_id` int NOT NULL,
  `subtotal` decimal(20,2) NOT NULL DEFAULT '0.00',
  `tax_total` decimal(20,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(20,2) NOT NULL DEFAULT '0.00',
  `total` decimal(20,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `inv_item_amount_index_inv_item_id_621c12bcbeae7` (`inv_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=234 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inv_recurring`
--

DROP TABLE IF EXISTS `inv_recurring`;
CREATE TABLE IF NOT EXISTS `inv_recurring` (
  `id` int NOT NULL AUTO_INCREMENT,
  `inv_id` int NOT NULL,
  `start` date DEFAULT NULL,
  `end` date DEFAULT NULL,
  `frequency` varchar(191) NOT NULL,
  `next` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inv_id` (`inv_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inv_tax_rate`
--

DROP TABLE IF EXISTS `inv_tax_rate`;
CREATE TABLE IF NOT EXISTS `inv_tax_rate` (
  `id` int NOT NULL AUTO_INCREMENT,
  `inv_id` int NOT NULL,
  `tax_rate_id` int NOT NULL,
  `include_item_tax` int NOT NULL DEFAULT '0',
  `inv_tax_rate_amount` decimal(20,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `inv_tax_rate_index_inv_id_6240556977e24` (`inv_id`),
  KEY `inv_tax_rate_index_tax_rate_id_6240556977e97` (`tax_rate_id`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `item_lookup`
--

DROP TABLE IF EXISTS `item_lookup`;
CREATE TABLE IF NOT EXISTS `item_lookup` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` longtext NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `merchant`
--

DROP TABLE IF EXISTS `merchant`;
CREATE TABLE IF NOT EXISTS `merchant` (
  `id` int NOT NULL AUTO_INCREMENT,
  `inv_id` int NOT NULL,
  `successful` tinyint(1) DEFAULT '1',
  `date` date NOT NULL,
  `driver` varchar(35) NOT NULL,
  `response` varchar(151) NOT NULL,
  `reference` varchar(151) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `merchant_index_inv_id_61ef006c9e3ca` (`inv_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

DROP TABLE IF EXISTS `payment`;
CREATE TABLE IF NOT EXISTS `payment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payment_method_id` int NOT NULL DEFAULT '0',
  `date` date NOT NULL,
  `amount` decimal(20,2) DEFAULT NULL,
  `note` longtext NOT NULL,
  `inv_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_index_inv_id_61ef006c9e412` (`inv_id`),
  KEY `payment_index_payment_method_id_61ef006c9e45e` (`payment_method_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_custom`
--

DROP TABLE IF EXISTS `payment_custom`;
CREATE TABLE IF NOT EXISTS `payment_custom` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payment_id` int NOT NULL,
  `custom_field_id` int NOT NULL,
  `value` text,
  PRIMARY KEY (`id`),
  KEY `payment_custom_index_payment_id_61ef006c9e4b1` (`payment_id`),
  KEY `payment_custom_index_custom_field_id_61ef006c9e505` (`custom_field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_method`
--

DROP TABLE IF EXISTS `payment_method`;
CREATE TABLE IF NOT EXISTS `payment_method` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payment_method`
--

INSERT INTO `payment_method` (`id`, `name`) VALUES
(1, 'Cash'),
(2, 'Credit Card');

-- --------------------------------------------------------

--
-- Table structure for table `post`
--

DROP TABLE IF EXISTS `post`;
CREATE TABLE IF NOT EXISTS `post` (
  `id` int NOT NULL AUTO_INCREMENT,
  `slug` varchar(128) NOT NULL,
  `title` varchar(191) NOT NULL DEFAULT '',
  `public` tinyint(1) NOT NULL DEFAULT '0',
  `content` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL,
  `published_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `post_index_user_id_61eef7f753caf` (`user_id`),
  KEY `post_index_public_published_at_61eef7f7541b1` (`public`,`published_at`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `post`
--

INSERT INTO `post` (`id`, `slug`, `title`, `public`, `content`, `created_at`, `updated_at`, `published_at`, `deleted_at`, `user_id`) VALUES
(1, 'jf1E0cMrZmB0OHispCleciRzeUr6ICQmoVitFuUe0sHJKMeotyCpre89QT5oxJ9OaF00EqYkqaONVEXllg2IHJus75dL4y2VLvNkoxGWrkTh-PNMKyjhlNO0ElsSX-7U', 'Quia numquam non est iure dolor nulla voluptatem.', 1, 'Mouse was speaking, and this Alice would not allow without knowing how old it was, even before she had succeeded in getting its body tucked away, comfortably enough, under her arm, that it might happen any minute, \'and then,\' thought Alice, and she jumped up on tiptoe, and peeped over the list, feeling very curious sensation, which puzzled her very earnestly, \'Now, Dinah, tell me your history, you know,\' said the Mock Turtle would be worth the trouble of getting her hands on her toes when they met in the common way. So she sat still and said nothing. \'Perhaps it doesn\'t mind.\' The table was a general clapping of hands at this: it was an old woman--but then--always to have changed since her swim in the prisoner\'s handwriting?\' asked another of the sea.\' \'I couldn\'t afford to learn it.\' said the Dormouse, not choosing to notice this last word two or three pairs of tiny white kid gloves and a bright brass plate with the Duchess, the Duchess! Oh! won\'t she be savage if I\'ve been changed for Mabel! I\'ll try if I can say.\' This was not an encouraging tone. Alice looked round, eager to see you any more!\' And here poor Alice began in a deep voice, \'are done with a shiver. \'I beg your pardon!\' cried Alice hastily, afraid that it was the first position in which you usually see Shakespeare, in the air: it puzzled her a good deal on where you want to get an opportunity of saying to herself, \'I don\'t know much,\' said the Rabbit\'s voice along--\'Catch him, you by the prisoner to--to somebody.\' \'It must have been that,\' said the King. \'When did you begin?\' The Hatter opened his eyes very wide on hearing this; but all he SAID was, \'Why is a long time together.\' \'Which is just the case with my wife; And the executioner myself,\' said the Cat, \'if you only walk long enough.\' Alice felt a little nervous about it while the Mouse to tell him. \'A nice muddle their slates\'ll be in a sulky tone; \'Seven jogged my elbow.\' On which Seven looked up eagerly, half hoping that they must needs come wriggling down from the roof. There were doors all round the refreshments!\' But there seemed to be seen--everything seemed to be almost out of breath, and said to the waving of the jurors were writing down \'stupid things!\' on their slates, and then unrolled the parchment scroll, and read as follows:-- \'The Queen of Hearts, he stole those tarts, And took them quite away!\' \'Consider your verdict,\' the King added in an offended tone, \'was, that the best cat in the act of crawling away: besides all this, there was a long way. So they sat down, and was looking at the top of her favourite word \'moral,\' and the executioner went off like an honest man.\' There was a queer-shaped little creature, and held it out loud. \'Thinking again?\' the Duchess by this time.) \'You\'re nothing but the Hatter asked triumphantly. Alice did not notice this question, but hurriedly went on, \'What\'s.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', '2021-09-30 04:41:29', NULL, 4),
(2, 'TU3OV8e3OlZcg3ggYPjCgNS_SLD1LTwh9-a7_Aq4FdVCBKArWVK6eGzQVqZTuoVA0nyf1T91-3li49iJq7NVbzAE7d0Pp0D-bO7wakT1cOulXm_-n8vLEPrpppFQIh24', 'Consequatur explicabo atque eaque.', 1, 'Alice in a large crowd collected round it: there were ten of them, and the procession moved on, three of the jurymen. \'It isn\'t a bird,\' Alice remarked. \'Oh, you can\'t take more.\' \'You mean you can\'t think! And oh, my poor little thing sobbed again (or grunted, it was a treacle-well.\' \'There\'s no such thing!\' Alice was so much contradicted in her life before, and behind it was a real Turtle.\' These words were followed by a very small cake, on which the wretched Hatter trembled so, that Alice had got its head down, and felt quite unhappy at the other, saying, in a low voice. \'Not at all,\' said the Footman. \'That\'s the reason of that?\' \'In my youth,\' said the Hatter. \'Nor I,\' said the Hatter, who turned pale and fidgeted. \'Give your evidence,\' said the Duchess, \'chop off her head!\' about once in her pocket, and was just beginning to see that the hedgehog a blow with its arms and frowning at the flowers and those cool fountains, but she saw them, they set to work shaking him and punching him in the wood, \'is to grow here,\' said the Dormouse. \'Fourteenth of March, I think you\'d take a fancy to cats if you wouldn\'t mind,\' said Alice: \'--where\'s the Duchess?\' \'Hush! Hush!\' said the White Rabbit; \'in fact, there\'s nothing written on the bank, with her head! Off--\' \'Nonsense!\' said Alice, (she had kept a piece of rudeness was more and more puzzled, but she had forgotten the Duchess sang the second thing is to France-- Then turn not pale, beloved snail, but.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', '2020-05-27 05:33:57', NULL, 5),
(3, '7PnpuYXCKiTG8uLjXVzwJ0V9fvrDnW_MRewoqITAvvIvbker2ijFspXkThrembwmVLoK7LTNhJ0oG-lmrzUfO7POdqx5URAtrWdp7HXZ2ubA176h8R3s4qGA4BBjvUlR', 'Rerum sed ut aliquid. Vel aut consequatur eos.', 1, 'WAS a curious feeling!\' said Alice; \'living at the top of the tail, and ending with the grin, which remained some time without hearing anything more: at last it unfolded its arms, took the hookah into its nest. Alice crouched down among the trees, a little queer, won\'t you?\' \'Not a bit,\' said the Duchess, \'chop off her head!\' the Queen in front of them, with her head struck against the ceiling, and had no very clear notion how long ago anything had happened.) So she called softly after it, never once considering how in the act of crawling away: besides all this, there was room for this, and after a fashion, and this Alice thought to herself. Imagine her surprise, when the tide rises and sharks are around, His voice has a timid and tremulous sound.] \'That\'s different from what I used to it!\' pleaded poor Alice began to cry again, for she was shrinking rapidly; so she went back for a minute, nurse! But I\'ve got to the voice of the others looked round also, and all the children she knew, who might do something better with the Dormouse. \'Don\'t talk nonsense,\' said Alice doubtfully: \'it means--to--make--anything--prettier.\' \'Well, then,\' the Cat went on, spreading out the verses the White Rabbit, who was trembling down to her great delight it fitted! Alice opened the door and went on for some way of expressing yourself.\' The baby grunted again, and put it in the same solemn tone, \'For the Duchess. \'Everything\'s got a moral, if only you can find out the Fish-Footman was gone, and, by the way the people near the door and went in. The door led right into it. \'That\'s very curious.\' \'It\'s all her life. Indeed, she had finished, her sister was.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', '2020-06-03 16:27:09', NULL, 5),
(4, 'fTCtS59-p88lRJyc_gqwGLPoDhZXjk4EIIS__IvLJ6oLRhw2tCtDB-Z_4muZrwGTa4SFQ4NsAki89hHNWZJfYpOCcqx1YMR5uFGrwqLul2yUKwSvWeydCc7aqwQuoiMc', 'Et dolorem non doloremque asperiores quia sed placeat.', 0, 'White Rabbit returning, splendidly dressed, with a pair of white kid gloves and the poor little thing grunted in reply (it had left off sneezing by this time, and was looking for it, she found this a good many voices all talking at once, and ran the faster, while more and more faintly came, carried on the shingle--will you come to the Classics master, though. He was looking at everything that was linked into hers began to tremble. Alice looked very anxiously into its face to see if he were trying which word sounded best. Some of the miserable Mock Turtle. \'Certainly not!\' said Alice to herself, being rather proud of it: for she had caught the baby violently up and saying, \'Thank you, it\'s a French mouse, come over with diamonds, and walked two and two, as the Dormouse followed him: the March Hare moved into the garden door. Poor Alice! It was as much right,\' said the sage, as he could think of what sort it was) scratching and scrambling about in all directions, \'just like a sky-rocket!\' \'So you think you could see this, as she ran. \'How surprised he\'ll be when he finds out who was trembling down to the puppy; whereupon the puppy jumped into the sky. Twinkle, twinkle--\"\' Here the Queen jumped up in her haste, she had wept when she had someone to listen to me! When I used to it!\' pleaded poor Alice. \'But you\'re so easily offended!\' \'You\'ll get used to read fairy-tales, I fancied that kind of authority over Alice. \'Stand up and saying, \'Thank you, it\'s a very little! Besides, SHE\'S she, and I\'m sure I can\'t see you?\' She was a general clapping of hands at this: it was all about, and crept a little scream, half of anger, and tried to curtsey as she spoke, but no result seemed to quiver all over crumbs.\' \'You\'re wrong about the temper of your flamingo. Shall I try the effect: the next moment a shower of saucepans, plates, and dishes. The Duchess took no notice of them at dinn--\' she checked herself hastily, and said to herself \'Suppose it should be free of them hit her in an offended tone, \'Hm! No accounting for tastes! Sing her \"Turtle Soup,\" will you, won\'t you join the dance?\"\' \'Thank you, sir, for your interesting story,\' but she heard one of the month, and doesn\'t tell what o\'clock it is!\' \'Why should it?\' muttered the Hatter. \'You MUST remember,\' remarked the King, the Queen, who was a long sleep you\'ve had!\' \'Oh, I\'ve had such a capital one for catching mice--oh, I beg your pardon!\' cried Alice hastily, afraid that it would be four thousand miles down, I think--\' (for, you see, Miss, this here ought to have no answers.\' \'If you didn\'t sign it,\' said Alice. \'Of course it is,\' said the Mock Turtle. \'Seals, turtles, salmon, and so on.\' \'What a number of changes she had forgotten the words.\' So they had to do this, so that altogether, for the White Rabbit, \'and that\'s why. Pig!\' She said the King. The White Rabbit as he came, \'Oh! the Duchess, \'and that\'s why. Pig!\' She said the Caterpillar. This was quite impossible to say anything. \'Why,\' said the King. The next witness would be quite as safe to stay in here any longer!\' She waited for some way, and then another confusion of voices--\'Hold up his head--Brandy now--Don\'t choke him--How was it, old fellow? What happened to me! I\'LL soon make you a couple?\' \'You are old, Father William,\' the young Crab, a little feeble, squeaking voice, (\'That\'s Bill,\' thought Alice,) \'Well, I should understand that better,\' Alice said nothing; she had somehow fallen into it: there were TWO little shrieks, and more puzzled, but she ran with all their simple sorrows, and find a number of executions the Queen put on his knee, and the moment how large she had found her head was so ordered about by mice and rabbits. I almost think I could, if I might venture to say it over) \'--yes, that\'s about the crumbs,\'.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', NULL, NULL, 5),
(5, 'e4PHBTFRZ_Gz5UtQOgYHz8DCdXiJ6dUnLhWjgW2W9lJVwcnCTe1PzJuraLQlA04cR2QNys1cB_CYW9bsbzsWAchi8FfijxDiTox6xS8mkQZz1fpqZaG6h1u7suKuGsyV', 'Mollitia aliquid porro voluptas hic saepe omnis dolore.', 1, 'I think you\'d better finish the story for yourself.\' \'No, please go on!\' Alice said with some severity; \'it\'s very rude.\' The Hatter opened his eyes very wide on hearing this; but all he SAID was, \'Why is a raven like a Jack-in-the-box, and up the fan and gloves. \'How queer it seems,\' Alice said very humbly; \'I won\'t have any rules in particular; at least, if there are, nobody attends to them--and you\'ve no idea how confusing it is you hate--C and D,\' she added aloud. \'Do you play croquet with the lobsters, out to sea as you liked.\' \'Is that the way out of sight. Alice remained looking thoughtfully at the window, and on it (as she had never seen such a curious appearance in the house, and wondering what to do, and in another moment, splash! she was up to the porpoise, \"Keep back, please: we don\'t want to see what was the Hatter. Alice felt so desperate that she had looked under it, and yet it was all dark overhead; before her was another puzzling question; and as the soldiers did. After these came the guests, mostly Kings and Queens, and among them Alice recognised the White Rabbit cried out, \'Silence in the book,\' said the Mock Turtle, who looked at the mouth with strings: into this they slipped the guinea-pig, head first, and then, and holding it to half-past one as long as I do,\' said the Pigeon; \'but I know is, something comes at me like a frog; and both the hedgehogs were out of it, and on it but tea. \'I don\'t even know what to do, so Alice went on, very much what would happen next. First, she dreamed of little cartwheels, and the Queen till she fancied she heard it before,\' said Alice,) and round goes the clock in a very deep well. Either the well was very provoking to find her in a deep, hollow tone: \'sit down, both of you, and listen to her. \'I can see you\'re trying to put his shoes on. \'--and just take his head sadly. \'Do I look like one, but the Dormouse turned out, and, by the fire, licking her paws and washing her face--and she is of yours.\"\' \'Oh, I BEG your pardon!\' cried Alice in a low curtain she had not the smallest notice of her hedgehog. The hedgehog was engaged in a frightened tone. \'The Queen will hear you! You see, she came upon a heap of sticks and dry leaves, and the three gardeners, but she knew she had drunk half the bottle, she found this a very short time the Queen was close behind us, and he\'s treading on her lap.', '2022-01-24 19:36:42', '2022-01-24 19:36:42', '2020-11-03 09:59:08', NULL, 5),
(6, '7lvOy-Xs8IdOUUV967D27B3ypYr1SGTJcA7eru63w83lhj-0ciik31b_ZV-cy9swXb7L7obaJiWA74zmAMS4TJQmjxg-AFd_nW-bAvCntdcNT6AenRcBLW7E3noOfXmJ', 'sdfgsdfg', 1, 'sfgsdfgs', '2022-01-24 22:39:00', '2022-01-24 22:39:00', NULL, NULL, 1),
(7, 'FlLNUSc_QbKCL02qJzCPEeT_l3uPMDxbJddigXcdDJYTQfaPx9arDWpdogdeTwb6wc3NlNGyVJSuKhl9jBoQ1B5yyJ-HUnClhckh_eOZPQPatuNwfkT2rbvxVHy6MOtL', 'asdfasdfffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff', 1, 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff', '2022-01-24 23:54:41', '2022-01-24 23:54:41', NULL, NULL, 1),
(8, 'kN2BULXDmO79npt_IFriVJ5jWq5_-wrE_4ySlx8BlsjHUNXydAqdOtbfG4XXMq8d0qb9xocodwBfmwlA4U_PyKO1EfoxBRRYD26cAGesQuF2UfpxtsAP8OtS8ckJ0nyA', 'asdfas', 1, 'asdfasasdfasdfasdfa', '2022-01-26 17:58:30', '2022-04-26 08:46:55', '2022-01-26 18:02:54', NULL, 1),
(9, '16_QKTv0LbvA6t1jpmB0Kx0zpAmpV-mH1Wx_HH5_8mIr4u3zSosUOfiodAI01pn1mByEiY_TyiI3yG_hzQYvAVeR0b7k12ysyVhrVcm4z_JoeDCI1xuchhWUKK_SAvM1', 'asdfasdfasdf', 1, 'asdfasdfasdfad', '2022-04-26 08:46:31', '2022-04-26 08:46:31', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `post_tag`
--

DROP TABLE IF EXISTS `post_tag`;
CREATE TABLE IF NOT EXISTS `post_tag` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `tag_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_tag_index_post_id_tag_id_61eef7f753d23` (`post_id`,`tag_id`),
  KEY `post_tag_index_post_id_61eef7f753d3b` (`post_id`),
  KEY `post_tag_index_tag_id_61eef7f753d55` (`tag_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `post_tag`
--

INSERT INTO `post_tag` (`id`, `post_id`, `tag_id`) VALUES
(2, 1, 2),
(3, 1, 3),
(1, 2, 1),
(12, 3, 3),
(4, 4, 3),
(5, 4, 4),
(6, 4, 5),
(11, 5, 1),
(7, 5, 2),
(8, 5, 3),
(9, 5, 4),
(10, 5, 5),
(21, 9, 14);

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

DROP TABLE IF EXISTS `product`;
CREATE TABLE IF NOT EXISTS `product` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_sku` text,
  `product_name` text,
  `product_description` longtext NOT NULL,
  `product_price` decimal(20,2) DEFAULT NULL,
  `purchase_price` decimal(20,2) DEFAULT NULL,
  `provider_name` text,
  `family_id` int DEFAULT NULL,
  `tax_rate_id` int DEFAULT NULL,
  `unit_id` int DEFAULT NULL,
  `product_tariff` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_index_family_id_621512e3108cf` (`family_id`),
  KEY `product_index_tax_rate_id_621512e31095d` (`tax_rate_id`),
  KEY `product_index_unit_id_621512e3109bf` (`unit_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `profile`
--

DROP TABLE IF EXISTS `profile`;
CREATE TABLE IF NOT EXISTS `profile` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `current` tinyint NOT NULL DEFAULT '0',
  `mobile` text,
  `email` text,
  `description` text,
  `date_created` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `profile_index_company_id_61ef006c9e64a` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project`
--

DROP TABLE IF EXISTS `project`;
CREATE TABLE IF NOT EXISTS `project` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `name` text,
  PRIMARY KEY (`id`),
  KEY `project_index_client_id_61ef006c9e6a0` (`client_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quote`
--

DROP TABLE IF EXISTS `quote`;
CREATE TABLE IF NOT EXISTS `quote` (
  `id` int NOT NULL AUTO_INCREMENT,
  `inv_id` int DEFAULT '0',
  `user_id` int NOT NULL,
  `client_id` int NOT NULL,
  `group_id` int NOT NULL,
  `status_id` tinyint NOT NULL DEFAULT '1',
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` datetime NOT NULL,
  `date_expires` datetime NOT NULL,
  `number` varchar(100) DEFAULT NULL,
  `discount_amount` decimal(20,2) NOT NULL DEFAULT '0.00',
  `discount_percent` decimal(20,2) NOT NULL DEFAULT '0.00',
  `url_key` varchar(32) NOT NULL,
  `password` varchar(90) DEFAULT NULL,
  `notes` longtext,
  PRIMARY KEY (`id`),
  KEY `quote_index_client_id_61f183fd96170` (`client_id`),
  KEY `quote_index_group_id_61f183fd961c8` (`group_id`),
  KEY `quote_index_user_id_61f183fd96219` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quote_amount`
--

DROP TABLE IF EXISTS `quote_amount`;
CREATE TABLE IF NOT EXISTS `quote_amount` (
  `id` int NOT NULL AUTO_INCREMENT,
  `quote_id` int NOT NULL,
  `item_subtotal` decimal(20,2) NOT NULL DEFAULT '0.00',
  `item_tax_total` decimal(20,2) NOT NULL DEFAULT '0.00',
  `tax_total` decimal(20,2) NOT NULL DEFAULT '0.00',
  `total` decimal(20,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `quote_amount_index_quote_id_62127193951cd` (`quote_id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quote_custom`
--

DROP TABLE IF EXISTS `quote_custom`;
CREATE TABLE IF NOT EXISTS `quote_custom` (
  `id` int NOT NULL AUTO_INCREMENT,
  `quote_id` int NOT NULL,
  `custom_field_id` int NOT NULL,
  `value` text,
  PRIMARY KEY (`id`),
  KEY `quote_custom_index_custom_field_id_61f183fd962c1` (`custom_field_id`),
  KEY `quote_custom_index_quote_id_61f183fd9631c` (`quote_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quote_item`
--

DROP TABLE IF EXISTS `quote_item`;
CREATE TABLE IF NOT EXISTS `quote_item` (
  `id` int NOT NULL AUTO_INCREMENT,
  `quote_id` int NOT NULL,
  `tax_rate_id` int NOT NULL,
  `product_id` int NOT NULL,
  `date_added` date NOT NULL,
  `name` text,
  `description` text,
  `quantity` decimal(20,2) NOT NULL DEFAULT '1.00',
  `price` decimal(20,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(20,2) NOT NULL DEFAULT '0.00',
  `order` int NOT NULL DEFAULT '0',
  `product_unit` varchar(50) DEFAULT NULL,
  `product_unit_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `quote_item_index_tax_rate_id_62127193952cb` (`tax_rate_id`),
  KEY `quote_item_index_product_id_6212719395325` (`product_id`),
  KEY `quote_item_index_quote_id_6212719395382` (`quote_id`)
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quote_item_amount`
--

DROP TABLE IF EXISTS `quote_item_amount`;
CREATE TABLE IF NOT EXISTS `quote_item_amount` (
  `id` int NOT NULL AUTO_INCREMENT,
  `quote_item_id` int NOT NULL,
  `subtotal` decimal(20,2) NOT NULL DEFAULT '0.00',
  `tax_total` decimal(20,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(20,2) NOT NULL DEFAULT '0.00',
  `total` decimal(20,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `quote_item_amount_index_quote_item_id_62127193953e0` (`quote_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quote_tax_rate`
--

DROP TABLE IF EXISTS `quote_tax_rate`;
CREATE TABLE IF NOT EXISTS `quote_tax_rate` (
  `id` int NOT NULL AUTO_INCREMENT,
  `quote_id` int NOT NULL,
  `tax_rate_id` int NOT NULL,
  `include_item_tax` int NOT NULL DEFAULT '0',
  `quote_tax_rate_amount` decimal(20,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `quote_tax_rate_index_quote_id_6212719395439` (`quote_id`),
  KEY `quote_tax_rate_index_tax_rate_id_6212719395496` (`tax_rate_id`)
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `setting`
--

DROP TABLE IF EXISTS `setting`;
CREATE TABLE IF NOT EXISTS `setting` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` longtext NOT NULL,
  `setting_trans` varchar(30) NOT NULL,
  `setting_section` varchar(30) NOT NULL,
  `setting_subsection` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=137 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `setting`
--

INSERT INTO `setting` (`id`, `setting_key`, `setting_value`, `setting_trans`, `setting_section`, `setting_subsection`) VALUES
(1, 'mark_invoices_sent_pdf', '0', '', '', ''),
(2, 'default_language', 'English', '', '', ''),
(3, 'currency_symbol', '£', '', '', ''),
(4, 'currency_symbol_placement', 'before', '', '', ''),
(5, 'currency_code', 'GBP', '', '', ''),
(6, 'disable_sidebar', '0', '', '', ''),
(7, 'monospace_amounts', '0', '', '', ''),
(8, 'cron_key', '7XAgA35N-E2-oWb1WdQtYJetC4ZOtpdC', '', '', ''),
(9, 'default_invoice_terms', 'These are the default terms they are.', '', '', ''),
(10, 'read_only_toggle', '2', '', '', ''),
(11, 'pdf_watermark', '0', '', '', ''),
(12, 'pdf_invoice_template', '', '', '', ''),
(13, 'pdf_invoice_template_paid', '', '', '', ''),
(14, 'pdf_invoice_template_overdue', '', '', '', ''),
(15, 'pdf_invoice_footer', 'This is a footer', '', '', ''),
(16, 'smtp_authentication', '0', '', '', ''),
(17, 'smtp_verify_certs', '1', '', '', ''),
(19, 'gateway_braintree_enabled', '1', '', '', ''),
(20, 'gateway_braintree_testMode', '1', '', '', ''),
(21, 'gateway_braintree_currency', 'GBP', '', '', ''),
(22, 'gateway_converge_enabled', '0', '', '', ''),
(23, 'gateway_converge_testMode', '0', '', '', ''),
(24, 'gateway_converge_currency', 'AED', '', '', ''),
(25, 'gateway_cybersource_enabled', '0', '', '', ''),
(26, 'gateway_cybersource_testMode', '0', '', '', ''),
(27, 'gateway_cybersource_currency', 'AED', '', '', ''),
(28, 'gateway_stripe_enabled', '0', '', '', ''),
(29, 'gateway_stripe_currency', 'GBP', '', '', ''),
(30, 'gateway_worldpay_enabled', '0', '', '', ''),
(31, 'gateway_worldpay_testMode', '0', '', '', ''),
(32, 'gateway_worldpay_currency', 'AED', '', '', ''),
(33, 'invoice_logo', '', '', '', ''),
(34, 'invoices_due_after', '40', '', '', ''),
(35, 'gateway_braintree_payment_method', '4', '', '', ''),
(36, 'gateway_stripe_payment_method', '2', '', '', ''),
(37, 'invoice_default_payment_method', '2', '', '', ''),
(38, 'email_invoice_template', '', '', '', ''),
(39, 'email_invoice_template_paid', '', '', '', ''),
(40, 'email_invoice_template_overdue', '', '', '', ''),
(41, 'date_format', 'd-m-Y', '', '', ''),
(47, 'thousands_separator', ',', '', '', ''),
(48, 'decimal_point', '.', '', '', ''),
(51, 'disable_read_only', '1', '', '', ''),
(52, 'default_item_tax_rate', '', '', '', ''),
(53, 'enable_permissive_search_clients', '0', '', '', ''),
(54, 'generate_quote_number_for_draft', '0', '', '', ''),
(55, 'quotes_expire_after', '30', '', '', ''),
(56, 'quote_pre_password', 'mypassword', '', '', ''),
(57, 'default_list_limit', '4', '', '', ''),
(58, 'nl', 'en', '', '', ''),
(59, 'cldr', 'ja-JP', '', '', ''),
(60, 'default_quote_group', '4', '', '', ''),
(61, 'generate_invoice_number_for_draft', '0', '', '', ''),
(62, 'default_invoice_group', '3', '', '', ''),
(64, 'default_country', 'AM', '', '', ''),
(65, 'first_day_of_week', '0', '', '', ''),
(66, 'tax_rate_decimal_places', '2', '', '', ''),
(67, 'number_format', 'number_format_us_uk', '', '', ''),
(68, 'quote_overview_period', 'this-month', '', '', ''),
(69, 'invoice_overview_period', 'this-month', '', '', ''),
(70, 'disable_quickactions', '1', '', '', ''),
(71, 'reports_in_new_tab', '0', '', '', ''),
(72, 'bcc_mails_to_admin', '0', '', '', ''),
(73, 'include_zugferd', '1', '', '', ''),
(74, 'automatic_email_on_recur', '0', '', '', ''),
(75, 'sumex', '0', '', '', ''),
(76, 'sumex_sliptype', '0', '', '', ''),
(77, 'sumex_role', '0', '', '', ''),
(78, 'sumex_place', '0', '', '', ''),
(79, 'sumex_canton', '0', '', '', ''),
(80, 'mark_quotes_sent_pdf', '0', '', '', ''),
(81, 'email_pdf_attachment', '1', '', '', ''),
(82, 'smtp_password_field_is_password', '1', '', '', ''),
(83, 'enable_online_payments', '0', '', '', ''),
(84, 'gateway_authorizenet_aim_enabled', '0', '', '', ''),
(85, 'gateway_authorizenet_aim_testMode', '0', '', '', ''),
(86, 'gateway_authorizenet_aim_developerMode', '0', '', '', ''),
(87, 'gateway_authorizenet_aim_currency', 'ARS', '', '', ''),
(88, 'gateway_authorizenet_sim_enabled', '0', '', '', ''),
(89, 'gateway_authorizenet_sim_testMode', '0', '', '', ''),
(90, 'gateway_authorizenet_sim_developerMode', '0', '', '', ''),
(91, 'gateway_authorizenet_sim_currency', 'ARS', '', '', ''),
(92, 'gateway_paypal_express_enabled', '0', '', '', ''),
(93, 'gateway_paypal_express_password_field_is_password', '1', '', '', ''),
(94, 'gateway_paypal_express_signature_field_is_password', '1', '', '', ''),
(95, 'gateway_paypal_express_testMode', '0', '', '', ''),
(96, 'gateway_paypal_express_currency', 'ARS', '', '', ''),
(97, 'gateway_paypal_pro_enabled', '0', '', '', ''),
(98, 'gateway_paypal_pro_password_field_is_password', '1', '', '', ''),
(99, 'gateway_paypal_pro_testMode', '0', '', '', ''),
(100, 'gateway_paypal_pro_currency', 'ARS', '', '', ''),
(101, 'gateway_stripe_apiKey_field_is_password', '1', '', '', ''),
(102, 'projects_enabled', '0', '', '', ''),
(103, 'default_hourly_rate_field_is_amount', '1', '', '', ''),
(104, 'custom_title', '', '', '', ''),
(105, 'invoice_pre_password', '', '', '', ''),
(106, 'public_invoice_template', '', '', '', ''),
(107, 'default_quote_notes', '', '', '', ''),
(108, 'pdf_quote_template', '', '', '', ''),
(109, 'public_quote_template', '', '', '', ''),
(110, 'email_quote_template', '', '', '', ''),
(111, 'pdf_quote_footer', '', '', '', ''),
(112, 'default_invoice_tax_rate', '', '', '', ''),
(113, 'default_include_item_tax', '', '', '', ''),
(114, 'email_send_method', 'sendmail', '', '', ''),
(115, 'smtp_server_address', '', '', '', ''),
(116, 'smtp_mail_from', '', '', '', ''),
(117, 'smtp_username', '', '', '', ''),
(118, 'smtp_password', '', '', '', ''),
(119, 'smtp_port', '', '', '', ''),
(120, 'smtp_security', '', '', '', ''),
(121, 'gateway_authorizenet_aim_apiLoginId', '', '', '', ''),
(122, 'gateway_authorizenet_aim_transactionKey', '', '', '', ''),
(123, 'gateway_authorizenet_aim_payment_method', '', '', '', ''),
(124, 'gateway_authorizenet_sim_apiLoginId', '', '', '', ''),
(125, 'gateway_authorizenet_sim_transactionKey', '', '', '', ''),
(126, 'gateway_authorizenet_sim_payment_method', '', '', '', ''),
(127, 'gateway_paypal_express_username', '', '', '', ''),
(128, 'gateway_paypal_express_password', '', '', '', ''),
(129, 'gateway_paypal_express_signature', '', '', '', ''),
(130, 'gateway_paypal_express_payment_method', '', '', '', ''),
(131, 'gateway_paypal_pro_username', '', '', '', ''),
(132, 'gateway_paypal_pro_password', '', '', '', ''),
(133, 'gateway_paypal_pro_signature', '', '', '', ''),
(134, 'gateway_paypal_pro_payment_method', '', '', '', ''),
(135, 'gateway_stripe_apiKey', '', '', '', ''),
(136, 'default_hourly_rate', '', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `sumex`
--

DROP TABLE IF EXISTS `sumex`;
CREATE TABLE IF NOT EXISTS `sumex` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice` int NOT NULL,
  `reason` int NOT NULL,
  `diagnosis` varchar(500) NOT NULL,
  `observations` varchar(500) NOT NULL,
  `treatmentstart` date NOT NULL,
  `treatmentend` date NOT NULL,
  `casedate` date NOT NULL,
  `casenumber` varchar(35) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tag`
--

DROP TABLE IF EXISTS `tag`;
CREATE TABLE IF NOT EXISTS `tag` (
  `id` int NOT NULL AUTO_INCREMENT,
  `label` varchar(191) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag_index_label_61eef7f754303` (`label`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tag`
--

INSERT INTO `tag` (`id`, `label`, `created_at`) VALUES
(1, 'porro', '2022-01-24 19:36:42'),
(2, 'et', '2022-01-24 19:36:42'),
(3, 'eligendi', '2022-01-24 19:36:42'),
(4, 'occaecati', '2022-01-24 19:36:42'),
(5, 'eum', '2022-01-24 19:36:42'),
(14, 'asdfasdfasdf', '2022-04-26 08:46:31');

-- --------------------------------------------------------

--
-- Table structure for table `task`
--

DROP TABLE IF EXISTS `task`;
CREATE TABLE IF NOT EXISTS `task` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `name` text,
  `description` longtext NOT NULL,
  `price` decimal(20,2) DEFAULT NULL,
  `finish_date` date NOT NULL,
  `status` tinyint(1) NOT NULL,
  `tax_rate_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `task_index_project_id_621fa279ec3d0` (`project_id`),
  KEY `task_index_tax_rate_id_621fa279ec5e5` (`tax_rate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tax_rate`
--

DROP TABLE IF EXISTS `tax_rate`;
CREATE TABLE IF NOT EXISTS `tax_rate` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tax_rate_name` text,
  `tax_rate_percent` decimal(5,2) NOT NULL,
  `tax_rate_default` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tax_rate`
--

INSERT INTO `tax_rate` (`id`, `tax_rate_name`, `tax_rate_percent`, `tax_rate_default`) VALUES
(1, 'Standard', '20.00', 1),
(2, 'Zero', '0.00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `unit`
--

DROP TABLE IF EXISTS `unit`;
CREATE TABLE IF NOT EXISTS `unit` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unit_name` varchar(50) NOT NULL,
  `unit_name_plrl` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `upload`
--

DROP TABLE IF EXISTS `upload`;
CREATE TABLE IF NOT EXISTS `upload` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `url_key` varchar(32) NOT NULL,
  `file_name_original` longtext NOT NULL,
  `file_name_new` longtext NOT NULL,
  `uploaded_date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `upload_index_client_id_61ef006c9ebbf` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(48) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_index_login_61eef7f7543f4` (`login`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `login`, `password_hash`, `created_at`, `updated_at`) VALUES
(1, 'james', '$2y$13$67EyasMMrWWBKdGNIOboaOnhyskMjF4OktNenULmZiYoGyr8UwOWq', '2022-01-24 19:06:29', '2022-01-24 19:06:29'),
(2, 'Estefania685', '$2y$13$nolC.wq5xilD.kxfbc5B7et/0NyEooiv73ccnp8ciophp7FIULTwG', '2022-01-24 19:36:39', '2022-01-24 19:36:39'),
(3, 'Donato9194', '$2y$13$oAw5hEZ7m8hTK6cKJgzxF.4oGr08oH2rNz/TWYyVL3.ER4y.RP8MS', '2022-01-24 19:36:40', '2022-01-24 19:36:40'),
(4, 'Jarvis1823', '$2y$13$PpHbJMqhvB2S/Bg7Ju.7AeNoOEkfthDf.e/x8/Jn2FfckkcqtlmXa', '2022-01-24 19:36:40', '2022-01-24 19:36:40'),
(5, 'Erich1237', '$2y$13$BiO9Fca6TfeH0AiU.M.CpeIzPFc7N7G83Wjdou9yozJW.8UIsTwbq', '2022-01-24 19:36:41', '2022-01-24 19:36:41'),
(6, 'Elenor8084', '$2y$13$GRDv1i1nQG0lPT3Nn3TBD.WMKlK6mjuWFkA4RKPY2NFHaQuefQcB2', '2022-01-24 19:36:41', '2022-01-24 19:36:41');

-- --------------------------------------------------------

--
-- Table structure for table `user_client`
--

DROP TABLE IF EXISTS `user_client`;
CREATE TABLE IF NOT EXISTS `user_client` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `client_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_client_index_user_id_61ef006c9ec1e` (`user_id`),
  KEY `user_client_index_client_id_61ef006c9ec7e` (`client_id`)
) ENGINE=InnoDB AUTO_INCREMENT=216 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_custom`
--

DROP TABLE IF EXISTS `user_custom`;
CREATE TABLE IF NOT EXISTS `user_custom` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `fieldid` int NOT NULL,
  `fieldvalue` text,
  PRIMARY KEY (`id`),
  KEY `user_custom_index_user_id_61ef006c9ecde` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_inv`
--

DROP TABLE IF EXISTS `user_inv`;
CREATE TABLE IF NOT EXISTS `user_inv` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `type` int NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  `language` varchar(191) DEFAULT 'system',
  `name` text,
  `company` text,
  `address_1` text,
  `address_2` text,
  `city` text,
  `state` text,
  `zip` text,
  `country` text,
  `phone` text,
  `fax` text,
  `mobile` text,
  `email` text,
  `password` varchar(60) NOT NULL,
  `web` text,
  `vat_id` text,
  `tax_code` text,
  `all_clients` tinyint(1) NOT NULL DEFAULT '0',
  `salt` varchar(100) DEFAULT NULL,
  `passwordreset_token` varchar(100) DEFAULT NULL,
  `subscribernumber` varchar(40) DEFAULT NULL,
  `iban` varchar(34) DEFAULT NULL,
  `gln` bigint DEFAULT NULL,
  `rcc` varchar(7) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_inv_index_user_id_6257223f923d1` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `client_custom`
--
ALTER TABLE `client_custom`
  ADD CONSTRAINT `client_custom_foreign_client_id_61ef006c9ddfc` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `client_custom_foreign_custom_field_id_61ef006c9de53` FOREIGN KEY (`custom_field_id`) REFERENCES `custom_field` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `client_note`
--
ALTER TABLE `client_note`
  ADD CONSTRAINT `client_note_foreign_client_id_61ef006c9dea6` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`);

--
-- Constraints for table `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `comment_foreign_post_id_61eef7f753c36` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `comment_foreign_user_id_61eef7f753be4` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `company_private`
--
ALTER TABLE `company_private`
  ADD CONSTRAINT `company_private_foreign_company_id_61ef006c9deec` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `custom_value`
--
ALTER TABLE `custom_value`
  ADD CONSTRAINT `custom_value_foreign_custom_field_id_61ef006c9df3f` FOREIGN KEY (`custom_field_id`) REFERENCES `custom_field` (`id`);

--
-- Constraints for table `gentor_relation`
--
ALTER TABLE `gentor_relation`
  ADD CONSTRAINT `gentor_relation_foreign_gentor_id_61ef006c9df88` FOREIGN KEY (`gentor_id`) REFERENCES `gentor` (`id`);

--
-- Constraints for table `identity`
--
ALTER TABLE `identity`
  ADD CONSTRAINT `identity_foreign_user_id_61eef7f7538f7` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `inv`
--
ALTER TABLE `inv`
  ADD CONSTRAINT `inv_foreign_client_id_6261d01b9897a` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`),
  ADD CONSTRAINT `inv_foreign_group_id_6261d01b988eb` FOREIGN KEY (`group_id`) REFERENCES `group` (`id`),
  ADD CONSTRAINT `inv_foreign_user_id_6261d01b987af` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `inv_amount`
--
ALTER TABLE `inv_amount`
  ADD CONSTRAINT `inv_amount_foreign_inv_id_623d98b37f634` FOREIGN KEY (`inv_id`) REFERENCES `inv` (`id`);

--
-- Constraints for table `inv_custom`
--
ALTER TABLE `inv_custom`
  ADD CONSTRAINT `inv_custom_foreign_custom_field_id_621be1080cdb2` FOREIGN KEY (`custom_field_id`) REFERENCES `custom_field` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `inv_custom_foreign_inv_id_621be1080cbe1` FOREIGN KEY (`inv_id`) REFERENCES `inv` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `inv_item`
--
ALTER TABLE `inv_item`
  ADD CONSTRAINT `inv_item_foreign_inv_id_621fa279e8b9e` FOREIGN KEY (`inv_id`) REFERENCES `inv` (`id`),
  ADD CONSTRAINT `inv_item_foreign_product_id_621fa279e8f8c` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`),
  ADD CONSTRAINT `inv_item_foreign_tax_rate_id_621fa279e8d99` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rate` (`id`),
  ADD CONSTRAINT `inv_item_foreign_unit_id_625809d50e3fd` FOREIGN KEY (`unit_id`) REFERENCES `unit` (`id`);

--
-- Constraints for table `inv_item_amount`
--
ALTER TABLE `inv_item_amount`
  ADD CONSTRAINT `inv_item_amount_foreign_inv_item_id_621c12bcbeb33` FOREIGN KEY (`inv_item_id`) REFERENCES `inv_item` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `inv_recurring`
--
ALTER TABLE `inv_recurring`
  ADD CONSTRAINT `inv_recurring_foreign_inv_id_624c32389b749` FOREIGN KEY (`inv_id`) REFERENCES `inv` (`id`);

--
-- Constraints for table `inv_tax_rate`
--
ALTER TABLE `inv_tax_rate`
  ADD CONSTRAINT `inv_tax_rate_foreign_inv_id_6240556977e3d` FOREIGN KEY (`inv_id`) REFERENCES `inv` (`id`),
  ADD CONSTRAINT `inv_tax_rate_foreign_tax_rate_id_6240556977ea9` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rate` (`id`);

--
-- Constraints for table `merchant`
--
ALTER TABLE `merchant`
  ADD CONSTRAINT `merchant_foreign_inv_id_61ef006c9e3d6` FOREIGN KEY (`inv_id`) REFERENCES `inv` (`id`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_foreign_inv_id_61ef006c9e41f` FOREIGN KEY (`inv_id`) REFERENCES `inv` (`id`),
  ADD CONSTRAINT `payment_foreign_payment_method_id_61ef006c9e46a` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_method` (`id`);

--
-- Constraints for table `payment_custom`
--
ALTER TABLE `payment_custom`
  ADD CONSTRAINT `payment_custom_foreign_custom_field_id_61ef006c9e512` FOREIGN KEY (`custom_field_id`) REFERENCES `custom_field` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `payment_custom_foreign_payment_id_61ef006c9e4bd` FOREIGN KEY (`payment_id`) REFERENCES `payment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `post_foreign_user_id_61eef7f753cbc` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `post_tag`
--
ALTER TABLE `post_tag`
  ADD CONSTRAINT `post_tag_foreign_post_id_61eef7f753d32` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `post_tag_foreign_tag_id_61eef7f753d4c` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_foreign_family_id_626a98f3a332a` FOREIGN KEY (`family_id`) REFERENCES `family` (`id`),
  ADD CONSTRAINT `product_foreign_tax_rate_id_626a98f3a3399` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rate` (`id`),
  ADD CONSTRAINT `product_foreign_unit_id_626a98f3a3404` FOREIGN KEY (`unit_id`) REFERENCES `unit` (`id`);

--
-- Constraints for table `profile`
--
ALTER TABLE `profile`
  ADD CONSTRAINT `profile_foreign_company_id_61ef006c9e656` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `project`
--
ALTER TABLE `project`
  ADD CONSTRAINT `project_foreign_client_id_61ef006c9e6ac` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`);

--
-- Constraints for table `quote`
--
ALTER TABLE `quote`
  ADD CONSTRAINT `quote_foreign_client_id_61f183fd96186` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`),
  ADD CONSTRAINT `quote_foreign_group_id_61f183fd961d5` FOREIGN KEY (`group_id`) REFERENCES `group` (`id`),
  ADD CONSTRAINT `quote_foreign_user_id_61f183fd96226` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quote_amount`
--
ALTER TABLE `quote_amount`
  ADD CONSTRAINT `quote_amount_foreign_quote_id_62127193951e4` FOREIGN KEY (`quote_id`) REFERENCES `quote` (`id`);

--
-- Constraints for table `quote_custom`
--
ALTER TABLE `quote_custom`
  ADD CONSTRAINT `quote_custom_foreign_custom_field_id_61f183fd962cd` FOREIGN KEY (`custom_field_id`) REFERENCES `custom_field` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `quote_custom_foreign_quote_id_61f183fd96329` FOREIGN KEY (`quote_id`) REFERENCES `quote` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quote_item`
--
ALTER TABLE `quote_item`
  ADD CONSTRAINT `quote_item_foreign_product_id_6212719395334` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`),
  ADD CONSTRAINT `quote_item_foreign_quote_id_6212719395391` FOREIGN KEY (`quote_id`) REFERENCES `quote` (`id`),
  ADD CONSTRAINT `quote_item_foreign_tax_rate_id_62127193952da` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rate` (`id`);

--
-- Constraints for table `quote_item_amount`
--
ALTER TABLE `quote_item_amount`
  ADD CONSTRAINT `quote_item_amount_foreign_quote_item_id_62127193953ef` FOREIGN KEY (`quote_item_id`) REFERENCES `quote_item` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quote_tax_rate`
--
ALTER TABLE `quote_tax_rate`
  ADD CONSTRAINT `quote_tax_rate_foreign_quote_id_6212719395448` FOREIGN KEY (`quote_id`) REFERENCES `quote` (`id`),
  ADD CONSTRAINT `quote_tax_rate_foreign_tax_rate_id_62127193954a5` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rate` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `task`
--
ALTER TABLE `task`
  ADD CONSTRAINT `task_foreign_project_id_621fa279ec418` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  ADD CONSTRAINT `task_foreign_tax_rate_id_621fa279ec62e` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rate` (`id`);

--
-- Constraints for table `upload`
--
ALTER TABLE `upload`
  ADD CONSTRAINT `upload_foreign_client_id_61ef006c9ebcb` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_client`
--
ALTER TABLE `user_client`
  ADD CONSTRAINT `user_client_foreign_client_id_61ef006c9ec8b` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_client_foreign_user_id_61ef006c9ec2a` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_custom`
--
ALTER TABLE `user_custom`
  ADD CONSTRAINT `user_custom_foreign_user_id_61ef006c9eceb` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_inv`
--
ALTER TABLE `user_inv`
  ADD CONSTRAINT `user_inv_foreign_user_id_6257ce555a8b8` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
