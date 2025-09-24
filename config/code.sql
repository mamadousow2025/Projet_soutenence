-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 24, 2025 at 06:40 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lms_isep`
--

-- --------------------------------------------------------

--
-- Table structure for table `annonces`
--

CREATE TABLE `annonces` (
  `id` int(11) NOT NULL,
  `cours_id` int(11) NOT NULL,
  `enseignant_id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `contenu` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificats`
--

CREATE TABLE `certificats` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `cours_id` int(11) NOT NULL,
  `date_obtention` datetime DEFAULT current_timestamp(),
  `fichier_pdf` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cours`
--

CREATE TABLE `cours` (
  `id` int(11) NOT NULL,
  `enseignant_id` int(11) NOT NULL,
  `titre` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `filiere_id` int(11) NOT NULL,
  `image_couverture` varchar(255) DEFAULT NULL,
  `video_cours` varchar(255) DEFAULT NULL,
  `pdf_cours` varchar(255) DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `actif` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `title` varchar(255) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `annee` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cours`
--

INSERT INTO `cours` (`id`, `enseignant_id`, `titre`, `description`, `filiere_id`, `image_couverture`, `video_cours`, `pdf_cours`, `date_creation`, `actif`, `created_at`, `status`, `title`, `nom`, `annee`) VALUES
(8, 7, 'Programmation', 'xn xwan xa n zn szn a jscczj bjcs j sjz ns n', 2, 'uploads/img_689682c9ef0e3.jpg', 'uploads/vid_68967d86bf85d.mp4', 'uploads/pdf_68967d86bfc34.pdf', '2025-08-09 00:43:18', 1, '2025-08-09 00:43:18', 'active', '', '', 1),
(9, 7, '2 h s', 'xn xwan', 3, 'uploads/img_68967de1da3cb.jpg', NULL, 'uploads/pdf_68967de1db1a0.pdf', '2025-08-09 00:44:49', 1, '2025-08-09 00:44:49', 'active', '', '', 1),
(11, 7, '2 h s', 'xn xwan', 3, NULL, NULL, 'uploads/pdf_68967eb35994b.pdf', '2025-08-09 00:48:19', 1, '2025-08-09 00:48:19', 'active', '', '', 1),
(12, 7, '2 h s', 'xn xwan', 3, NULL, NULL, 'uploads/pdf_68968084199fb.pdf', '2025-08-09 00:56:04', 1, '2025-08-09 00:56:04', 'active', '', '', 1),
(13, 7, '2 h s', 'xn xwan', 3, NULL, NULL, 'uploads/pdf_689680b86f6c7.pdf', '2025-08-09 00:56:56', 1, '2025-08-09 00:56:56', 'active', '', '', 1),
(14, 7, 'Programmation', 'ProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammation', 2, 'uploads/img_6896812d5d318.jpg', NULL, 'uploads/pdf_6896812d5d721.pdf', '2025-08-09 00:58:53', 1, '2025-08-09 00:58:53', 'active', '', '', 1),
(15, 7, 'Programmation', 'ProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammation', 2, 'uploads/img_6896818fd301a.jpg', NULL, 'uploads/pdf_6896818fd352c.pdf', '2025-08-09 01:00:31', 1, '2025-08-09 01:00:31', 'active', '', '', 1),
(16, 7, 'Programmation', 'ProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammation', 2, 'uploads/img_689681d007063.jpg', NULL, 'uploads/pdf_689681d00756d.pdf', '2025-08-09 01:01:36', 1, '2025-08-09 01:01:36', 'active', '', '', 1),
(17, 7, 'Programmation', 'ProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammationProgrammation', 2, 'uploads/img_6896823030a76.jpg', NULL, 'uploads/pdf_6896823030ea7.pdf', '2025-08-09 01:03:12', 1, '2025-08-09 01:03:12', 'active', '', '', 1),
(18, 5, 'Programmation', 'm  m , .. tdtcgvvgccfxf', 2, 'uploads/img_6897a9b3f4054.jpg', NULL, 'uploads/pdf_6897a9b4018f4.pdf', '2025-08-09 22:04:04', 1, '2025-08-09 22:04:04', 'active', '', '', 1),
(19, 15, 'Programmation', 'ffynknknk jbhyvy nm m nbmnknjn', 15, 'uploads/img_6897ae4741589.jpg', NULL, 'uploads/pdf_6897ae4741fe2.pdf', '2025-08-09 22:23:35', 1, '2025-08-09 22:23:35', 'active', '', '', 1),
(20, 15, 'Programmation', 'jbhvhbjkll jbhjbnm,', 1, NULL, 'uploads/vid_6897dd285be9f.mp4', NULL, '2025-08-09 22:24:30', 1, '2025-08-09 22:24:30', 'active', '', '', 1),
(21, 16, 'Econnomie', 'n hs hwhwfjhfvwh wfh', 14, 'uploads/img_68a4b84a3386b.jpg', NULL, 'uploads/pdf_68a4b84a342ad.pdf', '2025-08-19 19:45:46', 1, '2025-08-19 19:45:46', 'active', '', '', 1),
(23, 21, 'Programmation', 'jv whfqwbjqwcasbj2bqm1d', 3, 'uploads/img_68a5b90ecc155.jpg', NULL, NULL, '2025-08-20 14:00:53', 1, '2025-08-20 14:00:53', 'active', '', '', 1),
(24, 29, 'Programmation', 'ejejbJcsamc mqwe', 3, 'uploads/img_68b33c2538afb.png', NULL, 'uploads/pdf_68b33c253b6dc.pdf', '2025-08-30 20:00:05', 1, '2025-08-30 20:00:05', 'active', '', '', 1),
(28, 32, 'Programmation', 'hvhvjm m', 5, 'uploads/img_68b37bd0f32c6.png', 'uploads/vid_68b37bd0f38cd.mp4', NULL, '2025-08-31 00:31:44', 1, '2025-08-31 00:31:44', 'active', '', '', 1),
(30, 32, 'Programmation', 'qwjaslkwnqasl fqehasbkqwnd.wqvJBASWQM SD', 5, 'uploads/img_68b37f18ae4f2.png', NULL, 'uploads/pdf_68b37f18b0af4.pdf', '2025-08-31 00:45:44', 1, '2025-08-31 00:45:44', 'active', '', '', 1),
(31, 32, 'Econnomie', '', 5, NULL, NULL, 'uploads/pdf_68b37f330865f.pdf', '2025-08-31 00:46:11', 1, '2025-08-31 00:46:11', 'active', '', '', 1),
(32, 38, 'Programmation', 'wsk,mqeasm jwjm', 1, 'uploads/img_68b38f7c3bb23.jpg', NULL, 'uploads/pdf_68b38f7c3c29f.pdf', '2025-08-31 01:55:40', 1, '2025-08-31 01:55:40', 'active', '', '', 1),
(33, 28, 'BBade de Django', 'ksldfmlejbfkawelmm', 1, 'uploads/img_68b41dd8a624b.jpg', NULL, 'uploads/pdf_68b41dd8a727c.pdf', '2025-08-31 12:03:04', 1, '2025-08-31 12:03:04', 'active', '', '', 1),
(34, 28, 'Programmation', 'hvihkb,m', 1, 'uploads/img_68b42057c2756.jpg', NULL, NULL, '2025-08-31 12:13:43', 1, '2025-08-31 12:13:43', 'active', '', '', 1),
(35, 28, 'Programmation', 'hvihkb,m', 1, 'uploads/img_68b42145641da.jpg', NULL, NULL, '2025-08-31 12:17:41', 1, '2025-08-31 12:17:41', 'active', '', '', 1),
(38, 28, 'Formation Bureautique', '', 1, 'uploads/img_68b4252d1a04d.jpg', NULL, NULL, '2025-08-31 12:34:21', 1, '2025-08-31 12:34:21', 'active', '', '', 1),
(39, 28, 'laravel', 'uuhuhknkih', 1, NULL, NULL, NULL, '2025-08-31 14:58:49', 1, '2025-08-31 14:58:49', 'active', '', '', 1),
(40, 28, 'Historique', '', 1, NULL, NULL, NULL, '2025-08-31 17:58:07', 1, '2025-08-31 17:58:07', 'active', '', '', 1),
(41, 48, 'Reseau', 'e j rkd j rfm r,', 2, NULL, NULL, NULL, '2025-09-17 21:44:38', 1, '2025-09-17 21:44:38', 'active', '', '', 1),
(42, 55, 'Agriculture', 'c est quoi argriculture', 25, NULL, NULL, NULL, '2025-09-18 00:05:47', 1, '2025-09-18 00:05:47', 'active', '', '', 1),
(43, 28, 'python', 'c est python', 1, NULL, NULL, NULL, '2025-09-18 01:44:49', 1, '2025-09-18 01:44:49', 'active', '', '', 0),
(44, 59, 'metier du rails', 'ndkncd', 24, NULL, NULL, NULL, '2025-09-18 01:51:34', 1, '2025-09-18 01:51:34', 'active', '', '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `filiere_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','validated','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cours_direct`
--

CREATE TABLE `cours_direct` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date_heure` datetime NOT NULL,
  `lien_visio` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cours_id` int(11) DEFAULT NULL,
  `duree` int(11) DEFAULT 60,
  `max_participants` int(11) DEFAULT 30,
  `statut` varchar(50) DEFAULT 'actif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cours_direct`
--

INSERT INTO `cours_direct` (`id`, `teacher_id`, `titre`, `description`, `date_heure`, `lien_visio`, `created_at`, `cours_id`, `duree`, `max_participants`, `statut`) VALUES
(1, 28, 'Programmation', ' cdjbjwe j ', '2025-09-19 17:20:00', 'https://meet.jit.si/class_68b85d3017220', '2025-09-03 15:22:24', 33, 60, 50, 'en_cours'),
(2, 28, 'Econnomie', 'ebehknehrn3n,rmvm dv>', '2025-09-27 20:16:00', 'https://meet.jit.si/class_68b88605be703', '2025-09-03 18:16:37', 35, 60, 51, 'en_cours'),
(3, 48, 'cablage', 'hehet', '2025-09-17 22:58:00', 'https://meet.jit.si/class_68cb2123a0e0d', '2025-09-17 20:59:15', 41, 60, 50, 'planifie'),
(4, 55, 'saison des pluie', 'comment cutiver', '2025-09-18 01:06:00', 'https://meet.jit.si/class_68cb30eb923dd', '2025-09-17 22:06:35', 42, 60, 50, 'en_cours'),
(5, 28, 'BBade de Django', 'bhhvvgvvg', '2025-09-23 05:45:00', 'https://meet.jit.si/class_68d2180f2c177', '2025-09-23 03:46:23', 43, 30, 99, 'planifie');

-- --------------------------------------------------------

--
-- Table structure for table `devoirs`
--

CREATE TABLE `devoirs` (
  `id` int(11) NOT NULL,
  `titre` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `date_limite` date DEFAULT NULL,
  `cours_id` int(10) UNSIGNED NOT NULL,
  `enseignant_id` int(11) NOT NULL,
  `points` int(11) DEFAULT 0,
  `instructions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `devoirs`
--

INSERT INTO `devoirs` (`id`, `titre`, `description`, `date_limite`, `cours_id`, `enseignant_id`, `points`, `instructions`) VALUES
(16, 'BBade de Django', 'jbjbjm', '2025-10-03', 33, 28, 20, 'n h');

-- --------------------------------------------------------

--
-- Table structure for table `devoirs_rendus`
--

CREATE TABLE `devoirs_rendus` (
  `id` int(11) NOT NULL,
  `devoir_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fichier` varchar(255) NOT NULL,
  `date_soumission` datetime DEFAULT current_timestamp(),
  `note` decimal(4,2) DEFAULT NULL,
  `commentaire` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enseignants`
--

CREATE TABLE `enseignants` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `matricule` varchar(20) DEFAULT NULL,
  `date_embauche` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `etudiant`
--

CREATE TABLE `etudiant` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `matricule` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `etudiant_id` int(11) DEFAULT NULL,
  `filiere_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedbacks`
--

CREATE TABLE `feedbacks` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `livrable_id` int(11) NOT NULL,
  `commentaire` text NOT NULL,
  `statut` varchar(20) DEFAULT 'en attente',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `note` int(11) DEFAULT NULL,
  `commentaire_enseignant` text DEFAULT NULL,
  `date_correction` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `filieres`
--

CREATE TABLE `filieres` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `filieres`
--

INSERT INTO `filieres` (`id`, `nom`, `description`) VALUES
(1, 'Développement Web et Mobile', NULL),
(2, 'Réseau et Télécommunication', NULL),
(3, 'Administration Système et Réseau', NULL),
(4, 'Génie Logiciel', NULL),
(5, 'Intelligence Artificielle', NULL),
(6, 'Sécurité Informatique', NULL),
(7, 'Bases de Données', NULL),
(8, 'Cloud Computing', NULL),
(9, 'Data Science', NULL),
(10, 'Internet des Objets (IoT)', NULL),
(11, 'Multimédia et Design', NULL),
(12, 'Télécommunications', NULL),
(13, 'Systèmes Embarqués', NULL),
(14, 'Administration Réseaux', NULL),
(15, 'Programmation Mobile', NULL),
(16, 'Gestion des Affaires Administratives et Financières', NULL),
(17, 'Contact Humain', NULL),
(18, 'Tourisme et Loisirs', NULL),
(19, 'CONSEIL AGRICOLE', NULL),
(20, 'TRANSPORT LOGISTIQUE ET MOBILITÉ URBAINE', NULL),
(21, 'TRANSPORT FERROVIAIRE', NULL),
(22, 'JOURNALISTE REPORTER D\'IMAGES', NULL),
(23, 'CRÉATION MULTIMÉDIA', NULL),
(24, 'MAINTENANCE VOIES FERRÉES', NULL),
(25, 'EXPLOITATION AGRICOLE', NULL),
(26, 'PRODUCTION ANIMALE', NULL),
(27, 'CONSEIL INFO-ÉNERGIE', NULL),
(28, 'ÉNERGIES RENOUVELABLES', NULL),
(29, 'GESTION IMMOBILIÈRE', NULL),
(30, 'GESTION DES OUVRAGES HYDRAULIQUES', NULL),
(31, 'ART GRAPHIQUE & NUMÉRIQUE', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `forums`
--

CREATE TABLE `forums` (
  `id` int(11) NOT NULL,
  `cours_id` int(11) NOT NULL,
  `titre` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inscriptions`
--

CREATE TABLE `inscriptions` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `inscription_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lecons`
--

CREATE TABLE `lecons` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `titre` varchar(150) NOT NULL,
  `type` enum('video','pdf','texte','audio') NOT NULL,
  `contenu` text NOT NULL,
  `ordre` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `livrables`
--

CREATE TABLE `livrables` (
  `id` int(11) NOT NULL,
  `tache_id` int(11) NOT NULL,
  `etudiant_id` int(11) NOT NULL,
  `fichier_nom` varchar(255) DEFAULT NULL,
  `fichier_chemin` varchar(500) DEFAULT NULL,
  `date_soumission` datetime DEFAULT current_timestamp(),
  `commentaire` text DEFAULT NULL,
  `note` decimal(4,2) DEFAULT NULL,
  `statut` varchar(20) DEFAULT 'en attente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `matieres`
--

CREATE TABLE `matieres` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `filiere_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `expediteur_id` int(11) NOT NULL,
  `destinataire_id` int(11) NOT NULL,
  `sujet` varchar(255) NOT NULL,
  `contenu` text NOT NULL,
  `is_bulk` tinyint(1) NOT NULL DEFAULT 0,
  `date_envoi` datetime DEFAULT current_timestamp(),
  `lu` tinyint(1) DEFAULT 0,
  `destinataire_role` int(1) NOT NULL DEFAULT 2,
  `expediteur_role` int(1) NOT NULL DEFAULT 2
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `expediteur_id`, `destinataire_id`, `sujet`, `contenu`, `is_bulk`, `date_envoi`, `lu`, `destinataire_role`, `expediteur_role`) VALUES
(1, 54, 28, 'cours de laravel ', 'je veux que tu m expliquer', 0, '2025-09-18 01:27:19', 1, 0, 0),
(2, 28, 22, 'b  n ', '  nk', 0, '2025-09-20 01:42:59', 0, 0, 0),
(4, 55, 41, 'cours de laravel ', 'bonjour hawa comment vous allez j esperque vous allez bien.', 0, '2025-09-20 02:19:00', 0, 0, 0),
(5, 41, 41, 'cours de laravel ', 'bonjour hawa comment vous allez j esperque vous allez bien.', 0, '2025-09-20 02:23:08', 0, 0, 0),
(6, 48, 58, 'hhvh', 'jbjbj', 0, '2025-09-20 02:34:54', 0, 0, 0),
(7, 28, 47, 'cours de laravel ', ' n', 0, '2025-09-20 03:07:35', 0, 0, 0),
(8, 42, 59, 'm m w', 'n n e', 0, '2025-09-20 03:09:08', 0, 0, 0),
(9, 28, 42, 'cours de laravel ', 'hvhvh', 0, '2025-09-20 03:21:00', 0, 0, 0),
(10, 55, 42, 'm ', ' klml', 0, '2025-09-20 03:58:02', 0, 0, 0),
(11, 55, 42, 'cours de laravel ', 'hvhvh', 0, '2025-09-20 04:13:18', 1, 0, 0),
(12, 55, 42, 'cours de laravel ', 'hvhvh', 0, '2025-09-20 04:13:34', 1, 0, 0),
(13, 42, 22, 'm m w ', 'hvh', 0, '2025-09-20 04:14:48', 0, 0, 0),
(14, 42, 22, 'm m w ', 'hvh', 0, '2025-09-20 04:15:00', 0, 0, 0),
(15, 42, 47, 'cours de laravel ', 'b bes be', 0, '2025-09-20 04:15:20', 0, 0, 0),
(16, 28, 54, 'cours de laravel ', ' w n d', 0, '2025-09-20 04:16:27', 0, 0, 0),
(18, 30, 20, 'cours de laravel ', 'vgchbknkj b', 0, '2025-09-21 21:29:09', 1, 0, 0),
(21, 3, 3, 'cours de laravel', 'b n n m', 0, '2025-09-22 00:45:12', 1, 2, 2),
(22, 3, 20, 'cours de laravel', 'w n wnd', 0, '2025-09-22 00:46:48', 0, 2, 2),
(23, 3, 20, 'cours de laravel', 'jbjj', 0, '2025-09-22 00:48:31', 0, 2, 2),
(24, 3, 20, 'cours de laravel', 'jbjj', 0, '2025-09-22 00:51:21', 0, 2, 2),
(25, 3, 20, 'cours de laravel', 'h jn j', 0, '2025-09-22 00:52:18', 0, 2, 2),
(26, 30, 20, 'cours de laravel ', 'v b n ', 0, '2025-09-22 00:55:40', 1, 0, 0),
(27, 3, 20, 'bonjour bonjour bonjour bonjour', 'bonjourbonjourbonjourbonjourbonjour', 0, '2025-09-22 00:59:46', 0, 2, 2),
(28, 20, 20, 'cours de laravel ', 'v b n ', 0, '2025-09-22 01:37:25', 1, 0, 0),
(30, 3, 20, 'n nn n', 'm m m', 0, '2025-09-22 01:41:29', 0, 2, 2),
(31, 20, 53, 'm m w', 'nn', 0, '2025-09-22 01:50:02', 0, 0, 0),
(32, 42, 27, 'cours de laravel ', 'j j ', 0, '2025-09-22 01:50:52', 0, 0, 0),
(33, 42, 27, 'cours de laravel', 'j j', 0, '2025-09-22 01:54:14', 0, 0, 0),
(34, 42, 53, 'm m w', 'nn', 0, '2025-09-22 01:54:24', 0, 0, 0),
(35, 3, 4, 'b  n', 'h h', 0, '2025-09-22 01:58:09', 0, 2, 2),
(36, 30, 60, 'n nn n', 'b b', 0, '2025-09-22 02:41:49', 0, 1, 3),
(37, 3, 4, 'cours de laravel', 'hh h', 0, '2025-09-22 03:01:47', 0, 2, 2),
(38, 3, 20, 'cours de laravel', 'n hn', 0, '2025-09-22 03:03:56', 0, 2, 2),
(39, 3, 20, 'hhvh', 'bbbbbb', 0, '2025-09-22 03:06:11', 0, 2, 2),
(40, 3, 28, 'hhvh', 'm', 0, '2025-09-22 03:09:46', 0, 2, 2),
(41, 30, 28, 'pour la certifcation', 'Bonjour Monsieur diallo comment vous allez j esperque tu vas \r\nil faut que les apprenenant revisent bien car bientot les exenan', 0, '2025-09-22 03:39:32', 0, 0, 0),
(42, 28, 42, 'cours django', 'bonjour ablaye je veux que vous reviser bien les cours', 0, '2025-09-22 03:41:58', 0, 0, 0),
(43, 30, 42, 'cours django', 'bonjour ablaye je veux que vous reviser bien les cours', 0, '2025-09-22 04:00:56', 0, 0, 0),
(44, 30, 42, 'cours django', 'bonjour ablaye je veux que vous reviser bien les cours', 0, '2025-09-22 04:20:09', 0, 0, 0),
(45, 30, 2, 'cours de laravel ', 'h h j k km  iciii', 1, '2025-09-22 04:30:08', 0, 0, 0),
(46, 30, 4, 'cours de laravel ', 'h h j k km  iciii', 1, '2025-09-22 04:30:08', 0, 0, 0),
(47, 30, 9, 'cours de laravel ', 'h h j k km  iciii', 1, '2025-09-22 04:30:08', 0, 0, 0),
(48, 30, 10, 'cours de laravel ', 'h h j k km  iciii', 1, '2025-09-22 04:30:08', 0, 0, 0),
(49, 30, 11, 'cours de laravel ', 'h h j k km  iciii', 1, '2025-09-22 04:30:08', 0, 0, 0),
(50, 30, 19, 'cours de laravel ', 'h h j k km  iciii', 1, '2025-09-22 04:30:08', 0, 0, 0),
(51, 30, 33, 'cours de laravel ', 'h h j k km  iciii', 1, '2025-09-22 04:30:08', 0, 0, 0),
(52, 30, 36, 'cours de laravel ', 'h h j k km  iciii', 1, '2025-09-22 04:30:08', 0, 0, 0),
(53, 30, 37, 'cours de laravel ', 'h h j k km  iciii', 1, '2025-09-22 04:30:08', 0, 0, 0),
(54, 30, 40, 'cours de laravel ', 'h h j k km  iciii', 1, '2025-09-22 04:30:08', 0, 0, 0),
(55, 30, 41, 'cours de laravel ', 'h h j k km  iciii', 1, '2025-09-22 04:30:08', 0, 0, 0),
(56, 30, 42, 'cours de laravel ', 'h h j k km  iciii', 1, '2025-09-22 04:30:08', 0, 0, 0),
(57, 30, 43, 'cours de laravel ', 'h h j k km  iciii', 1, '2025-09-22 04:30:08', 0, 0, 0),
(58, 30, 44, 'cours de laravel ', 'h h j k km  iciii', 1, '2025-09-22 04:30:08', 0, 0, 0),
(59, 30, 57, 'cours de laravel ', 'h h j k km  iciii', 1, '2025-09-22 04:30:08', 0, 0, 0),
(60, 28, 2, 'cours PHP', 'demain il on va faire un evaluatuion sur les bases de php', 1, '2025-09-22 04:33:04', 0, 0, 0),
(61, 28, 4, 'cours PHP', 'demain il on va faire un evaluatuion sur les bases de php', 1, '2025-09-22 04:33:04', 0, 0, 0),
(62, 28, 9, 'cours PHP', 'demain il on va faire un evaluatuion sur les bases de php', 1, '2025-09-22 04:33:04', 0, 0, 0),
(63, 28, 10, 'cours PHP', 'demain il on va faire un evaluatuion sur les bases de php', 1, '2025-09-22 04:33:04', 0, 0, 0),
(64, 28, 11, 'cours PHP', 'demain il on va faire un evaluatuion sur les bases de php', 1, '2025-09-22 04:33:04', 0, 0, 0),
(65, 28, 19, 'cours PHP', 'demain il on va faire un evaluatuion sur les bases de php', 1, '2025-09-22 04:33:04', 0, 0, 0),
(66, 28, 33, 'cours PHP', 'demain il on va faire un evaluatuion sur les bases de php', 1, '2025-09-22 04:33:04', 0, 0, 0),
(67, 28, 36, 'cours PHP', 'demain il on va faire un evaluatuion sur les bases de php', 1, '2025-09-22 04:33:04', 0, 0, 0),
(68, 28, 37, 'cours PHP', 'demain il on va faire un evaluatuion sur les bases de php', 1, '2025-09-22 04:33:04', 0, 0, 0),
(69, 28, 40, 'cours PHP', 'demain il on va faire un evaluatuion sur les bases de php', 1, '2025-09-22 04:33:04', 1, 0, 0),
(70, 28, 41, 'cours PHP', 'demain il on va faire un evaluatuion sur les bases de php', 1, '2025-09-22 04:33:04', 0, 0, 0),
(71, 28, 42, 'cours PHP', 'demain il on va faire un evaluatuion sur les bases de php', 1, '2025-09-22 04:33:04', 0, 0, 0),
(72, 28, 43, 'cours PHP', 'demain il on va faire un evaluatuion sur les bases de php', 1, '2025-09-22 04:33:04', 0, 0, 0),
(73, 28, 44, 'cours PHP', 'demain il on va faire un evaluatuion sur les bases de php', 1, '2025-09-22 04:33:04', 0, 0, 0),
(74, 28, 57, 'cours PHP', 'demain il on va faire un evaluatuion sur les bases de php', 1, '2025-09-22 04:33:04', 0, 0, 0),
(75, 42, 55, 'm m w', '  g g h  ', 0, '2025-09-22 04:34:35', 0, 0, 0),
(80, 30, 11, 'd&#039;absences non justifiées en DWM', 'Toutes les absences non justifiées seront recensées par Monsieur Djibril SAMBOU.\r\nTous ces apprenants qui ne justifieront pas leurs absences, seront immédiatement traduits en CONSEIL DE DISCIPLINE.\r\nJe répète tout apprenant qui ne veut plus étudier, n&#039;a qu&#039;à nous le signaler. Et la Direction de L&#039;ISEP THIES va vous enlever de la liste de la classe et surtout couper directement votre BOURSE.\r\nMerci pour votre compréhension.\r\nCordialement.\r\nMn', 1, '2025-09-23 04:17:03', 0, 0, 0),
(81, 30, 19, 'd&#039;absences non justifiées en DWM', 'Toutes les absences non justifiées seront recensées par Monsieur Djibril SAMBOU.\r\nTous ces apprenants qui ne justifieront pas leurs absences, seront immédiatement traduits en CONSEIL DE DISCIPLINE.\r\nJe répète tout apprenant qui ne veut plus étudier, n&#039;a qu&#039;à nous le signaler. Et la Direction de L&#039;ISEP THIES va vous enlever de la liste de la classe et surtout couper directement votre BOURSE.\r\nMerci pour votre compréhension.\r\nCordialement.\r\nMn', 1, '2025-09-23 04:17:03', 0, 0, 0),
(82, 30, 33, 'd&#039;absences non justifiées en DWM', 'Toutes les absences non justifiées seront recensées par Monsieur Djibril SAMBOU.\r\nTous ces apprenants qui ne justifieront pas leurs absences, seront immédiatement traduits en CONSEIL DE DISCIPLINE.\r\nJe répète tout apprenant qui ne veut plus étudier, n&#039;a qu&#039;à nous le signaler. Et la Direction de L&#039;ISEP THIES va vous enlever de la liste de la classe et surtout couper directement votre BOURSE.\r\nMerci pour votre compréhension.\r\nCordialement.\r\nMn', 1, '2025-09-23 04:17:03', 0, 0, 0),
(83, 30, 36, 'd&#039;absences non justifiées en DWM', 'Toutes les absences non justifiées seront recensées par Monsieur Djibril SAMBOU.\r\nTous ces apprenants qui ne justifieront pas leurs absences, seront immédiatement traduits en CONSEIL DE DISCIPLINE.\r\nJe répète tout apprenant qui ne veut plus étudier, n&#039;a qu&#039;à nous le signaler. Et la Direction de L&#039;ISEP THIES va vous enlever de la liste de la classe et surtout couper directement votre BOURSE.\r\nMerci pour votre compréhension.\r\nCordialement.\r\nMn', 1, '2025-09-23 04:17:03', 0, 0, 0),
(84, 30, 37, 'd&#039;absences non justifiées en DWM', 'Toutes les absences non justifiées seront recensées par Monsieur Djibril SAMBOU.\r\nTous ces apprenants qui ne justifieront pas leurs absences, seront immédiatement traduits en CONSEIL DE DISCIPLINE.\r\nJe répète tout apprenant qui ne veut plus étudier, n&#039;a qu&#039;à nous le signaler. Et la Direction de L&#039;ISEP THIES va vous enlever de la liste de la classe et surtout couper directement votre BOURSE.\r\nMerci pour votre compréhension.\r\nCordialement.\r\nMn', 1, '2025-09-23 04:17:03', 0, 0, 0),
(86, 30, 41, 'd&#039;absences non justifiées en DWM', 'Toutes les absences non justifiées seront recensées par Monsieur Djibril SAMBOU.\r\nTous ces apprenants qui ne justifieront pas leurs absences, seront immédiatement traduits en CONSEIL DE DISCIPLINE.\r\nJe répète tout apprenant qui ne veut plus étudier, n&#039;a qu&#039;à nous le signaler. Et la Direction de L&#039;ISEP THIES va vous enlever de la liste de la classe et surtout couper directement votre BOURSE.\r\nMerci pour votre compréhension.\r\nCordialement.\r\nMn', 1, '2025-09-23 04:17:03', 0, 0, 0),
(87, 30, 42, 'd&#039;absences non justifiées en DWM', 'Toutes les absences non justifiées seront recensées par Monsieur Djibril SAMBOU.\r\nTous ces apprenants qui ne justifieront pas leurs absences, seront immédiatement traduits en CONSEIL DE DISCIPLINE.\r\nJe répète tout apprenant qui ne veut plus étudier, n&#039;a qu&#039;à nous le signaler. Et la Direction de L&#039;ISEP THIES va vous enlever de la liste de la classe et surtout couper directement votre BOURSE.\r\nMerci pour votre compréhension.\r\nCordialement.\r\nMn', 1, '2025-09-23 04:17:03', 0, 0, 0),
(88, 30, 43, 'd&#039;absences non justifiées en DWM', 'Toutes les absences non justifiées seront recensées par Monsieur Djibril SAMBOU.\r\nTous ces apprenants qui ne justifieront pas leurs absences, seront immédiatement traduits en CONSEIL DE DISCIPLINE.\r\nJe répète tout apprenant qui ne veut plus étudier, n&#039;a qu&#039;à nous le signaler. Et la Direction de L&#039;ISEP THIES va vous enlever de la liste de la classe et surtout couper directement votre BOURSE.\r\nMerci pour votre compréhension.\r\nCordialement.\r\nMn', 1, '2025-09-23 04:17:03', 0, 0, 0),
(89, 30, 44, 'd&#039;absences non justifiées en DWM', 'Toutes les absences non justifiées seront recensées par Monsieur Djibril SAMBOU.\r\nTous ces apprenants qui ne justifieront pas leurs absences, seront immédiatement traduits en CONSEIL DE DISCIPLINE.\r\nJe répète tout apprenant qui ne veut plus étudier, n&#039;a qu&#039;à nous le signaler. Et la Direction de L&#039;ISEP THIES va vous enlever de la liste de la classe et surtout couper directement votre BOURSE.\r\nMerci pour votre compréhension.\r\nCordialement.\r\nMn', 1, '2025-09-23 04:17:03', 0, 0, 0),
(91, 61, 20, 'cours de laravel ', 'Bonjour', 0, '2025-09-23 04:51:39', 0, 0, 0),
(92, 61, 20, 'cours de laravel ', 'Bonjour', 0, '2025-09-23 04:52:11', 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `messagess`
--

CREATE TABLE `messagess` (
  `id` int(11) NOT NULL,
  `expediteur_id` int(11) NOT NULL,
  `expediteur_role` enum('enseignant','etudiant') NOT NULL,
  `destinataire_id` int(11) NOT NULL,
  `destinataire_role` enum('enseignant','etudiant') NOT NULL,
  `sujet` varchar(255) NOT NULL,
  `contenu` text NOT NULL,
  `lu` tinyint(1) DEFAULT 0,
  `date_envoi` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages_forum`
--

CREATE TABLE `messages_forum` (
  `id` int(11) NOT NULL,
  `sujet_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `date_post` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages_projet`
--

CREATE TABLE `messages_projet` (
  `id` int(11) NOT NULL,
  `projet_id` int(11) NOT NULL,
  `enseignant_id` int(11) DEFAULT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_envoi` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `id` int(11) NOT NULL,
  `cours_id` int(11) NOT NULL,
  `titre` varchar(150) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'normal',
  `description` text DEFAULT NULL,
  `contenu` text DEFAULT NULL,
  `ordre` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`id`, `cours_id`, `titre`, `type`, `description`, `contenu`, `ordre`, `created_at`, `updated_at`, `created_by`) VALUES
(1, 33, 'html css', 'normal', NULL, NULL, 2, '2025-08-31 10:57:24', '2025-08-31 10:57:24', 0),
(2, 38, 'laravel', 'normal', NULL, NULL, 0, '2025-08-31 11:15:12', '2025-08-31 11:15:12', 0),
(3, 34, 'css', 'normal', NULL, NULL, 0, '2025-08-31 12:10:29', '2025-08-31 12:10:29', 0),
(4, 39, 'PPO', 'normal', NULL, NULL, 1, '2025-08-31 12:59:38', '2025-08-31 12:59:38', 0),
(5, 40, 'Historique', 'normal', NULL, NULL, 1, '2025-08-31 15:58:34', '2025-08-31 15:58:34', 0),
(6, 41, 'cablage', 'normal', NULL, NULL, 2, '2025-09-17 19:45:16', '2025-09-17 19:45:16', 0),
(7, 42, 'procedure de la culture', 'normal', NULL, NULL, 1, '2025-09-17 22:09:26', '2025-09-17 22:09:26', 0),
(8, 43, 'teste unitaire', 'normal', NULL, NULL, 0, '2025-09-17 23:45:15', '2025-09-17 23:45:15', 0),
(9, 44, 'rails', 'normal', NULL, NULL, 0, '2025-09-17 23:51:55', '2025-09-17 23:51:55', 0);

-- --------------------------------------------------------

--
-- Table structure for table `modules_completed`
--

CREATE TABLE `modules_completed` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `modules_completed`
--

INSERT INTO `modules_completed` (`id`, `student_id`, `module_id`, `completed_at`) VALUES
(1, 37, 1, '2025-08-31 11:11:22'),
(2, 37, 2, '2025-08-31 11:16:42'),
(3, 37, 3, '2025-08-31 12:12:13'),
(4, 37, 4, '2025-08-31 13:01:30'),
(5, 37, 5, '2025-08-31 16:43:38'),
(6, 41, 5, '2025-09-03 18:56:53'),
(7, 41, 4, '2025-09-03 19:01:37'),
(8, 42, 5, '2025-09-06 00:10:53'),
(9, 42, 4, '2025-09-06 01:00:51'),
(10, 43, 5, '2025-09-10 13:00:34'),
(11, 54, 7, '2025-09-17 22:13:33');

-- --------------------------------------------------------

--
-- Table structure for table `module_contenus`
--

CREATE TABLE `module_contenus` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'normal',
  `titre` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `contenu` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ordre` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `module_contenus`
--

INSERT INTO `module_contenus` (`id`, `module_id`, `type`, `titre`, `description`, `contenu`, `created_at`, `updated_at`, `ordre`) VALUES
(1, 2, 'fichier', 'Programmation', NULL, '1756641668_CARTE VISITE.pdf', '2025-08-31 12:01:08', '2025-08-31 12:01:08', 0),
(2, 2, 'fichier', 'Programmation', NULL, '1756641713_CARTE VISITE.pdf', '2025-08-31 12:01:54', '2025-08-31 12:01:54', 0),
(3, 3, 'fichier', 'Programmation', NULL, '1756642262_CARTE VISITE.pdf', '2025-08-31 12:11:02', '2025-08-31 12:11:02', 0),
(4, 2, 'fichier', 'Programmation', NULL, '', '2025-08-31 13:00:32', '2025-08-31 13:00:32', 0),
(5, 2, 'fichier', 'Programmation', NULL, '', '2025-08-31 13:00:49', '2025-08-31 13:00:49', 0),
(6, 3, 'fichier', 'qjbjb', NULL, '1756648937_CARTE VISITE.pdf', '2025-08-31 14:02:17', '2025-08-31 14:02:17', 0),
(7, 1, 'fichier', 'Formation Bureautique', NULL, '1756654787_CMS plan du  cours.docx', '2025-08-31 15:39:47', '2025-08-31 15:39:47', 1),
(8, 1, 'lien', 'arava', NULL, 'https://youtu.be/jpZC476jE7c?si=j6UqqTHaikMSas9W', '2025-08-31 15:42:36', '2025-08-31 15:42:36', 2),
(9, 2, 'fichier', 'nskwf', NULL, '1756655659_2.png', '2025-08-31 15:54:19', '2025-08-31 15:54:19', 2),
(10, 4, 'fichier', '2 h s', NULL, '', '2025-08-31 15:55:57', '2025-08-31 15:55:57', 1),
(11, 4, 'fichier', 'Econnomie', NULL, '1756656274_2.png', '2025-08-31 16:04:34', '2025-08-31 16:04:34', 0),
(12, 4, 'video', 'html css', NULL, '1756657379_WhatsApp Vidéo 2024-04-16 à 13.32.10_bc25c883.mp4', '2025-08-31 16:22:59', '2025-08-31 16:22:59', 1),
(13, 5, 'lien', 'e2e2', NULL, 'youtube:jpZC476jE7c', '2025-08-31 16:35:25', '2025-08-31 16:35:25', 2),
(14, 5, 'video', 'html css', NULL, '1756659061_WhatsApp Vidéo 2024-04-16 à 13.32.10_bc25c883.mp4', '2025-08-31 16:51:01', '2025-08-31 16:51:01', 0),
(15, 6, 'fichier', 'le fichier de cablage', NULL, '1758138356_1756641668_CARTE VISITE.pdf', '2025-09-17 19:45:56', '2025-09-17 19:45:56', 1),
(16, 7, 'lien', 'cultuver', NULL, 'youtube:AlCAovzYRMo', '2025-09-17 22:09:57', '2025-09-17 22:09:57', 5),
(17, 8, 'lien', 'python', NULL, 'youtube:oUJolR5bX6g', '2025-09-17 23:46:16', '2025-09-17 23:46:16', 0),
(18, 9, 'fichier', 'rails', NULL, '1758153146_1756641668_CARTE VISITE.pdf', '2025-09-17 23:52:26', '2025-09-17 23:52:26', 0);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `contenu` text NOT NULL,
  `type` enum('message','annonce','systeme','autre') DEFAULT 'systeme',
  `reference_id` int(11) DEFAULT NULL,
  `lu` tinyint(1) DEFAULT 0,
  `date_creation` datetime NOT NULL,
  `date_lecture` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `titre`, `contenu`, `type`, `reference_id`, `lu`, `date_creation`, `date_lecture`) VALUES
(1, 2, 'Nouveau message de Elhadji sow', 'cours de laravel ', 'message', NULL, 0, '2025-09-22 04:30:08', NULL),
(2, 4, 'Nouveau message de Elhadji sow', 'cours de laravel ', 'message', NULL, 0, '2025-09-22 04:30:08', NULL),
(3, 9, 'Nouveau message de Elhadji sow', 'cours de laravel ', 'message', NULL, 0, '2025-09-22 04:30:08', NULL),
(4, 10, 'Nouveau message de Elhadji sow', 'cours de laravel ', 'message', NULL, 0, '2025-09-22 04:30:08', NULL),
(5, 11, 'Nouveau message de Elhadji sow', 'cours de laravel ', 'message', NULL, 0, '2025-09-22 04:30:08', NULL),
(6, 19, 'Nouveau message de Elhadji sow', 'cours de laravel ', 'message', NULL, 0, '2025-09-22 04:30:08', NULL),
(7, 33, 'Nouveau message de Elhadji sow', 'cours de laravel ', 'message', NULL, 0, '2025-09-22 04:30:08', NULL),
(8, 36, 'Nouveau message de Elhadji sow', 'cours de laravel ', 'message', NULL, 0, '2025-09-22 04:30:08', NULL),
(9, 37, 'Nouveau message de Elhadji sow', 'cours de laravel ', 'message', NULL, 0, '2025-09-22 04:30:08', NULL),
(10, 40, 'Nouveau message de Elhadji sow', 'cours de laravel ', 'message', NULL, 0, '2025-09-22 04:30:08', NULL),
(11, 41, 'Nouveau message de Elhadji sow', 'cours de laravel ', 'message', NULL, 0, '2025-09-22 04:30:08', NULL),
(12, 42, 'Nouveau message de Elhadji sow', 'cours de laravel ', 'message', NULL, 0, '2025-09-22 04:30:08', NULL),
(13, 43, 'Nouveau message de Elhadji sow', 'cours de laravel ', 'message', NULL, 0, '2025-09-22 04:30:08', NULL),
(14, 44, 'Nouveau message de Elhadji sow', 'cours de laravel ', 'message', NULL, 0, '2025-09-22 04:30:08', NULL),
(15, 57, 'Nouveau message de Elhadji sow', 'cours de laravel ', 'message', NULL, 0, '2025-09-22 04:30:08', NULL),
(16, 2, 'Nouveau message de Nouhou diallo Diallo', 'cours PHP', 'message', NULL, 0, '2025-09-22 04:33:04', NULL),
(17, 4, 'Nouveau message de Nouhou diallo Diallo', 'cours PHP', 'message', NULL, 0, '2025-09-22 04:33:04', NULL),
(18, 9, 'Nouveau message de Nouhou diallo Diallo', 'cours PHP', 'message', NULL, 0, '2025-09-22 04:33:04', NULL),
(19, 10, 'Nouveau message de Nouhou diallo Diallo', 'cours PHP', 'message', NULL, 0, '2025-09-22 04:33:04', NULL),
(20, 11, 'Nouveau message de Nouhou diallo Diallo', 'cours PHP', 'message', NULL, 0, '2025-09-22 04:33:04', NULL),
(21, 19, 'Nouveau message de Nouhou diallo Diallo', 'cours PHP', 'message', NULL, 0, '2025-09-22 04:33:04', NULL),
(22, 33, 'Nouveau message de Nouhou diallo Diallo', 'cours PHP', 'message', NULL, 0, '2025-09-22 04:33:04', NULL),
(23, 36, 'Nouveau message de Nouhou diallo Diallo', 'cours PHP', 'message', NULL, 0, '2025-09-22 04:33:04', NULL),
(24, 37, 'Nouveau message de Nouhou diallo Diallo', 'cours PHP', 'message', NULL, 0, '2025-09-22 04:33:04', NULL),
(25, 40, 'Nouveau message de Nouhou diallo Diallo', 'cours PHP', 'message', NULL, 0, '2025-09-22 04:33:04', NULL),
(26, 41, 'Nouveau message de Nouhou diallo Diallo', 'cours PHP', 'message', NULL, 0, '2025-09-22 04:33:04', NULL),
(27, 42, 'Nouveau message de Nouhou diallo Diallo', 'cours PHP', 'message', NULL, 0, '2025-09-22 04:33:04', NULL),
(28, 43, 'Nouveau message de Nouhou diallo Diallo', 'cours PHP', 'message', NULL, 0, '2025-09-22 04:33:04', NULL),
(29, 44, 'Nouveau message de Nouhou diallo Diallo', 'cours PHP', 'message', NULL, 0, '2025-09-22 04:33:04', NULL),
(30, 57, 'Nouveau message de Nouhou diallo Diallo', 'cours PHP', 'message', NULL, 0, '2025-09-22 04:33:04', NULL),
(31, 55, 'Nouveau message de Ablaye konate', 'm m w', 'message', NULL, 0, '2025-09-22 04:34:35', NULL),
(32, 2, 'Nouveau message de Elhadji sow', 'd&#039;absences non justifiées en DWM', 'message', NULL, 0, '2025-09-23 04:17:03', NULL),
(33, 4, 'Nouveau message de Elhadji sow', 'd&#039;absences non justifiées en DWM', 'message', NULL, 0, '2025-09-23 04:17:03', NULL),
(34, 9, 'Nouveau message de Elhadji sow', 'd&#039;absences non justifiées en DWM', 'message', NULL, 0, '2025-09-23 04:17:03', NULL),
(35, 10, 'Nouveau message de Elhadji sow', 'd&#039;absences non justifiées en DWM', 'message', NULL, 0, '2025-09-23 04:17:03', NULL),
(36, 11, 'Nouveau message de Elhadji sow', 'd&#039;absences non justifiées en DWM', 'message', NULL, 0, '2025-09-23 04:17:03', NULL),
(37, 19, 'Nouveau message de Elhadji sow', 'd&#039;absences non justifiées en DWM', 'message', NULL, 0, '2025-09-23 04:17:03', NULL),
(38, 33, 'Nouveau message de Elhadji sow', 'd&#039;absences non justifiées en DWM', 'message', NULL, 0, '2025-09-23 04:17:03', NULL),
(39, 36, 'Nouveau message de Elhadji sow', 'd&#039;absences non justifiées en DWM', 'message', NULL, 0, '2025-09-23 04:17:03', NULL),
(40, 37, 'Nouveau message de Elhadji sow', 'd&#039;absences non justifiées en DWM', 'message', NULL, 0, '2025-09-23 04:17:03', NULL),
(41, 40, 'Nouveau message de Elhadji sow', 'd&#039;absences non justifiées en DWM', 'message', NULL, 0, '2025-09-23 04:17:03', NULL),
(42, 41, 'Nouveau message de Elhadji sow', 'd&#039;absences non justifiées en DWM', 'message', NULL, 0, '2025-09-23 04:17:03', NULL),
(43, 42, 'Nouveau message de Elhadji sow', 'd&#039;absences non justifiées en DWM', 'message', NULL, 0, '2025-09-23 04:17:03', NULL),
(44, 43, 'Nouveau message de Elhadji sow', 'd&#039;absences non justifiées en DWM', 'message', NULL, 0, '2025-09-23 04:17:03', NULL),
(45, 44, 'Nouveau message de Elhadji sow', 'd&#039;absences non justifiées en DWM', 'message', NULL, 0, '2025-09-23 04:17:03', NULL),
(46, 57, 'Nouveau message de Elhadji sow', 'd&#039;absences non justifiées en DWM', 'message', NULL, 0, '2025-09-23 04:17:03', NULL),
(47, 20, 'Nouveau message de Niang Fadal', 'cours de laravel ', 'message', NULL, 0, '2025-09-23 04:51:39', NULL),
(48, 20, 'Nouveau message de Niang Fadal', 'cours de laravel ', 'message', NULL, 0, '2025-09-23 04:52:11', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `progression`
--

CREATE TABLE `progression` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `modules_total` int(11) DEFAULT 0,
  `modules_faits` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `progression`
--

INSERT INTO `progression` (`id`, `student_id`, `course_id`, `modules_total`, `modules_faits`, `updated_at`) VALUES
(1, 37, 33, 1, 1, '2025-08-31 11:11:22'),
(2, 37, 38, 1, 1, '2025-08-31 11:16:42'),
(3, 37, 34, 1, 1, '2025-08-31 12:12:13'),
(4, 37, 39, 1, 1, '2025-08-31 13:01:30'),
(5, 37, 40, 1, 1, '2025-08-31 16:43:38'),
(6, 41, 40, 1, 1, '2025-09-03 18:56:53'),
(7, 41, 39, 1, 1, '2025-09-03 19:01:37'),
(8, 42, 40, 1, 1, '2025-09-06 00:10:53'),
(9, 42, 39, 1, 1, '2025-09-06 01:00:51'),
(10, 43, 40, 1, 1, '2025-09-10 13:00:34'),
(11, 54, 42, 1, 1, '2025-09-17 22:13:33');

-- --------------------------------------------------------

--
-- Table structure for table `progressions`
--

CREATE TABLE `progressions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `lecon_id` int(11) DEFAULT NULL,
  `quiz_id` int(11) DEFAULT NULL,
  `statut` enum('en_cours','termine') DEFAULT 'en_cours',
  `date_modification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projets`
--

CREATE TABLE `projets` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type_projet` enum('cahier_charge','sujet_pratique','creation') NOT NULL,
  `enseignant_id` int(11) NOT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_limite` date DEFAULT NULL,
  `statut` enum('actif','termine','suspendu') DEFAULT 'actif',
  `filiere_id` int(11) NOT NULL,
  `objectifs` text DEFAULT NULL,
  `criteres_evaluation` text DEFAULT NULL,
  `ressources_necessaires` text DEFAULT NULL,
  `competences_developpees` text DEFAULT NULL,
  `date_modification` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projets`
--

INSERT INTO `projets` (`id`, `titre`, `description`, `type_projet`, `enseignant_id`, `date_creation`, `date_limite`, `statut`, `filiere_id`, `objectifs`, `criteres_evaluation`, `ressources_necessaires`, `competences_developpees`, `date_modification`) VALUES
(5, 'BBade de Django', 'n an s', 'cahier_charge', 28, '2025-09-24 04:06:20', '2025-09-26', 'actif', 30, ' ms x', 'ms m cs', 'ms m sc', ' m s', NULL),
(6, 'laravel', 'hvhxbqjbjq', 'cahier_charge', 42, '2025-09-24 04:18:44', '2025-09-24', 'actif', 1, 'a m mqa m a', ' mx m w, , z', ' xzma m wdm', ' xa  xam ', NULL),
(7, 'laravel', 'hvhxbqjbjq', 'cahier_charge', 42, '2025-09-24 04:19:21', '2025-09-24', 'actif', 1, 'a m mqa m a', ' mx m w, , z', ' xzma m wdm', ' xa  xam ', NULL),
(8, 'laravel', 'n xm MD ', 'cahier_charge', 28, '2025-09-24 04:21:15', '2025-09-30', 'actif', 1, 'M M WM ', 'XZM S CS', 'C SM M SCW', 'M CM C', NULL),
(9, 'laravel', 'hvhxbqjbjq', 'cahier_charge', 28, '2025-09-24 04:21:25', '2025-09-24', 'actif', 1, 'a m mqa m a', ' mx m w, , z', ' xzma m wdm', ' xa  xam ', NULL),
(10, 'laravel', 'n xm MD ', 'cahier_charge', 42, '2025-09-24 05:17:29', '2025-09-30', 'actif', 1, 'M M WM ', 'XZM S CS', 'C SM M SCW', 'M CM C', NULL),
(11, 'python', 'voici le projet concernant python', 'sujet_pratique', 28, '2025-09-24 05:20:43', '2025-09-24', 'actif', 1, ' a wdm d', ' sam maw ', 'max m xa ', 'mxa m ax', '2025-09-24 05:22:52'),
(12, 'laravel', 'n xm MD ', 'cahier_charge', 28, '2025-09-24 05:23:46', '2025-09-30', 'actif', 1, 'M M WM ', 'XZM S CS', 'C SM M SCW', 'M CM C', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `projet_etudiants`
--

CREATE TABLE `projet_etudiants` (
  `id` int(11) NOT NULL,
  `projet_id` int(11) NOT NULL,
  `etudiant_id` int(11) NOT NULL,
  `date_affectation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projet_fichiers`
--

CREATE TABLE `projet_fichiers` (
  `id` int(11) NOT NULL,
  `projet_id` int(11) NOT NULL,
  `nom_fichier` varchar(255) NOT NULL,
  `chemin_fichier` varchar(255) NOT NULL,
  `date_upload` timestamp NOT NULL DEFAULT current_timestamp(),
  `nom_original` varchar(255) NOT NULL,
  `type_fichier` varchar(100) NOT NULL,
  `taille_fichier` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projet_fichiers`
--

INSERT INTO `projet_fichiers` (`id`, `projet_id`, `nom_fichier`, `chemin_fichier`, `date_upload`, `nom_original`, `type_fichier`, `taille_fichier`) VALUES
(1, 5, '68d3521c96526_tic-tac (2).png', '../uploads/projets/5/68d3521c96526_tic-tac (2).png', '2025-09-24 02:06:20', 'tic-tac (2).png', 'image', 3476),
(2, 6, '68d355047ead7_1756641668_CARTE VISITE.pdf', '../uploads/projets/6/68d355047ead7_1756641668_CARTE VISITE.pdf', '2025-09-24 02:18:44', '1756641668_CARTE VISITE.pdf', 'document', 409985),
(3, 7, '68d35529bf914_1756641668_CARTE VISITE.pdf', '../uploads/projets/7/68d35529bf914_1756641668_CARTE VISITE.pdf', '2025-09-24 02:19:21', '1756641668_CARTE VISITE.pdf', 'document', 409985),
(4, 8, '68d3559b8208a_Guide_Concours.pdf', '../uploads/projets/8/68d3559b8208a_Guide_Concours.pdf', '2025-09-24 02:21:15', 'Guide_Concours.pdf', 'document', 2026060),
(5, 9, '68d355a5b231d_1756641668_CARTE VISITE.pdf', '../uploads/projets/9/68d355a5b231d_1756641668_CARTE VISITE.pdf', '2025-09-24 02:21:25', '1756641668_CARTE VISITE.pdf', 'document', 409985),
(6, 10, '68d362c9f0bd2_Guide_Concours.pdf', '../uploads/projets/10/68d362c9f0bd2_Guide_Concours.pdf', '2025-09-24 03:17:29', 'Guide_Concours.pdf', 'document', 2026060),
(7, 11, '68d3638b90368_localisateur (1).png', '../uploads/projets/11/68d3638b90368_localisateur (1).png', '2025-09-24 03:20:43', 'localisateur (1).png', 'image', 11146),
(8, 12, '68d36442f4065_Guide_Concours.pdf', '../uploads/projets/12/68d36442f4065_Guide_Concours.pdf', '2025-09-24 03:23:47', 'Guide_Concours.pdf', 'document', 2026060);

-- --------------------------------------------------------

--
-- Table structure for table `projet_liens`
--

CREATE TABLE `projet_liens` (
  `id` int(11) NOT NULL,
  `projet_id` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `date_ajout` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `quizz_id` int(11) DEFAULT NULL,
  `question` text NOT NULL,
  `type` enum('qcm','vrai_faux','texte_court') NOT NULL,
  `question_text` text NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `type_question` varchar(50) NOT NULL,
  `reponse_text` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `quizz_id`, `question`, `type`, `question_text`, `points`, `type_question`, `reponse_text`) VALUES
(5, 19, '', 'qcm', 'M M E', 1, 'qcm', NULL),
(6, 20, '', 'qcm', 'qui est ce que DWM', 1, 'qcm', NULL),
(7, 21, '', 'qcm', 'n j', 1, 'qcm', NULL),
(8, 21, '', 'qcm', 'n j', 1, 'qcm', NULL),
(9, 21, '', 'qcm', 'n j', 1, 'qcm', NULL),
(10, 21, '', 'qcm', 'jj', 1, 'qcm', NULL),
(11, 22, '', 'qcm', '2d2', 1, 'qcm', NULL),
(12, 23, '', 'qcm', 'qwff', 1, 'qcm', NULL),
(13, 23, '', 'qcm', 'qwff', 1, 'qcm', NULL),
(14, 24, '', 'qcm', 'n', 1, 'qcm', NULL),
(15, 25, '', 'qcm', 'm m', 1, 'qcm', NULL),
(16, 22, '', 'qcm', 'mc c', 1, 'qcm', NULL),
(17, 27, '', 'qcm', 'qui est le capitaine tu senegal', 1, 'qcm', NULL),
(18, 28, '', 'qcm', 'le capitaine du senegal', 1, 'qcm', NULL),
(19, 30, '', 'qcm', 'comment cultuver arachide au senegale', 1, 'qcm', NULL),
(20, 31, '', 'qcm', 'jhv jm', 1, 'qcm', NULL),
(21, 32, '', 'qcm', 'comment cultuver au senegal ?', 1, 'qcm', NULL),
(22, 33, '', 'qcm', 'c est quoi developpement ?', 1, 'texte_libre', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `quiz`
--

CREATE TABLE `quiz` (
  `id` int(11) NOT NULL,
  `enseignant_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `titre` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `actif` tinyint(1) DEFAULT 1,
  `date_limite` date NOT NULL DEFAULT '2099-12-31',
  `cours_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quizz`
--

CREATE TABLE `quizz` (
  `id` int(11) NOT NULL,
  `enseignant_id` int(11) NOT NULL,
  `module_id` int(11) DEFAULT NULL,
  `course_id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_limite` datetime NOT NULL,
  `points` int(11) DEFAULT 0,
  `actif` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `type_quiz` varchar(50) NOT NULL DEFAULT 'QCM',
  `duree` int(11) NOT NULL DEFAULT 0,
  `cours_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'actif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizz`
--

INSERT INTO `quizz` (`id`, `enseignant_id`, `module_id`, `course_id`, `titre`, `description`, `date_creation`, `date_limite`, `points`, `actif`, `created_at`, `type_quiz`, `duree`, `cours_id`, `status`) VALUES
(1, 28, NULL, 33, 'Programmation', 'vhvhjn', '2025-09-03 21:38:07', '2025-09-17 21:22:00', 20, 1, '2025-09-03 23:05:38', 'QCM', 0, NULL, 'actif'),
(2, 28, NULL, 39, 'html css', 'jwbjndk', '2025-09-03 21:49:48', '2025-09-25 21:49:00', 20, 1, '2025-09-03 23:05:38', 'QCM', 0, NULL, 'actif'),
(3, 28, NULL, 40, 'html css', 'nsq nwd', '2025-09-03 22:30:47', '2025-10-03 22:30:00', 20, 1, '2025-09-03 23:05:38', 'QCM', 0, NULL, 'actif'),
(4, 28, NULL, 39, 'vhvh', 'fxgc', '2025-09-03 22:37:41', '2025-09-23 22:37:00', 20, 1, '2025-09-03 23:05:38', 'QCM', 0, NULL, 'actif'),
(5, 28, NULL, 39, 'BBade de Django', 'h vh', '2025-09-03 22:47:42', '2025-09-27 22:47:00', 20, 1, '2025-09-03 23:05:38', 'QCM', 0, NULL, 'actif'),
(6, 28, NULL, 38, 'BBade de Django', 'm qda', '2025-09-03 22:58:26', '2025-09-17 22:58:00', 20, 1, '2025-09-03 23:05:38', 'QCM', 0, NULL, 'actif'),
(7, 28, NULL, 38, 'BBade de Django', 'm qda', '2025-09-03 23:03:07', '2025-09-17 22:58:00', 20, 1, '2025-09-03 23:05:38', 'QCM', 0, NULL, 'actif'),
(8, 28, NULL, 33, 'b  b', '', '2025-09-03 23:03:51', '2025-09-04 23:03:00', 20, 1, '2025-09-03 23:05:38', 'QCM', 0, NULL, 'actif'),
(9, 28, NULL, 33, 'b  b', '', '2025-09-03 23:05:44', '2025-09-04 23:03:00', 20, 1, '2025-09-03 23:05:44', 'QCM', 0, NULL, 'actif'),
(10, 28, NULL, 33, 'Econnomie', '2e2r', '2025-09-05 16:03:24', '2025-09-11 15:58:00', 20, 1, '2025-09-05 16:03:24', 'qcm', 30, NULL, 'actif'),
(11, 28, NULL, 33, 'b  b', '', '2025-09-05 16:05:39', '2025-09-04 23:03:00', 20, 1, '2025-09-05 16:05:39', 'QCM', 0, NULL, 'actif'),
(12, 28, NULL, 38, 'BBade de Django', 'm qda', '2025-09-05 16:05:45', '2025-09-17 22:58:00', 20, 1, '2025-09-05 16:05:45', 'QCM', 0, NULL, 'actif'),
(13, 28, NULL, 39, 'BBade de Django', 'h vh', '2025-09-05 16:05:56', '2025-09-27 22:47:00', 20, 1, '2025-09-05 16:05:56', 'QCM', 0, NULL, 'actif'),
(14, 28, NULL, 38, 'Econnomie', 'srwrd', '2025-09-05 16:23:14', '2025-10-31 16:23:00', 20, 1, '2025-09-05 16:23:14', 'vrai_faux', 30, NULL, 'actif'),
(15, 28, NULL, 39, 'BBade de Django', 'jbj', '2025-09-05 16:25:33', '2025-10-23 22:38:00', 20, 1, '2025-09-05 16:25:33', 'qcm', 30, NULL, 'actif'),
(16, 28, NULL, 39, 'vhvh', 'fxgc', '2025-09-05 16:25:40', '2025-09-23 22:37:00', 20, 1, '2025-09-05 16:25:40', 'qcm', 30, NULL, 'actif'),
(17, 28, NULL, 38, 'BBade de Django', 'n hihih', '2025-09-05 16:36:12', '2025-09-24 22:36:00', 20, 1, '2025-09-05 16:36:12', 'qcm', 30, NULL, 'actif'),
(18, 28, NULL, 33, 'html css', 'nnnnn', '2025-09-05 16:37:57', '2025-09-17 16:37:00', 20, 1, '2025-09-05 16:37:57', 'vrai_faux', 30, NULL, 'actif'),
(19, 28, NULL, 38, 'Econnomie', 'j j', '2025-09-05 17:27:32', '2025-09-22 17:27:00', 20, 1, '2025-09-05 17:27:32', 'qcm', 30, NULL, 'actif'),
(20, 28, NULL, 33, '2 h s', 'completre bien les quizze', '2025-09-05 18:05:05', '2025-09-25 18:05:00', 20, 1, '2025-09-05 18:05:05', 'qcm', 30, NULL, 'actif'),
(21, 28, NULL, 33, 'Econnomie', 'bj', '2025-09-05 18:07:35', '2025-09-17 18:07:00', 20, 1, '2025-09-05 18:07:35', 'qcm', 30, NULL, 'actif'),
(22, 28, NULL, 33, '2 h s', 'dqd', '2025-09-05 18:14:58', '2025-10-03 18:14:00', 20, 1, '2025-09-05 18:14:58', 'qcm', 30, NULL, 'actif'),
(23, 28, NULL, 33, 'Econnomie', 'wr', '2025-09-05 18:25:40', '2025-09-23 18:25:00', 20, 1, '2025-09-05 18:25:40', 'qcm', 30, NULL, 'actif'),
(24, 28, NULL, 33, 'html css', 'v', '2025-09-06 00:43:48', '2025-09-15 00:43:00', 20, 1, '2025-09-06 00:43:48', 'qcm', 30, NULL, 'actif'),
(25, 28, NULL, 33, 'Econnomie', 'k ,', '2025-09-06 01:37:00', '2025-09-18 01:36:00', 20, 1, '2025-09-06 01:37:00', 'qcm', 30, NULL, 'actif'),
(26, 28, NULL, 33, 'Econnomie', 'n n', '2025-09-06 15:22:37', '2025-09-09 15:22:00', 20, 1, '2025-09-06 15:22:37', 'association', 30, NULL, 'actif'),
(27, 28, NULL, 33, 'Elhafji', 'wjame fm wefm m wefd', '2025-09-10 23:29:56', '2025-09-10 23:29:00', 20, 1, '2025-09-10 23:29:56', 'qcm', 30, NULL, 'actif'),
(28, 28, NULL, 35, 'Fallou', 'fallou', '2025-09-10 23:34:11', '2025-10-10 23:33:00', 20, 1, '2025-09-10 23:34:11', 'qcm', 30, NULL, 'actif'),
(29, 28, NULL, 33, 'BBade de Django', 'n n n', '2025-09-12 20:38:19', '2025-09-15 20:38:00', 20, 1, '2025-09-12 20:38:19', 'qcm', 30, NULL, 'actif'),
(30, 55, NULL, 42, 'cuture senegalaise', 'comment cultuver', '2025-09-18 00:32:13', '2025-09-18 00:31:00', 20, 1, '2025-09-18 00:32:13', 'qcm', 15, NULL, 'actif'),
(31, 55, NULL, 42, 'cuture senegalaise', 'jnk nkj', '2025-09-18 00:37:58', '2025-09-18 02:37:00', 20, 1, '2025-09-18 00:37:58', 'qcm', 30, NULL, 'actif'),
(32, 55, NULL, 42, 'cultuver', 'dwn wdjjdw', '2025-09-18 00:40:31', '2025-09-18 00:40:00', 20, 1, '2025-09-18 00:40:31', 'qcm', 30, NULL, 'actif'),
(33, 55, NULL, 42, 'developpement', 'ggcg', '2025-09-18 00:43:31', '2025-09-27 00:43:00', 20, 1, '2025-09-18 00:43:31', 'texte_libre', 30, NULL, 'actif');

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `course_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_devoir`
--

CREATE TABLE `quiz_devoir` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `etudiant_id` int(11) NOT NULL,
  `reponse` text DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `date_passage` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'inactive'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reponses`
--

CREATE TABLE `reponses` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `reponse` text NOT NULL,
  `correcte` tinyint(1) DEFAULT 0,
  `reponse_text` text DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reponses`
--

INSERT INTO `reponses` (`id`, `question_id`, `reponse`, `correcte`, `reponse_text`, `is_correct`) VALUES
(1, 13, '', 0, 'qd', 1),
(2, 13, '', 0, 'qd', 0),
(3, 13, '', 0, 'qd', 0),
(4, 13, '', 0, 'qda', 0),
(5, 14, '', 0, 'h', 1),
(6, 14, '', 0, 'h', 0),
(7, 14, '', 0, 'h', 0),
(8, 14, '', 0, 'h', 0),
(9, 15, '', 0, 'eit', 1),
(10, 15, '', 0, 'qda', 0),
(11, 15, '', 0, 'h', 0),
(12, 15, '', 0, '  n qs', 0),
(13, 17, '', 0, 'coulibali', 1),
(14, 17, '', 0, 'mane', 0),
(15, 17, '', 0, 'ndiaye', 0),
(16, 17, '', 0, 'sarr', 0),
(17, 18, '', 0, 'hee e', 1),
(18, 18, '', 0, 'j d ', 0),
(19, 18, '', 0, ' r fr', 0),
(20, 18, '', 0, ' n rf', 0),
(21, 19, '', 0, 'bien', 1),
(22, 19, '', 0, 'bien', 0),
(23, 19, '', 0, 'bien', 0),
(24, 19, '', 0, 'bien', 0),
(25, 20, '', 0, 'eit', 1),
(26, 20, '', 0, 'h', 0),
(27, 20, '', 0, 'qda', 0),
(28, 20, '', 0, 'qda', 0),
(29, 21, '', 0, '1', 1),
(30, 21, '', 0, '2', 0),
(31, 21, '', 0, '3', 0),
(32, 21, '', 0, '4', 0),
(33, 22, '', 0, 'c est l augamentation', 1);

-- --------------------------------------------------------

--
-- Table structure for table `reponses_etudiants`
--

CREATE TABLE `reponses_etudiants` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `etudiant_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `reponse` text DEFAULT NULL,
  `date_reponse` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reponses_etudiants`
--

INSERT INTO `reponses_etudiants` (`id`, `quiz_id`, `etudiant_id`, `question_id`, `reponse`, `date_reponse`) VALUES
(1, 28, 42, 18, 'hee e', '2025-09-10 23:58:18'),
(2, 28, 42, 18, 'hee e', '2025-09-11 00:23:51'),
(3, 33, 54, 22, 'c est l augamentation', '2025-09-18 00:44:34');

-- --------------------------------------------------------

--
-- Table structure for table `ressources`
--

CREATE TABLE `ressources` (
  `id` int(11) NOT NULL,
  `cours_id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `fichier` varchar(255) DEFAULT NULL,
  `date_ajout` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`) VALUES
(3, 'admin'),
(2, 'enseignant'),
(1, 'etudiant');

-- --------------------------------------------------------

--
-- Table structure for table `sessions_live`
--

CREATE TABLE `sessions_live` (
  `id` int(11) NOT NULL,
  `cours_id` int(11) NOT NULL,
  `titre` varchar(150) DEFAULT NULL,
  `date_session` datetime NOT NULL,
  `lien_visio` varchar(255) NOT NULL,
  `actif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sujets_forum`
--

CREATE TABLE `sujets_forum` (
  `id` int(11) NOT NULL,
  `forum_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `taches`
--

CREATE TABLE `taches` (
  `id` int(11) NOT NULL,
  `projet_id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `statut` enum('a_faire','en_cours','termine') DEFAULT 'a_faire',
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `priorite` enum('basse','moyenne','haute') DEFAULT 'moyenne',
  `date_limite` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `taches`
--

INSERT INTO `taches` (`id`, `projet_id`, `titre`, `description`, `statut`, `date_creation`, `date_debut`, `date_fin`, `priorite`, `date_limite`) VALUES
(1, 11, 'anyser les besoibj w ', '  n n n n ', 'a_faire', '2025-09-24 05:20:43', NULL, NULL, 'moyenne', '2025-09-24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `filiere_id` int(11) DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `actif` tinyint(1) DEFAULT 1,
  `role` varchar(50) NOT NULL DEFAULT 'student',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `nom_filiere` varchar(100) DEFAULT NULL,
  `annee` enum('1','2') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nom`, `prenom`, `email`, `password`, `role_id`, `filiere_id`, `date_creation`, `actif`, `role`, `created_at`, `nom_filiere`, `annee`) VALUES
(2, 'sow', 'fallou', 'fallou@gmail.com', '$2y$10$/DAn2Q6QekSpOXeQrlSiqu8HWWu0q1tJOHfvO/r7c6kYL5mDM7E86', 1, 1, '2025-08-08 17:18:38', 1, 'student', '2025-08-30 03:14:34', NULL, NULL),
(3, 'sow', 'Elhadji', 'isa@gmail.com', '$2y$10$BMsSUTS61qy/Ri9U0aJGBussXLAOSWxtiAjuDIGZnngxjzoCmdYUy', 1, 9, '2025-08-08 17:20:31', 1, 'student', '2025-08-30 03:14:34', NULL, NULL),
(4, 'sow', 'Elhadji', 'awa@gmail.com', '$2y$10$O3aKN8oh7QMIBHUD9UvyHeMeNOeduK.nLKrlOIIqslTxX5WNDnGtK', 1, 1, '2025-08-08 17:37:08', 1, 'student', '2025-08-30 03:14:34', NULL, NULL),
(5, 'sow', 'awa', 'aliou@gmail.com', '$2y$10$BevTWtwEjuG7BvJt9WdRAO6tZIV7r0Q2k2cF8G0y6/P1Xs9QdxHFu', 2, NULL, '2025-08-08 21:17:18', 1, 'student', '2025-08-30 03:14:34', NULL, NULL),
(6, 'sow', 'Elhadji', 'aliouu@gmail.com', '$2y$10$tBqNwU0GfPSTf837ZRWD3u/iT.5mJnDQ8CGWT1rGOPd5yNyFmh3mK', 1, 2, '2025-08-08 21:18:29', 1, 'student', '2025-08-30 03:14:34', NULL, NULL),
(7, 'sow', 'Elhadji Mamadou Sow', 'sow@gmail.com', '$2y$10$S9oTZbh73ZBne/sS7DOf3OGkLFUe0aGKJCEkM.tABO3ZB5XGQYcvu', 2, NULL, '2025-08-08 22:45:45', 1, 'student', '2025-08-30 03:14:34', NULL, NULL),
(8, 'sow', 'Elhadji Mamadou Sow', 'soww@gmail.com', '$2y$10$VY3KL/iEdGRmhONMYY4zBO.m0eHf7GFSXiUZIxpVYFa2ANZr6BJzi', 1, 14, '2025-08-08 22:48:33', 1, 'student', '2025-08-30 03:14:34', NULL, NULL),
(9, 'sow', 'Amadou', 'soow@gmail.com', '$2y$10$anhDmCeqwfrlVOQDRbIf7.TxzahF.XVgyz6TfrpdamOkZcENP5sUa', 1, 1, '2025-08-09 01:43:36', 1, 'student', '2025-08-30 03:14:34', NULL, NULL),
(10, 'sow', 'fifi', 'fi@gmail.com', '$2y$10$76tmrKBCQq0glGKjg/8kOepLpxULnQHfrBn0r33kBfWB1/nprBBWC', 1, 1, '2025-08-09 01:50:16', 1, 'student', '2025-08-30 03:14:34', NULL, NULL),
(11, 'sow', 'isaa', 'aw@gmail.com', '$2y$10$XTk/KkA12n0P3ZMiYIsc.OO4qNwJ3wgtitKv5KTN5UPJCIOOl8InO', 1, 1, '2025-08-09 01:58:23', 1, 'student', '2025-08-30 03:14:34', NULL, NULL),
(12, 'sow', 'ma', 'ma@gmail.com', '$2y$10$jpF6LdDUimLzwBiy8HEotu/gTVJUhsh6FJ1HNygcjtkulk2yz/Dvy', 1, 14, '2025-08-09 02:57:59', 1, 'student', '2025-08-30 03:14:34', NULL, NULL),
(13, 'Diallo', 'Ami', 'ami@gmail.com', '$2y$10$bP8S/WLMKy4gg3nAiB1HfOxL7tU3eVxiJTvNyvrCAOaQe/QWv0Gpm', 2, NULL, '2025-08-09 22:06:40', 1, 'student', '2025-08-30 03:14:34', NULL, NULL),
(15, 'Ndiaye', 'Fallou', 'ndiayee@gmail.com', '$2y$10$YJ4D7TkiRQXkzVx0J5dK3ORJ9EsRMI3RJUHncnYqa0YPtE49iwzzy', 2, NULL, '2025-08-09 22:22:00', 1, 'student', '2025-08-30 03:14:34', NULL, NULL),
(17, 'sow', 'Elhadji Sow', 'diarraa@gmail.com', '$2y$10$ZtaYyWG/ypsO4Ec9GgVl7.S6jyDWrP9.hfZpNc9pBQfjBT8PFvdqy', 2, NULL, '2025-08-19 21:20:05', 1, 'student', '2025-08-30 03:14:34', NULL, NULL),
(19, 'Vilan', 'Hady', 'vilane@gmail.com', '$2y$10$50tl6QVt5rtnYX./Nqmu6ORABlDBxhYn09B5HBRs6LrbHWOafi7e2', 1, 1, '2025-08-19 21:26:40', 1, 'student', '2025-08-30 03:14:34', NULL, NULL),
(20, 'Bassoum', 'Haw oumar', 'bassoum@gmail.com', '$2y$10$kyNdcEmnGGLf4Rq9Wf77muuXrrymVFWCu5BKeWRv4xjJVWLzsjHZW', 2, NULL, '2025-08-19 21:28:10', 1, 'student', '2025-08-30 03:14:34', NULL, NULL),
(21, 'fall', 'awa', 'falla@gmail.com', '$2y$10$oaPo0N/2JvJzJ1YH2RZrjujtLCCA9LgBp1209WqTXbliU0Z.rn85.', 2, NULL, '2025-08-20 13:59:57', 1, 'student', '2025-08-30 03:14:34', NULL, NULL),
(22, 'sow', 'Elhadji', 'so0ow@gmail.com', '$2y$10$251wdIU5IE5OJjShjARDJe5.eyCxN3AWAUaZEDmttvavM.goRRqNW', 3, NULL, '2025-08-21 23:23:53', 1, 'student', '2025-08-30 03:14:34', NULL, NULL),
(27, 'sow', 'Elhadji', 'ba@gmail.com', '$2y$10$k48NoK2GDmfshvB1YWouhe2UKdDjlCi/tpkBDMRH.vBFAW7ehdKvy', 2, 13, '2025-08-30 03:43:28', 1, 'student', '2025-08-30 03:43:28', NULL, NULL),
(28, 'Diallo', 'Nouhou diallo', 'n@gmail.com', '$2y$10$1ST1j3Y5mW/Ea1TGdBN0ZeB9PKzrRmSjRb0O370UG4HVaRUTTEVFW', 2, 1, '2025-08-30 13:56:42', 1, 'etudiant', '2025-08-30 13:56:42', NULL, NULL),
(30, 'sow', 'Elhadji', 'm@gmail.com', '$2y$10$bZRb0uZvzoiIK44shtLmb.rOTNCxWnyxumVymtgaeMHaRjzea/JS6', 3, NULL, '2025-08-30 20:04:31', 1, 'student', '2025-08-30 20:04:31', NULL, NULL),
(31, 'konate', 'salif', 's@gmail.com', '$2y$10$lARNoPuYOksTCkx/dluSNujFahkedjAdYibkL0khuHulo9ZDB9p56', 2, 1, '2025-08-30 21:28:19', 1, 'student', '2025-08-30 21:28:19', NULL, NULL),
(32, 'sow', 'Issa', 'b@gmail.com', '$2y$10$31XXSuAogD1nCpC/XP3CxOSsTOjSrpaX5uhjidlOBgK2c7AEosNU6', 2, 5, '2025-08-30 21:30:57', 1, 'student', '2025-08-30 21:30:57', NULL, NULL),
(33, 'keita', 'Doudou', 'd@gmail.com', '$2y$10$e7CyESzlOIM1hOzVrP511..MUndCWDYbOiXorS4kVtAccaFcEPFfO', 1, 1, '2025-08-31 00:55:25', 1, 'student', '2025-08-31 00:55:25', NULL, NULL),
(34, 'sow', 'Elhadji', 'u@gmail.com', '$2y$10$4fH4/eP0E2CucXr0J82U7.ChBVJQ3/idIHGbosd2Hp4dmrGg5vOUu', 1, 15, '2025-08-31 01:20:37', 1, 'student', '2025-08-31 01:20:37', NULL, NULL),
(35, 'fall', 'Ahmadou', 'h@gmail.com', '$2y$10$agoKOTA3kXotlSQohH2hu.X4cen7gycK.pVmoTMCcTPZ3RY92y8r2', 1, 14, '2025-08-31 01:33:46', 1, 'student', '2025-08-31 01:33:46', NULL, NULL),
(36, 'Sow', 'Faly', 'f@gmail.edusndwm.com', '$2y$10$hSIE8xgKvbL7lVfb./C5H.nhGmPIqZvb0XW6a8EqKfa61OV9j9.6G', 1, 1, '2025-08-31 01:44:16', 1, 'student', '2025-08-31 01:44:16', NULL, NULL),
(37, 'Diarra', 'Mamadou', 'l@gmail.com', '$2y$10$NU/LRL.Y6tKTzRx8goSIhe3bgmD8BX41DsjqXdxNc1peQN/564JXi', 1, 1, '2025-08-31 01:46:04', 1, 'student', '2025-08-31 01:46:04', NULL, NULL),
(38, 'Diallo', 'Bobo', 'am@gmail.com', '$2y$10$bo.dSRfMFvPCeBptzyqL5uEIcIsR9j15tjLKc6KghGGyxi7Lex1Kq', 2, 1, '2025-08-31 01:54:39', 1, 'student', '2025-08-31 01:54:39', NULL, NULL),
(40, 'ali', 'Elhadji', 'i@gmail.com', '$2y$10$zZG6HktdZxY5JBl73QZrKeEpEqNLZAkZrNtvFkJW1yi8VvHAQE0hu', 1, 1, '2025-09-03 20:48:05', 1, 'student', '2025-09-03 20:48:05', NULL, ''),
(41, 'Bassoum', 'Elhadji', 'x@gmail.com', '$2y$10$XFVPWFLKkpDqSa3QrY7ql./2ImfVKig4FF/5V5Pnz4KE.3TOG/sMO', 1, 1, '2025-09-03 20:52:48', 1, 'student', '2025-09-03 20:52:48', NULL, '2'),
(42, 'konate', 'Ablaye', 'e@gmail.com', '$2y$10$GVVH84i4Rajaxig8nBKi4uqt8sBLKC.j.kSr4URwUfCq4KyBSNA3i', 1, 1, '2025-09-03 21:03:48', 1, 'student', '2025-09-03 21:03:48', NULL, '2'),
(43, 'sow', 'Elhadji', 'v@gmail.com', '$2y$10$NhjyNMQj/v6vUWtQCUbjLOroZmcFDM57q.kduTQclSBo.MvowdxxC', 1, 1, '2025-09-10 14:59:39', 1, 'student', '2025-09-10 14:59:39', NULL, '1'),
(44, 'sow', 'Elhadji', 'z@gmail.com', '$2y$10$zhwSDVI2moSQtk39kmgRiOHuwMvIndXL/zGt3irnxRYyvle3A9mbu', 1, 1, '2025-09-11 11:03:59', 1, 'student', '2025-09-11 11:03:59', NULL, '2'),
(47, 'Ndao', 'Abddalah', 'uu@gmail.com', '$2y$10$qchYztfs2YRLgJJihhSi5u6lTBcr23iqTJ8bilsOtNA.spn7plLqO', 1, 2, '2025-09-17 21:38:19', 1, 'student', '2025-09-17 21:38:19', NULL, '2'),
(48, 'sow', 'Elhadji', 'nn@gmail.com', '$2y$10$61j8ggnBZcqGbeL5CLLCueg5XxsgReEc07XNY3bL7XVz/LQR2bnsu', 2, 2, '2025-09-17 21:43:24', 1, 'student', '2025-09-17 21:43:24', NULL, ''),
(49, 'Gueye', 'Noumbe', 'ee@gmail.com', '$2y$10$Umku3/xJsSAakW5l9L2fP.iW7kieABaq6gXxEVfzaQkxARcmBb4Fy', 1, 14, '2025-09-17 22:20:34', 1, 'student', '2025-09-17 22:20:34', NULL, '1'),
(50, 'sow', 'Elhadji', 'oo@gmail.com', '$2y$10$.ll1WxSrkCJBKQMiIq2ZkuNrL5ZLhhQONYbp1SIeb/4s7oOUNSYNC', 1, 2, '2025-09-17 22:22:17', 1, 'student', '2025-09-17 22:22:17', NULL, '2'),
(51, 'sow', 'Elhadji', 'ii@gmail.com', '$2y$10$8JgqFQVdRC6kKvJC/6Szau3p./9YqhgP7pKCtNEVpdVQFPrEM6Wfe', 1, 9, '2025-09-17 22:24:01', 1, 'student', '2025-09-17 22:24:01', NULL, '2'),
(52, 'sow', 'Elhadji', 'kk@gmail.com', '$2y$10$avk7XAcijNGa3DJxony42enEGGPnBlCsX6hPBoQVHa7DdhNL5V5lW', 1, 2, '2025-09-17 22:36:40', 1, 'student', '2025-09-17 22:36:40', NULL, '1'),
(53, 'sow', 'Elhadji', 'xn@gmail.com', '$2y$10$ptSh3V.ta6S7egFeJ4Frte962R8Qa7EGdaFT/iOrYKDvHd1dkh58S', 1, 6, '2025-09-17 23:10:49', 1, 'student', '2025-09-17 23:10:49', NULL, '2'),
(54, 'Ndao', 'Amadou', 'vv@gmail.com', '$2y$10$UiTzYhZkMNOcCmJK3Ob.o.G3zNM8XqtFMwbO2yvp3UOQ2jn3vhakS', 1, 25, '2025-09-18 00:02:21', 1, 'student', '2025-09-18 00:02:21', NULL, '1'),
(55, 'Ndao', 'Amadou', 'vvv@gmail.com', '$2y$10$.XMdudOQkioQjkzmDjZrU.h3ZKyeSrALHK0yLA1cyizT5/57Iq1VS', 2, 25, '2025-09-18 00:04:46', 1, 'student', '2025-09-18 00:04:46', NULL, ''),
(56, 'Ndiaye', 'Monsieur', 'nd@gmail.com', '$2y$10$QeyW4jhGAYGl0Oguaf87zOWHKTh5jALnj1Vcl9qTOTU0PJWc1tu0y', 1, 25, '2025-09-18 00:13:12', 1, 'student', '2025-09-18 00:13:12', NULL, '1'),
(57, 'fall', 'Moussa', 'ss@gmail.com', '$2y$10$N9cPwpPsxrEIW10p/gtSeuJBJS8LzLw97Eul/LbKcU/qU8e85iLsK', 1, 1, '2025-09-18 01:47:23', 1, 'student', '2025-09-18 01:47:23', NULL, '1'),
(58, 'ba', 'Fanta', 'gg@gmail.com', '$2y$10$6ZNCh.LZrWmSX4UpP8hvru5GJ9EayAg9eY9k7IXr9adBBQ0.XWOrW', 1, 24, '2025-09-18 01:49:50', 1, 'student', '2025-09-18 01:49:50', NULL, '1'),
(59, 'sow', 'Elhadji', 'ggg@gmail.com', '$2y$10$K4g9XseXvjKhK0Wd8wkDD.vnc9Z4p6STr30NinZKpzV/.VODuyimW', 2, 24, '2025-09-18 01:50:31', 1, 'student', '2025-09-18 01:50:31', NULL, ''),
(60, 'sow', 'Elhadji', 'gggg@gmail.com', '$2y$10$TXK7OCR0HyfQvm/VaLQUD.qf7L1xYd8yNFp8kRiDH9AwA0G5r0gvW', 1, 24, '2025-09-18 01:53:52', 1, 'etudiant', '2025-09-18 01:53:52', NULL, '2'),
(61, 'Fadal', 'Niang', 'fadal.isep@gmail.com', '$2y$10$KbbP5rJROpdp2zT9XYPfqepgKKwoFMf/tPGoYg9nwkHIEF0Gw4Cvi', 1, 1, '2025-09-23 04:50:14', 1, 'student', '2025-09-23 04:50:14', NULL, '2');

-- --------------------------------------------------------

--
-- Table structure for table `user_filieres`
--

CREATE TABLE `user_filieres` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) DEFAULT NULL,
  `nom_filiere` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `filiere_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_matieres`
--

CREATE TABLE `user_matieres` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `matiere_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `annonces`
--
ALTER TABLE `annonces`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cours_id` (`cours_id`),
  ADD KEY `enseignant_id` (`enseignant_id`);

--
-- Indexes for table `certificats`
--
ALTER TABLE `certificats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `cours_id` (`cours_id`);

--
-- Indexes for table `cours`
--
ALTER TABLE `cours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `filiere_id` (`filiere_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `filiere_id` (`filiere_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cours_direct`
--
ALTER TABLE `cours_direct`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `devoirs`
--
ALTER TABLE `devoirs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `devoirs_rendus`
--
ALTER TABLE `devoirs_rendus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `devoir_id` (`devoir_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `enseignants`
--
ALTER TABLE `enseignants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `matricule` (`matricule`);

--
-- Indexes for table `etudiant`
--
ALTER TABLE `etudiant`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `matricule` (`matricule`);

--
-- Indexes for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `filieres`
--
ALTER TABLE `filieres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Indexes for table `forums`
--
ALTER TABLE `forums`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cours_id` (`cours_id`);

--
-- Indexes for table `inscriptions`
--
ALTER TABLE `inscriptions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lecons`
--
ALTER TABLE `lecons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `livrables`
--
ALTER TABLE `livrables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tache_id` (`tache_id`),
  ADD KEY `etudiant_id` (`etudiant_id`);

--
-- Indexes for table `matieres`
--
ALTER TABLE `matieres`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expediteur_id` (`expediteur_id`),
  ADD KEY `destinataire_id` (`destinataire_id`);

--
-- Indexes for table `messagess`
--
ALTER TABLE `messagess`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages_forum`
--
ALTER TABLE `messages_forum`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sujet_id` (`sujet_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `messages_projet`
--
ALTER TABLE `messages_projet`
  ADD PRIMARY KEY (`id`),
  ADD KEY `projet_id` (`projet_id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cours_id` (`cours_id`);

--
-- Indexes for table `modules_completed`
--
ALTER TABLE `modules_completed`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `module_contenus`
--
ALTER TABLE `module_contenus`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_user_lu` (`user_id`,`lu`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `progression`
--
ALTER TABLE `progression`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `progressions`
--
ALTER TABLE `progressions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `lecon_id` (`lecon_id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `projets`
--
ALTER TABLE `projets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `enseignant_id` (`enseignant_id`);

--
-- Indexes for table `projet_etudiants`
--
ALTER TABLE `projet_etudiants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `projet_id` (`projet_id`),
  ADD KEY `etudiant_id` (`etudiant_id`);

--
-- Indexes for table `projet_fichiers`
--
ALTER TABLE `projet_fichiers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `projet_id` (`projet_id`);

--
-- Indexes for table `projet_liens`
--
ALTER TABLE `projet_liens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `projet_id` (`projet_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `questions_ibfk_1` (`quizz_id`);

--
-- Indexes for table `quiz`
--
ALTER TABLE `quiz`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `fk_quiz_cours` (`cours_id`);

--
-- Indexes for table `quizz`
--
ALTER TABLE `quizz`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `quiz_devoir`
--
ALTER TABLE `quiz_devoir`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reponses`
--
ALTER TABLE `reponses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `reponses_etudiants`
--
ALTER TABLE `reponses_etudiants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`),
  ADD KEY `etudiant_id` (`etudiant_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `ressources`
--
ALTER TABLE `ressources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cours_id` (`cours_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `sessions_live`
--
ALTER TABLE `sessions_live`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cours_id` (`cours_id`);

--
-- Indexes for table `sujets_forum`
--
ALTER TABLE `sujets_forum`
  ADD PRIMARY KEY (`id`),
  ADD KEY `forum_id` (`forum_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `taches`
--
ALTER TABLE `taches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `projet_id` (`projet_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `filiere_id` (`filiere_id`);

--
-- Indexes for table `user_filieres`
--
ALTER TABLE `user_filieres`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `filiere_id` (`filiere_id`);

--
-- Indexes for table `user_matieres`
--
ALTER TABLE `user_matieres`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `matiere_id` (`matiere_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `annonces`
--
ALTER TABLE `annonces`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `certificats`
--
ALTER TABLE `certificats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cours`
--
ALTER TABLE `cours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cours_direct`
--
ALTER TABLE `cours_direct`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `devoirs`
--
ALTER TABLE `devoirs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `devoirs_rendus`
--
ALTER TABLE `devoirs_rendus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enseignants`
--
ALTER TABLE `enseignants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `etudiant`
--
ALTER TABLE `etudiant`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `filieres`
--
ALTER TABLE `filieres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `forums`
--
ALTER TABLE `forums`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inscriptions`
--
ALTER TABLE `inscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lecons`
--
ALTER TABLE `lecons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `livrables`
--
ALTER TABLE `livrables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `matieres`
--
ALTER TABLE `matieres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `messagess`
--
ALTER TABLE `messagess`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages_forum`
--
ALTER TABLE `messages_forum`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages_projet`
--
ALTER TABLE `messages_projet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `modules_completed`
--
ALTER TABLE `modules_completed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `module_contenus`
--
ALTER TABLE `module_contenus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `progression`
--
ALTER TABLE `progression`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `progressions`
--
ALTER TABLE `progressions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projets`
--
ALTER TABLE `projets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `projet_etudiants`
--
ALTER TABLE `projet_etudiants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projet_fichiers`
--
ALTER TABLE `projet_fichiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `projet_liens`
--
ALTER TABLE `projet_liens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `quiz`
--
ALTER TABLE `quiz`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quizz`
--
ALTER TABLE `quizz`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_devoir`
--
ALTER TABLE `quiz_devoir`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reponses`
--
ALTER TABLE `reponses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `reponses_etudiants`
--
ALTER TABLE `reponses_etudiants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ressources`
--
ALTER TABLE `ressources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sessions_live`
--
ALTER TABLE `sessions_live`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sujets_forum`
--
ALTER TABLE `sujets_forum`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `taches`
--
ALTER TABLE `taches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `user_filieres`
--
ALTER TABLE `user_filieres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `user_matieres`
--
ALTER TABLE `user_matieres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `annonces`
--
ALTER TABLE `annonces`
  ADD CONSTRAINT `annonces_ibfk_1` FOREIGN KEY (`cours_id`) REFERENCES `cours` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `annonces_ibfk_2` FOREIGN KEY (`enseignant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `certificats`
--
ALTER TABLE `certificats`
  ADD CONSTRAINT `certificats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `certificats_ibfk_2` FOREIGN KEY (`cours_id`) REFERENCES `cours` (`id`);

--
-- Constraints for table `cours`
--
ALTER TABLE `cours`
  ADD CONSTRAINT `cours_ibfk_1` FOREIGN KEY (`filiere_id`) REFERENCES `filieres` (`id`);

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`filiere_id`) REFERENCES `filieres` (`id`),
  ADD CONSTRAINT `courses_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `cours_direct`
--
ALTER TABLE `cours_direct`
  ADD CONSTRAINT `cours_direct_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `devoirs_rendus`
--
ALTER TABLE `devoirs_rendus`
  ADD CONSTRAINT `devoirs_rendus_ibfk_1` FOREIGN KEY (`devoir_id`) REFERENCES `devoirs` (`id`),
  ADD CONSTRAINT `devoirs_rendus_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `enseignants`
--
ALTER TABLE `enseignants`
  ADD CONSTRAINT `enseignants_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `forums`
--
ALTER TABLE `forums`
  ADD CONSTRAINT `forums_ibfk_1` FOREIGN KEY (`cours_id`) REFERENCES `cours` (`id`);

--
-- Constraints for table `lecons`
--
ALTER TABLE `lecons`
  ADD CONSTRAINT `lecons_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`);

--
-- Constraints for table `livrables`
--
ALTER TABLE `livrables`
  ADD CONSTRAINT `livrables_ibfk_1` FOREIGN KEY (`tache_id`) REFERENCES `taches` (`id`),
  ADD CONSTRAINT `livrables_ibfk_2` FOREIGN KEY (`etudiant_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`expediteur_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`destinataire_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `messages_forum`
--
ALTER TABLE `messages_forum`
  ADD CONSTRAINT `messages_forum_ibfk_1` FOREIGN KEY (`sujet_id`) REFERENCES `sujets_forum` (`id`),
  ADD CONSTRAINT `messages_forum_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `messages_projet`
--
ALTER TABLE `messages_projet`
  ADD CONSTRAINT `messages_projet_ibfk_1` FOREIGN KEY (`projet_id`) REFERENCES `projets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_projet_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `modules`
--
ALTER TABLE `modules`
  ADD CONSTRAINT `modules_ibfk_1` FOREIGN KEY (`cours_id`) REFERENCES `cours` (`id`);

--
-- Constraints for table `modules_completed`
--
ALTER TABLE `modules_completed`
  ADD CONSTRAINT `modules_completed_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `modules_completed_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `progression`
--
ALTER TABLE `progression`
  ADD CONSTRAINT `progression_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `progression_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `cours` (`id`);

--
-- Constraints for table `progressions`
--
ALTER TABLE `progressions`
  ADD CONSTRAINT `progressions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `progressions_ibfk_2` FOREIGN KEY (`lecon_id`) REFERENCES `lecons` (`id`),
  ADD CONSTRAINT `progressions_ibfk_3` FOREIGN KEY (`quiz_id`) REFERENCES `quiz` (`id`);

--
-- Constraints for table `projets`
--
ALTER TABLE `projets`
  ADD CONSTRAINT `projets_ibfk_1` FOREIGN KEY (`enseignant_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `projet_etudiants`
--
ALTER TABLE `projet_etudiants`
  ADD CONSTRAINT `projet_etudiants_ibfk_1` FOREIGN KEY (`projet_id`) REFERENCES `projets` (`id`),
  ADD CONSTRAINT `projet_etudiants_ibfk_2` FOREIGN KEY (`etudiant_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `projet_fichiers`
--
ALTER TABLE `projet_fichiers`
  ADD CONSTRAINT `projet_fichiers_ibfk_1` FOREIGN KEY (`projet_id`) REFERENCES `projets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projet_liens`
--
ALTER TABLE `projet_liens`
  ADD CONSTRAINT `projet_liens_ibfk_1` FOREIGN KEY (`projet_id`) REFERENCES `projets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`quizz_id`) REFERENCES `quizz` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quiz`
--
ALTER TABLE `quiz`
  ADD CONSTRAINT `fk_quiz_cours` FOREIGN KEY (`cours_id`) REFERENCES `cours` (`id`),
  ADD CONSTRAINT `quiz_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`);

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`);

--
-- Constraints for table `reponses`
--
ALTER TABLE `reponses`
  ADD CONSTRAINT `reponses_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`);

--
-- Constraints for table `reponses_etudiants`
--
ALTER TABLE `reponses_etudiants`
  ADD CONSTRAINT `reponses_etudiants_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizz` (`id`),
  ADD CONSTRAINT `reponses_etudiants_ibfk_2` FOREIGN KEY (`etudiant_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reponses_etudiants_ibfk_3` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`);

--
-- Constraints for table `ressources`
--
ALTER TABLE `ressources`
  ADD CONSTRAINT `ressources_ibfk_1` FOREIGN KEY (`cours_id`) REFERENCES `cours` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sessions_live`
--
ALTER TABLE `sessions_live`
  ADD CONSTRAINT `sessions_live_ibfk_1` FOREIGN KEY (`cours_id`) REFERENCES `cours` (`id`);

--
-- Constraints for table `sujets_forum`
--
ALTER TABLE `sujets_forum`
  ADD CONSTRAINT `sujets_forum_ibfk_1` FOREIGN KEY (`forum_id`) REFERENCES `forums` (`id`),
  ADD CONSTRAINT `sujets_forum_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `taches`
--
ALTER TABLE `taches`
  ADD CONSTRAINT `taches_ibfk_1` FOREIGN KEY (`projet_id`) REFERENCES `projets` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`filiere_id`) REFERENCES `filieres` (`id`);

--
-- Constraints for table `user_filieres`
--
ALTER TABLE `user_filieres`
  ADD CONSTRAINT `user_filieres_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `user_filieres_ibfk_2` FOREIGN KEY (`filiere_id`) REFERENCES `filieres` (`id`);

--
-- Constraints for table `user_matieres`
--
ALTER TABLE `user_matieres`
  ADD CONSTRAINT `user_matieres_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_matieres_ibfk_2` FOREIGN KEY (`matiere_id`) REFERENCES `matieres` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
