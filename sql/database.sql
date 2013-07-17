-- phpMyAdmin SQL Dump
-- version 3.4.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 17, 2013 at 02:35 PM
-- Server version: 5.1.67
-- PHP Version: 5.3.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `DATABASE_NAME`
--

-- --------------------------------------------------------

--
-- Table structure for table `User`
--

-- DROP TABLE IF EXISTS `User`;
CREATE TABLE IF NOT EXISTS `User` (
  `UserID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `Name` varchar(80) COLLATE utf8_bin NOT NULL,
  `Email` varchar(255) COLLATE utf8_bin NOT NULL,
  `Password` varchar(255) COLLATE utf8_bin NOT NULL,
  `Company` varchar(255) COLLATE utf8_bin NOT NULL,
  `UserType` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `OfficePhone` varchar(18) COLLATE utf8_bin NOT NULL,
  `CellPhone` varchar(16) COLLATE utf8_bin NOT NULL,
  `Skype` varchar(40) COLLATE utf8_bin NOT NULL,
  `FacebookId` varchar(40) COLLATE utf8_bin NOT NULL,
  `FacebookToken`` varchar(255) COLLATE utf8_bin NOT NULL,
  `StartStamp` bigint(20) unsigned NOT NULL,
  `LastStamp` bigint(20) unsigned NOT NULL,
  `SignupIP` varchar(25) COLLATE utf8_bin NOT NULL,
  `ConfCode` varchar(12) COLLATE utf8_bin NOT NULL,
  `ConfStamp` bigint(20) unsigned NOT NULL,
  `AccountLevel` tinyint(4) NOT NULL DEFAULT '0',
  `ContactPrefs` bigint(20) unsigned NOT NULL,
  `Active` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `Email` (`Email`),
  KEY `Active` (`Active`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
