-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 21, 2024 at 11:22 PM
-- Server version: 8.0.31
-- PHP Version: 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `soundbro_empty_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `basicinfo`
--

DROP TABLE IF EXISTS `basicinfo`;
CREATE TABLE IF NOT EXISTS `basicinfo` (
  `StartDate` date NOT NULL,
  `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `IsPublished` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`StartDate`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `examaccomps`
--

DROP TABLE IF EXISTS `examaccomps`;
CREATE TABLE IF NOT EXISTS `examaccomps` (
  `AccompID` int NOT NULL,
  `School` tinyint(1) NOT NULL,
  `Start0900` tinyint(1) NOT NULL,
  `Start0930` tinyint(1) NOT NULL,
  `Start1000` tinyint(1) NOT NULL,
  `Start1030` tinyint(1) NOT NULL,
  `Start1100` tinyint(1) NOT NULL,
  `Start1130` tinyint(1) NOT NULL,
  `Start1200` tinyint(1) NOT NULL,
  `Start1230` tinyint(1) NOT NULL,
  `Start1300` tinyint(1) NOT NULL,
  `Start1330` tinyint(1) NOT NULL,
  `Start1400` tinyint(1) NOT NULL,
  `Start1430` tinyint(1) NOT NULL,
  `Start1500` tinyint(1) NOT NULL,
  `Start1530` tinyint(1) NOT NULL,
  `Start1600` tinyint(1) NOT NULL,
  `Start1630` tinyint(1) NOT NULL,
  PRIMARY KEY (`AccompID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `examinees`
--

DROP TABLE IF EXISTS `examinees`;
CREATE TABLE IF NOT EXISTS `examinees` (
  `ExamineeID` int NOT NULL,
  `Instrument` varchar(191) NOT NULL,
  `Grade` int NOT NULL,
  `SupervisorID` int NOT NULL,
  `AccompID` int DEFAULT NULL,
  PRIMARY KEY (`ExamineeID`),
  KEY `examinees_fk_2` (`Instrument`),
  KEY `examinees_fk_3` (`Grade`),
  KEY `examinees_fk_4` (`SupervisorID`),
  KEY `examinees_fk_5` (`AccompID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `families`
--

DROP TABLE IF EXISTS `families`;
CREATE TABLE IF NOT EXISTS `families` (
  `Instrument` varchar(191) NOT NULL,
  `InstrumentFamily` varchar(191) NOT NULL,
  PRIMARY KEY (`Instrument`),
  KEY `families_fk_1` (`InstrumentFamily`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `families`
--

INSERT INTO `families` (`Instrument`, `InstrumentFamily`) VALUES
('piano', 'piano'),
('drum kit', 'drum kit and percussion'),
('tuned percussion', 'drum kit and percussion'),
('snare drum', 'drum kit and percussion'),
('timpani', 'drum kit and percussion'),
('orchestral percussion', 'drum kit and percussion'),
('pedal harp', 'harp'),
('non-pedal harp', 'harp'),
('organ', 'organ'),
('rock and pop bass', 'rock and pop exams'),
('rock and pop drums', 'rock and pop exams'),
('rock and pop guitar', 'rock and pop exams'),
('rock and pop keyboards', 'rock and pop exams'),
('rock and pop vocals', 'rock and pop exams'),
('electronic keyboard', 'electronic keyboard'),
('french horn', 'brass'),
('eb tenor horn', 'brass'),
('trumpet', 'brass'),
('cornet', 'brass'),
('flugelhorn', 'brass'),
('eb soprano cornet', 'brass'),
('euphonium', 'brass'),
('baritone', 'brass'),
('trombone', 'brass'),
('bass trombone', 'brass'),
('tuba', 'brass'),
('eb bass', 'brass'),
('bb bass', 'brass'),
('acoustic guitar', 'guitar'),
('classical guitar', 'guitar'),
('violin', 'strings'),
('viola', 'strings'),
('cello', 'strings'),
('double bass', 'strings'),
('scottish traditional fiddle', 'strings'),
('singing', 'singing'),
('flute', 'woodwind'),
('clarinet', 'woodwind'),
('oboe', 'woodwind'),
('bassoon', 'woodwind'),
('saxophone', 'woodwind'),
('recorder', 'woodwind'),
('jazz flute', 'woodwind'),
('jazz clarinet', 'woodwind'),
('jazz saxophone', 'woodwind'),
('accordion', 'woodwind');

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

DROP TABLE IF EXISTS `schedule`;
CREATE TABLE IF NOT EXISTS `schedule` (
  `ScheduleID` int NOT NULL AUTO_INCREMENT,
  `ExamineeID` int NOT NULL,
  `StartTime` time NOT NULL,
  PRIMARY KEY (`ScheduleID`),
  KEY `schedule_fk_1` (`ExamineeID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
CREATE TABLE IF NOT EXISTS `students` (
  `StudentID` int NOT NULL AUTO_INCREMENT,
  `FirstName` varchar(191) NOT NULL,
  `LastName` varchar(191) NOT NULL,
  `Email` varchar(191) NOT NULL,
  `Password` varchar(191) NOT NULL,
  `ParentEmail` varchar(191) NOT NULL,
  PRIMARY KEY (`StudentID`),
  UNIQUE KEY `student_email_unique` (`Email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supervisors`
--

DROP TABLE IF EXISTS `supervisors`;
CREATE TABLE IF NOT EXISTS `supervisors` (
  `SupervisorID` int NOT NULL AUTO_INCREMENT,
  `FirstName` varchar(191) NOT NULL,
  `LastName` varchar(191) NOT NULL,
  `Email` varchar(191) NOT NULL,
  `Password` varchar(191) NOT NULL,
  PRIMARY KEY (`SupervisorID`),
  UNIQUE KEY `supervisor_email_unique` (`Email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timings`
--

DROP TABLE IF EXISTS `timings`;
CREATE TABLE IF NOT EXISTS `timings` (
  `Grade` int NOT NULL,
  `InstrumentFamily` varchar(191) NOT NULL,
  `Duration` int NOT NULL,
  PRIMARY KEY (`InstrumentFamily`,`Grade`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `timings`
--

INSERT INTO `timings` (`Grade`, `InstrumentFamily`, `Duration`) VALUES
(1, 'piano', 11),
(2, 'piano', 11),
(3, 'piano', 12),
(4, 'piano', 16),
(5, 'piano', 16),
(6, 'piano', 22),
(7, 'piano', 22),
(8, 'piano', 27),
(1, 'drum kit and percussion', 15),
(2, 'drum kit and percussion', 15),
(3, 'drum kit and percussion', 16),
(4, 'drum kit and percussion', 21),
(5, 'drum kit and percussion', 21),
(6, 'drum kit and percussion', 27),
(7, 'drum kit and percussion', 27),
(8, 'drum kit and percussion', 32),
(1, 'harp', 13),
(2, 'harp', 15),
(3, 'harp', 15),
(4, 'harp', 20),
(5, 'harp', 20),
(6, 'harp', 25),
(7, 'harp', 25),
(8, 'harp', 30),
(1, 'organ', 13),
(2, 'organ', 15),
(3, 'organ', 15),
(4, 'organ', 20),
(5, 'organ', 20),
(6, 'organ', 25),
(7, 'organ', 25),
(8, 'organ', 30),
(1, 'rock and pop exams', 13),
(2, 'rock and pop exams', 15),
(3, 'rock and pop exams', 15),
(4, 'rock and pop exams', 20),
(5, 'rock and pop exams', 20),
(6, 'rock and pop exams', 25),
(7, 'rock and pop exams', 25),
(8, 'rock and pop exams', 30),
(1, 'electronic keyboard', 13),
(2, 'electronic keyboard', 15),
(3, 'electronic keyboard', 15),
(4, 'electronic keyboard', 20),
(5, 'electronic keyboard', 20),
(6, 'electronic keyboard', 25),
(7, 'electronic keyboard', 25),
(8, 'electronic keyboard', 30),
(1, 'brass', 13),
(2, 'brass', 13),
(3, 'brass', 13),
(4, 'brass', 18),
(5, 'brass', 18),
(6, 'brass', 23),
(7, 'brass', 23),
(8, 'brass', 28),
(1, 'guitar', 13),
(2, 'guitar', 13),
(3, 'guitar', 13),
(4, 'guitar', 18),
(5, 'guitar', 18),
(6, 'guitar', 23),
(7, 'guitar', 23),
(8, 'guitar', 28),
(1, 'strings', 13),
(2, 'strings', 13),
(3, 'strings', 13),
(4, 'strings', 18),
(5, 'strings', 18),
(6, 'strings', 23),
(7, 'strings', 23),
(8, 'strings', 28),
(1, 'singing', 13),
(2, 'singing', 13),
(3, 'singing', 13),
(4, 'singing', 18),
(5, 'singing', 18),
(6, 'singing', 23),
(7, 'singing', 23),
(8, 'singing', 28),
(1, 'woodwind', 13),
(2, 'woodwind', 13),
(3, 'woodwind', 13),
(4, 'woodwind', 18),
(5, 'woodwind', 18),
(6, 'woodwind', 23),
(7, 'woodwind', 23),
(8, 'woodwind', 28);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
