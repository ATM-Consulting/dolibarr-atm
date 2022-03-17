-- phpMyAdmin SQL Dump
-- version 4.6.6deb4+deb9u2
-- https://www.phpmyadmin.net/
--
-- Client :  localhost:3306
-- Généré le :  Ven 30 Juillet 2021 à 09:52
-- Version du serveur :  10.2.33-MariaDB-10.2.33+maria~stretch
-- Version de PHP :  5.6.40-30+0~20200807.36+debian9~1.gbp3a37a8

# SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
# SET time_zone = "+00:00";
#
#
# /*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
# /*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
# /*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
# /*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `legalcategory`
--

-- --------------------------------------------------------

--
-- Structure de la table `llx_c_staff`
--

CREATE TABLE `llx_c_sirene_staff` (
  `code_sirene_staff` varchar(4) DEFAULT NULL,
  `label_sirene_staff` varchar(250) DEFAULT NULL,
  `code_dolibarr_staff` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Contenu de la table `llx_c_staff`
--

INSERT INTO `llx_c_sirene_staff` (`code_sirene_staff`, `label_sirene_staff`, `code_dolibarr_staff`) VALUES
('NN', '-', '0'),
('0', '0', 'EF0'),
('1', '1 – 5', 'EF1-5'),
('2', '1 – 5', 'EF1-5'),
('3', '6 – 10', 'EF6-10'),
('11', '11 – 50', 'EF11-50'),
('12', '11 - 50 ', 'EF11-50'),
('21', '51 – 100', 'EF51-100'),
('22', '100 – 500', 'EF100-500'),
('31', '100 – 500', 'EF100-500'),
('32', '100 – 500', 'EF100-500'),
('41', '> 500', 'EF500-'),
('42', '> 500', 'EF500-'),
('51', '> 500', 'EF500-'),
('52', '> 500', 'EF500-'),
('53', '> 500', 'EF500-');

