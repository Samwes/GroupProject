-- phpMyAdmin SQL Dump
-- version 4.6.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 23, 2016 at 06:48 PM
-- Server version: 5.7.14
-- PHP Version: 5.6.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gpmain`
--

-- --------------------------------------------------------

--
-- Table structure for table `authtable`
--

CREATE TABLE `authtable` (
  `userid` int(11) NOT NULL,
  `authToken` text NOT NULL,
  `expirDate` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `chattable`
--

CREATE TABLE `chattable` (
  `user1` int(11) NOT NULL,
  `user2` int(11) NOT NULL,
  `user1seen` timestamp NULL DEFAULT NULL,
  `user2seen` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `itemtable`
--

CREATE TABLE `itemtable` (
  `foodid` int(11) NOT NULL,
  `expirydate` date NOT NULL,
  `category` text NOT NULL,
  `userid` int(11) NOT NULL,
  `name` text NOT NULL,
  `description` text NOT NULL,
  `latit` decimal(12,10) NOT NULL,
  `longit`  decimal(12,10) NOT NULL,
  `amount` int(11) NOT NULL,
  `weight` float NOT NULL,
  `image` text NOT NULL,
  `active` tinyint(1) NOT NULL,
  `hidden` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `messagetable`
--

CREATE TABLE `messagetable` (
  `messageid` int(11) NOT NULL,
  `message` text NOT NULL,
  `time` timestamp NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `requestmessagetable`
--

CREATE TABLE `requestmessagetable` (
  `messageid` int(11) NOT NULL,
  `sender` int(11) NOT NULL,
  `requestid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `requesttable`
--

CREATE TABLE `requesttable` (
  `requestid` int(11) NOT NULL,
  `requester` int(11) NOT NULL,
  `foodid` int(11) NOT NULL,
  `accepted` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `usermessagetable`
--

CREATE TABLE `usermessagetable` (
  `messageid` int(11) NOT NULL,
  `sender` int(11) NOT NULL,
  `receiver` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `usertable`
--

CREATE TABLE `usertable` (
  `userid` int(11) NOT NULL,
  `username` text NOT NULL,
  `password` text NOT NULL,
  `salt` char(22) NOT NULL,  
  `picture` text,
  `email` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `authtable`
--
ALTER TABLE `authtable`
  ADD PRIMARY KEY (`userid`);

--
-- Indexes for table `chattable`
--
ALTER TABLE `chattable`
  ADD PRIMARY KEY (`user1`,`user2`),
  ADD KEY `user2` (`user2`);

--
-- Indexes for table `itemtable`
--
ALTER TABLE `itemtable`
  ADD PRIMARY KEY (`foodid`),
  ADD KEY `userid` (`userid`);

--
-- Indexes for table `messagetable`
--
ALTER TABLE `messagetable`
  ADD PRIMARY KEY (`messageid`);

--
-- Indexes for table `requestmessagetable`
--
ALTER TABLE `requestmessagetable`
  ADD PRIMARY KEY (`messageid`),
  ADD KEY `sender` (`sender`),
  ADD KEY `requestid` (`requestid`);

--
-- Indexes for table `requesttable`
--
ALTER TABLE `requesttable`
  ADD PRIMARY KEY (`requestid`),
  ADD KEY `requester` (`requester`),
  ADD KEY `foodid` (`foodid`);

--
-- Indexes for table `usermessagetable`
--
ALTER TABLE `usermessagetable`
  ADD PRIMARY KEY (`messageid`),
  ADD KEY `sender` (`sender`),
  ADD KEY `receiver` (`receiver`);

--
-- Indexes for table `usertable`
--
ALTER TABLE `usertable`
  ADD PRIMARY KEY (`userid`);

--
-- AUTO_INCREMENT for dumped tables
--

ALTER TABLE `usertable`
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT;
  
ALTER TABLE `messagetable`
  MODIFY `messageid` int(11) NOT NULL AUTO_INCREMENT;
  
ALTER TABLE `requesttable`
  MODIFY `requestid` int(11) NOT NULL AUTO_INCREMENT;
  
ALTER TABLE `itemtable`
  MODIFY `foodid` int(11) NOT NULL AUTO_INCREMENT;

-- Constraints for dumped tables
--

--
-- Constraints for table `authtable`
--
ALTER TABLE `authtable`
  ADD CONSTRAINT `authtable_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `usertable` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `chattable`
--
ALTER TABLE `chattable`
  ADD CONSTRAINT `chattable_ibfk_1` FOREIGN KEY (`user1`) REFERENCES `usertable` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `chattable_ibfk_2` FOREIGN KEY (`user2`) REFERENCES `usertable` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `itemtable`
--
ALTER TABLE `itemtable`
  ADD CONSTRAINT `itemtable_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `usertable` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `requestmessagetable`
--
ALTER TABLE `requestmessagetable`
  ADD CONSTRAINT `requestmessagetable_ibfk_1` FOREIGN KEY (`messageid`) REFERENCES `messagetable` (`messageid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `requestmessagetable_ibfk_2` FOREIGN KEY (`sender`) REFERENCES `usertable` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `requestmessagetable_ibfk_3` FOREIGN KEY (`requestid`) REFERENCES `requesttable` (`requestid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `requesttable`
--
ALTER TABLE `requesttable`
  ADD CONSTRAINT `requesttable_ibfk_1` FOREIGN KEY (`requester`) REFERENCES `usertable` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `requesttable_ibfk_2` FOREIGN KEY (`foodid`) REFERENCES `itemtable` (`foodid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `usermessagetable`
--
ALTER TABLE `usermessagetable`
  ADD CONSTRAINT `usermessagetable_ibfk_1` FOREIGN KEY (`messageid`) REFERENCES `messagetable` (`messageid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `usermessagetable_ibfk_2` FOREIGN KEY (`sender`) REFERENCES `usertable` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `usermessagetable_ibfk_3` FOREIGN KEY (`receiver`) REFERENCES `usertable` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
