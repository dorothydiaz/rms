-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: rms_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `hr_applicants`
--

DROP TABLE IF EXISTS `hr_applicants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_applicants` (
  `applicant_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_vacancy_id` bigint(20) unsigned DEFAULT NULL,
  `first_name` varchar(60) NOT NULL,
  `middle_name` varchar(60) DEFAULT NULL,
  `last_name` varchar(60) NOT NULL,
  `suffix` varchar(15) DEFAULT NULL,
  `preferred_name` varchar(60) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `civil_status` varchar(30) DEFAULT NULL,
  `nationality` varchar(50) NOT NULL DEFAULT 'Filipino',
  `contact_number` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `resume_path` varchar(255) DEFAULT NULL,
  `applied_position` varchar(100) NOT NULL,
  `desired_salary` decimal(12,2) DEFAULT NULL,
  `employment_type` varchar(50) DEFAULT NULL,
  `available_start_date` date DEFAULT NULL,
  `years_of_experience` varchar(50) DEFAULT NULL,
  `work_setup_preference` varchar(50) DEFAULT NULL,
  `source` varchar(50) NOT NULL DEFAULT 'Walk-in',
  `recruiter_id` bigint(20) unsigned DEFAULT NULL,
  `recruiter_name` varchar(100) DEFAULT NULL,
  `hiring_manager_id` bigint(20) unsigned DEFAULT NULL,
  `hiring_manager_name` varchar(100) DEFAULT NULL,
  `education` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`education`)),
  `work_experience` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`work_experience`)),
  `skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`skills`)),
  `certifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`certifications`)),
  `languages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`languages`)),
  `application_answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`application_answers`)),
  `documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`documents`)),
  `privacy_consent` tinyint(1) NOT NULL DEFAULT 1,
  `data_processing_consent` tinyint(1) NOT NULL DEFAULT 1,
  `applicant_declaration` tinyint(1) NOT NULL DEFAULT 1,
  `screening_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`screening_data`)),
  `final_review_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`final_review_data`)),
  `offer_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`offer_data`)),
  `pre_employment_requirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pre_employment_requirements`)),
  `preboarding_tasks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preboarding_tasks`)),
  `onboarding_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`onboarding_data`)),
  `activity_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`activity_history`)),
  `application_date` date NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'New',
  `hired_as_employee_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`applicant_id`),
  KEY `applicants_job_vacancy_id_foreign` (`job_vacancy_id`),
  KEY `applicants_hired_as_employee_id_foreign` (`hired_as_employee_id`),
  CONSTRAINT `applicants_hired_as_employee_id_foreign` FOREIGN KEY (`hired_as_employee_id`) REFERENCES `hr_employees` (`employee_id`) ON DELETE SET NULL,
  CONSTRAINT `applicants_job_vacancy_id_foreign` FOREIGN KEY (`job_vacancy_id`) REFERENCES `hr_job_vacancies` (`job_vacancy_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_applicants`
--

LOCK TABLES `hr_applicants` WRITE;
/*!40000 ALTER TABLE `hr_applicants` DISABLE KEYS */;
INSERT INTO `hr_applicants` VALUES (1,1,'Miguel',NULL,'Torres',NULL,NULL,NULL,NULL,NULL,'Filipino','09175551234','miguel.torres@email.com','Signal Village, Taguig City',NULL,NULL,NULL,'Line Cook / Griller',NULL,NULL,NULL,NULL,NULL,'Walk-in',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-15','Hired',602,'2026-09-23 10:41:27','2026-09-30 19:16:45');
/*!40000 ALTER TABLE `hr_applicants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_assessments`
--

DROP TABLE IF EXISTS `hr_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_assessments` (
  `assessment_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `applicant_id` bigint(20) unsigned NOT NULL,
  `assessment_type` varchar(60) NOT NULL,
  `title` varchar(150) NOT NULL,
  `assessment_date` date DEFAULT NULL,
  `score` decimal(6,2) DEFAULT NULL,
  `passing_score` decimal(6,2) DEFAULT NULL,
  `result` varchar(30) NOT NULL DEFAULT 'Pending',
  `evaluator_id` bigint(20) unsigned DEFAULT NULL,
  `evaluator_name` varchar(100) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`assessment_id`),
  KEY `hr_assessments_applicant_id_foreign` (`applicant_id`),
  CONSTRAINT `hr_assessments_applicant_id_foreign` FOREIGN KEY (`applicant_id`) REFERENCES `hr_applicants` (`applicant_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_assessments`
--

LOCK TABLES `hr_assessments` WRITE;
/*!40000 ALTER TABLE `hr_assessments` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr_assessments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_attendance_action_logs`
--

DROP TABLE IF EXISTS `hr_attendance_action_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_attendance_action_logs` (
  `attendance_action_log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attendance_record_id` bigint(20) unsigned DEFAULT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `action_type` varchar(60) NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `notes` varchar(255) DEFAULT NULL,
  `action_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`attendance_action_log_id`),
  KEY `hr_attendance_action_logs_attendance_record_id_foreign` (`attendance_record_id`),
  KEY `hr_attendance_action_logs_employee_id_foreign` (`employee_id`),
  KEY `hr_attendance_action_logs_action_by_foreign` (`action_by`),
  CONSTRAINT `hr_attendance_action_logs_action_by_foreign` FOREIGN KEY (`action_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `hr_attendance_action_logs_attendance_record_id_foreign` FOREIGN KEY (`attendance_record_id`) REFERENCES `hr_attendance_records` (`attendance_record_id`) ON DELETE CASCADE,
  CONSTRAINT `hr_attendance_action_logs_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`employee_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_attendance_action_logs`
--

LOCK TABLES `hr_attendance_action_logs` WRITE;
/*!40000 ALTER TABLE `hr_attendance_action_logs` DISABLE KEYS */;
INSERT INTO `hr_attendance_action_logs` VALUES (1,6,1,'overtime_rejected','{\"date\":\"2026-09-21\",\"overtime_hours\":\"2.00\",\"previous_status\":\"Approved\",\"new_status\":\"Rejected\",\"remarks\":null}','Overtime of 2.00 hrs on 2026-09-21 rejected by System Administrator',1,'2026-10-01 09:45:22','2026-10-01 09:45:22'),(2,168,8,'undertime_authorized','{\"date\":\"2026-09-28\",\"undertime_minutes\":35,\"previous_status\":\"Pending\",\"new_status\":\"Approved\",\"remarks\":null}','Undertime of 35 mins on 2026-09-28 authorized by System Administrator',1,'2026-10-01 09:55:46','2026-10-01 09:55:46');
/*!40000 ALTER TABLE `hr_attendance_action_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_attendance_corrections`
--

DROP TABLE IF EXISTS `hr_attendance_corrections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_attendance_corrections` (
  `attendance_correction_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attendance_record_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `requested_by` bigint(20) unsigned NOT NULL,
  `original_time_in` time DEFAULT NULL,
  `original_time_out` time DEFAULT NULL,
  `requested_time_in` time DEFAULT NULL,
  `requested_time_out` time DEFAULT NULL,
  `reason` text NOT NULL,
  `supporting_document` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewer_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`attendance_correction_id`),
  KEY `attendance_corrections_attendance_record_id_foreign` (`attendance_record_id`),
  KEY `attendance_corrections_employee_id_foreign` (`employee_id`),
  KEY `attendance_corrections_requested_by_foreign` (`requested_by`),
  KEY `attendance_corrections_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `attendance_corrections_attendance_record_id_foreign` FOREIGN KEY (`attendance_record_id`) REFERENCES `hr_attendance_records` (`attendance_record_id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_corrections_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`employee_id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_corrections_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_corrections_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=140 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_attendance_corrections`
--

LOCK TABLES `hr_attendance_corrections` WRITE;
/*!40000 ALTER TABLE `hr_attendance_corrections` DISABLE KEYS */;
INSERT INTO `hr_attendance_corrections` VALUES (1,16,2,7,'14:00:00','23:00:00','13:40:00','23:00:00','Biometric machine scanner delayed clock-in during peak lunch prep.',NULL,'Pending',NULL,NULL,NULL,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(2,97,65,7,'14:00:00','23:00:00','13:40:00','23:00:00','Biometric machine scanner delayed clock-in during peak lunch prep.',NULL,'Pending',NULL,NULL,NULL,'2026-09-24 09:37:11','2026-09-24 09:37:11');
/*!40000 ALTER TABLE `hr_attendance_corrections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_attendance_records`
--

DROP TABLE IF EXISTS `hr_attendance_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_attendance_records` (
  `attendance_record_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `schedule_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `in_1` time DEFAULT NULL,
  `out_1` time DEFAULT NULL,
  `in_2` time DEFAULT NULL,
  `out_2` time DEFAULT NULL,
  `in_3` time DEFAULT NULL,
  `out_3` time DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `break_out` time DEFAULT NULL,
  `break_in` time DEFAULT NULL,
  `coffee_break_out` time DEFAULT NULL,
  `coffee_break_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `total_hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `regular_hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `late_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `undertime_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `undertime_status` varchar(25) NOT NULL DEFAULT 'Pending',
  `undertime_approved_by` bigint(20) unsigned DEFAULT NULL,
  `undertime_approved_at` timestamp NULL DEFAULT NULL,
  `undertime_remarks` varchar(255) DEFAULT NULL,
  `overtime_hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `overtime_status` varchar(25) NOT NULL DEFAULT 'Pending',
  `overtime_approved_by` bigint(20) unsigned DEFAULT NULL,
  `overtime_approved_at` timestamp NULL DEFAULT NULL,
  `overtime_remarks` varchar(255) DEFAULT NULL,
  `absence_days` decimal(4,2) NOT NULL DEFAULT 0.00,
  `night_diff_hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `holiday_type` enum('None','Regular','SpecialNonWorking') NOT NULL DEFAULT 'None',
  `is_rest_day` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Present','Absent','Late','Half Day','On Leave','Rest Day','Holiday') NOT NULL DEFAULT 'Present',
  `dtr_remarks` varchar(50) DEFAULT NULL,
  `source` enum('Web','Manual','Biometric') NOT NULL DEFAULT 'Web',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`attendance_record_id`),
  UNIQUE KEY `attendance_records_employee_id_date_unique` (`employee_id`,`date`),
  KEY `attendance_records_schedule_id_foreign` (`schedule_id`),
  KEY `attendance_records_branch_id_foreign` (`branch_id`),
  CONSTRAINT `attendance_records_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `hr_branches` (`branch_id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_records_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`employee_id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_records_schedule_id_foreign` FOREIGN KEY (`schedule_id`) REFERENCES `hr_employee_schedules` (`employee_schedule_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=523 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_attendance_records`
--

LOCK TABLES `hr_attendance_records` WRITE;
/*!40000 ALTER TABLE `hr_attendance_records` DISABLE KEYS */;
INSERT INTO `hr_attendance_records` VALUES (1,1,1,1,'2026-09-16','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(2,1,2,1,'2026-09-17','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(3,1,3,1,'2026-09-18','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(4,1,4,1,'2026-09-19','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(5,1,5,1,'2026-09-20','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(6,1,6,1,'2026-09-21','06:25:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'17:00:00',10.00,8.00,0,0,'Pending',NULL,NULL,NULL,2.00,'Rejected',1,'2026-10-01 09:45:22','Overtime rejected by manager/HR',0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-10-01 09:45:22'),(7,1,7,1,'2026-09-22','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(8,1,8,1,'2026-09-23','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',1,'Rest Day',NULL,'Web','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(9,2,9,1,'2026-09-16','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(10,2,10,1,'2026-09-17','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(11,2,11,1,'2026-09-18','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(12,2,12,1,'2026-09-19','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(13,2,13,1,'2026-09-20','14:00:00','12:00:00','13:00:00','16:15:00','16:45:00','01:00:00','14:00:00','12:00:00','13:00:00','16:15:00','16:45:00','23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(14,2,14,1,'2026-09-21','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(15,2,15,1,'2026-09-22','14:25:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:25:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',7.58,7.58,25,25,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.33,'None',0,'Late',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-10-01 09:52:57'),(16,2,16,1,'2026-09-23','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',1,'Rest Day',NULL,'Web','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(17,3,17,1,'2026-09-16','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(18,3,18,1,'2026-09-17','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(19,3,19,1,'2026-09-18','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(20,3,20,1,'2026-09-19','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(21,3,21,1,'2026-09-20','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(22,3,22,1,'2026-09-21','22:25:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','09:00:00',10.00,8.00,0,0,'Pending',NULL,NULL,NULL,2.00,'Approved',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(23,3,23,1,'2026-09-22','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(24,3,24,1,'2026-09-23','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',1,'Rest Day',NULL,'Web','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(25,4,25,1,'2026-09-16','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(26,4,26,1,'2026-09-17','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(27,4,27,1,'2026-09-18','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(28,4,28,1,'2026-09-19','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(29,4,29,1,'2026-09-20','06:00:00','12:00:00','13:00:00',NULL,NULL,'17:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(30,4,30,1,'2026-09-21','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'17:00:00',10.00,8.00,0,0,'Pending',NULL,NULL,NULL,2.00,'Approved',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(31,4,31,1,'2026-09-22','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(32,4,32,1,'2026-09-23','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',1,'Rest Day',NULL,'Web','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(33,5,33,2,'2026-09-16','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(34,5,34,2,'2026-09-17','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(35,5,35,2,'2026-09-18','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(36,5,36,2,'2026-09-19','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(37,5,37,2,'2026-09-20','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(38,5,38,2,'2026-09-21','14:25:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(39,5,39,2,'2026-09-22','14:25:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:25:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',7.58,7.58,25,25,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.33,'None',0,'Late',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-10-01 09:52:57'),(40,5,40,2,'2026-09-23','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',1,'Rest Day',NULL,'Web','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(41,6,41,2,'2026-09-16','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(42,6,42,2,'2026-09-17','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(43,6,43,2,'2026-09-18','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(44,6,44,2,'2026-09-19','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(45,6,45,2,'2026-09-20','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','09:00:00','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(46,6,46,2,'2026-09-21','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','09:00:00',10.00,8.00,0,0,'Pending',NULL,NULL,NULL,2.00,'Approved',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(47,6,47,2,'2026-09-22','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(48,6,48,2,'2026-09-23','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',1,'Rest Day',NULL,'Web','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(49,7,49,2,'2026-09-16','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(50,7,50,2,'2026-09-17','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(51,7,51,2,'2026-09-18','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(52,7,52,2,'2026-09-19','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(53,7,53,2,'2026-09-20','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(54,7,54,2,'2026-09-21','06:25:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(55,7,55,2,'2026-09-22','06:25:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:25:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',7.58,7.58,25,25,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Late',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-10-01 09:52:57'),(56,7,56,2,'2026-09-23','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',1,'Rest Day',NULL,'Web','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(57,8,57,3,'2026-09-16','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(58,8,58,3,'2026-09-17','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(59,8,59,3,'2026-09-18','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(60,8,60,3,'2026-09-19','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(61,8,61,3,'2026-09-20','14:00:00','12:00:00','13:00:00','16:15:00','16:45:00','01:00:00','14:00:00','12:00:00','13:00:00','16:15:00','16:45:00','23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(62,8,62,3,'2026-09-21','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'01:00:00',10.00,8.00,0,0,'Pending',NULL,NULL,NULL,2.00,'Approved',NULL,NULL,NULL,0.00,2.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(63,8,63,3,'2026-09-22','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(64,8,64,3,'2026-09-23','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',1,'Rest Day',NULL,'Web','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(65,9,65,3,'2026-09-16','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(66,9,66,3,'2026-09-17','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(67,9,67,3,'2026-09-18','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(68,9,68,3,'2026-09-19','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(69,9,69,3,'2026-09-20','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(70,9,70,3,'2026-09-21','22:25:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(71,9,71,3,'2026-09-22','22:25:00','12:00:00','13:00:00',NULL,NULL,'07:00:00','22:25:00','12:00:00','13:00:00',NULL,NULL,'07:00:00',7.58,7.58,25,25,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.25,'None',0,'Late',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-10-01 09:52:57'),(72,9,72,3,'2026-09-23','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00','22:00:00','12:00:00','13:00:00','16:15:00','16:45:00','07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',1,'Rest Day',NULL,'Web','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(73,10,73,3,'2026-09-16','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(74,10,74,3,'2026-09-17','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(75,10,75,3,'2026-09-18','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(76,10,76,3,'2026-09-19','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-23 22:07:22'),(77,10,77,3,'2026-09-20','06:00:00','12:00:00','13:00:00',NULL,NULL,'17:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(78,10,78,3,'2026-09-21','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'17:00:00',10.00,8.00,0,0,'Pending',NULL,NULL,NULL,2.00,'Approved',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(79,10,79,3,'2026-09-22','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(80,10,80,3,'2026-09-23','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',1,'Rest Day',NULL,'Web','Logged via biometric station','2026-09-23 10:41:27','2026-09-24 09:37:11'),(81,1,NULL,1,'2026-10-15','08:00:00','12:00:00','13:00:00','15:30:00','15:45:00','17:00:00','08:00:00','12:00:00','13:00:00','15:30:00','15:45:00','17:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Manual',NULL,'2026-09-23 23:43:45','2026-09-23 23:43:45'),(82,64,88,1,'2026-09-17','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(83,64,89,1,'2026-09-18','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(84,64,90,1,'2026-09-19','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(85,64,91,1,'2026-09-20','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(86,64,92,1,'2026-09-21','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(87,64,93,1,'2026-09-22','06:25:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:25:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',7.58,7.58,25,25,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Late',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-10-01 09:52:57'),(88,64,94,1,'2026-09-23',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',1,'Rest Day',NULL,'Web',NULL,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(89,64,95,1,'2026-09-24','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(90,65,96,1,'2026-09-17','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(91,65,97,1,'2026-09-18','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(92,65,98,1,'2026-09-19','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(93,65,99,1,'2026-09-20','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(94,65,100,1,'2026-09-21','14:00:00','12:00:00','13:00:00',NULL,NULL,'01:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'01:00:00',10.00,8.00,0,0,'Pending',NULL,NULL,NULL,2.00,'Approved',NULL,NULL,NULL,0.00,2.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(95,65,101,1,'2026-09-22','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(96,65,102,1,'2026-09-23',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',1,'Rest Day',NULL,'Web',NULL,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(97,65,103,1,'2026-09-24','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(98,66,104,1,'2026-09-17','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(99,66,105,1,'2026-09-18','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(100,66,106,1,'2026-09-19','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(101,66,107,1,'2026-09-20','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(102,66,108,1,'2026-09-21','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(103,66,109,1,'2026-09-22','22:25:00','12:00:00','13:00:00',NULL,NULL,'07:00:00','22:25:00','12:00:00','13:00:00',NULL,NULL,'07:00:00',7.58,7.58,25,25,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.25,'None',0,'Late',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-10-01 09:52:57'),(104,66,110,1,'2026-09-23',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',1,'Rest Day',NULL,'Web',NULL,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(105,66,111,1,'2026-09-24','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(106,1,81,1,'2026-09-24','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(107,2,112,1,'2026-09-24','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(108,3,113,1,'2026-09-24','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(109,67,114,1,'2026-09-17','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(110,67,115,1,'2026-09-18','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(111,67,116,1,'2026-09-19','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(112,67,117,1,'2026-09-20','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(113,67,118,1,'2026-09-21','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(114,67,119,1,'2026-09-22','06:25:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:25:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',7.58,7.58,25,25,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Late',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-10-01 09:52:57'),(115,67,120,1,'2026-09-23',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',1,'Rest Day',NULL,'Web',NULL,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(116,67,121,1,'2026-09-24','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(117,68,122,1,'2026-09-17','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(118,68,123,1,'2026-09-18','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(119,68,124,1,'2026-09-19','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(120,68,125,1,'2026-09-20','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(121,68,126,1,'2026-09-21','14:00:00','12:00:00','13:00:00',NULL,NULL,'01:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'01:00:00',10.00,8.00,0,0,'Pending',NULL,NULL,NULL,2.00,'Approved',NULL,NULL,NULL,0.00,2.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(122,68,127,1,'2026-09-22','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(123,68,128,1,'2026-09-23',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',1,'Rest Day',NULL,'Web',NULL,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(124,68,129,1,'2026-09-24','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(125,69,130,1,'2026-09-17','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(126,69,131,1,'2026-09-18','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(127,69,132,1,'2026-09-19','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(128,69,133,1,'2026-09-20','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(129,69,134,1,'2026-09-21','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(130,69,135,1,'2026-09-22','22:25:00','12:00:00','13:00:00',NULL,NULL,'07:00:00','22:25:00','12:00:00','13:00:00',NULL,NULL,'07:00:00',7.58,7.58,25,25,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.25,'None',0,'Late',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-10-01 09:52:57'),(131,69,136,1,'2026-09-23',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',1,'Rest Day',NULL,'Web',NULL,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(132,69,137,1,'2026-09-24','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(133,4,138,1,'2026-09-24','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(134,5,139,2,'2026-09-24','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(135,6,140,2,'2026-09-24','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(136,7,141,2,'2026-09-24','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(137,8,142,3,'2026-09-24','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00','14:00:00','12:00:00','13:00:00',NULL,NULL,'23:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(138,9,143,3,'2026-09-24','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00','22:00:00','12:00:00','13:00:00',NULL,NULL,'07:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,7.50,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(139,10,144,3,'2026-09-24','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00','06:00:00','12:00:00','13:00:00',NULL,NULL,'15:00:00',8.00,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric','Logged via biometric station','2026-09-24 09:37:11','2026-09-24 09:37:11'),(164,5,NULL,2,'2026-09-29','20:45:00',NULL,NULL,NULL,NULL,NULL,'20:45:00',NULL,NULL,NULL,NULL,NULL,0.00,0.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Manual',NULL,'2026-09-29 04:45:40','2026-09-29 04:45:40'),(165,5,NULL,2,'2026-09-28','11:56:00',NULL,NULL,NULL,NULL,'21:00:00','11:56:00',NULL,NULL,NULL,NULL,'21:00:00',8.07,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric',NULL,'2026-09-29 06:41:22','2026-09-29 06:41:22'),(166,1,NULL,1,'2026-09-28','10:59:00',NULL,NULL,NULL,NULL,'20:03:00','10:59:00',NULL,NULL,NULL,NULL,'20:03:00',8.07,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric',NULL,'2026-09-29 06:41:22','2026-09-29 06:41:22'),(167,64,NULL,1,'2026-09-28','11:59:00',NULL,NULL,NULL,NULL,'21:04:00','11:59:00',NULL,NULL,NULL,NULL,'21:04:00',8.08,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric',NULL,'2026-09-29 06:41:22','2026-09-29 06:41:22'),(168,8,NULL,3,'2026-09-28','12:37:00',NULL,NULL,NULL,NULL,'21:02:00','12:37:00',NULL,NULL,NULL,NULL,'21:02:00',7.42,7.42,37,35,'Approved',1,'2026-10-01 09:55:46',NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Late',NULL,'Biometric',NULL,'2026-09-29 06:41:22','2026-10-01 09:55:46'),(169,66,NULL,1,'2026-09-28','11:55:00',NULL,NULL,NULL,NULL,'21:01:00','11:55:00',NULL,NULL,NULL,NULL,'21:01:00',8.10,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric',NULL,'2026-09-29 06:41:22','2026-09-29 06:41:22'),(170,6,NULL,2,'2026-09-28','11:58:00',NULL,NULL,NULL,NULL,'21:00:00','11:58:00',NULL,NULL,NULL,NULL,'21:00:00',8.03,8.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric',NULL,'2026-09-29 06:41:22','2026-09-29 06:41:22'),(171,67,NULL,1,'2026-09-28',NULL,NULL,NULL,NULL,NULL,'20:01:00',NULL,NULL,NULL,NULL,NULL,'20:01:00',0.00,0.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric',NULL,'2026-09-29 06:42:09','2026-09-29 06:42:09'),(172,1,NULL,1,'2026-09-29',NULL,NULL,NULL,NULL,NULL,'20:01:00',NULL,NULL,NULL,NULL,NULL,'20:01:00',0.00,0.00,0,0,'Pending',NULL,NULL,NULL,0.00,'Pending',NULL,NULL,NULL,0.00,0.00,'None',0,'Present',NULL,'Biometric',NULL,'2026-09-29 06:42:09','2026-09-29 06:42:09'),(403,1,NULL,1,'2026-10-02',NULL,NULL,NULL,NULL,NULL,NULL,'08:00:00',NULL,NULL,NULL,NULL,'19:30:00',10.50,8.00,0,0,'Pending',NULL,NULL,NULL,2.50,'Pending',NULL,NULL,'Kitchen inventory and dinner peak rush coverage',0.00,0.00,'None',0,'Present',NULL,'Web',NULL,'2026-10-01 19:45:25','2026-10-01 19:45:25'),(404,2,NULL,1,'2026-10-01',NULL,NULL,NULL,NULL,NULL,NULL,'09:00:00',NULL,NULL,NULL,NULL,'20:00:00',10.00,8.00,0,0,'Pending',NULL,NULL,NULL,2.00,'Pending',NULL,NULL,'Store closing procedure and deep sanitization',0.00,0.00,'None',0,'Present',NULL,'Web',NULL,'2026-10-01 19:45:25','2026-10-01 19:45:25'),(405,3,NULL,1,'2026-09-30',NULL,NULL,NULL,NULL,NULL,NULL,'07:30:00',NULL,NULL,NULL,NULL,'18:00:00',9.50,8.00,0,0,'Pending',NULL,NULL,NULL,1.50,'Pending',NULL,NULL,'Early breakfast prep and delivery intake',0.00,0.00,'None',0,'Present',NULL,'Web',NULL,'2026-10-01 19:45:25','2026-10-01 19:45:25'),(406,4,NULL,1,'2026-09-29',NULL,NULL,NULL,NULL,NULL,NULL,'10:00:00',NULL,NULL,NULL,NULL,'22:00:00',11.00,8.00,0,0,'Pending',NULL,NULL,NULL,3.00,'Pending',NULL,NULL,'Catering event setup and live station service',0.00,0.00,'None',0,'Present',NULL,'Web',NULL,'2026-10-01 19:45:25','2026-10-01 19:45:25');
/*!40000 ALTER TABLE `hr_attendance_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_branches`
--

DROP TABLE IF EXISTS `hr_branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_branches` (
  `branch_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_name` varchar(100) NOT NULL,
  `branch_code` varchar(30) NOT NULL,
  `address` text DEFAULT NULL,
  `branch_phone_number` varchar(50) DEFAULT NULL,
  `branch_email_address` varchar(100) DEFAULT NULL,
  `branch_is_active` tinyint(1) NOT NULL DEFAULT 1,
  `branch_created_at` timestamp NULL DEFAULT NULL,
  `branch_updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`branch_id`),
  UNIQUE KEY `branches_code_unique` (`branch_code`),
  KEY `branches_company_id_foreign` (`company_id`),
  CONSTRAINT `branches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `hr_companies` (`company_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_branches`
--

LOCK TABLES `hr_branches` WRITE;
/*!40000 ALTER TABLE `hr_branches` DISABLE KEYS */;
INSERT INTO `hr_branches` VALUES (1,1,'Makati Flagship Branch','MAK-01','Greenbelt 5, Ayala Center, Makati City','+63 2 8888 1111','makati@rusticrestaurant.ph',1,'2026-09-23 10:40:49','2026-09-23 10:40:49'),(2,1,'BGC Taguig Branch','BGC-02','Bonifacio High Street Central, BGC, Taguig City','+63 2 8888 2222','bgc@rusticrestaurant.ph',1,'2026-09-23 10:40:49','2026-09-23 10:40:49'),(3,1,'Quezon City Branch','QC-03','Tomas Morato Ave cor Scout Gandia, Quezon City','+63 2 8888 3333','qc@rusticrestaurant.ph',1,'2026-09-23 10:40:49','2026-09-23 10:40:49'),(4,NULL,'Test Branch','TEST_BR',NULL,NULL,NULL,1,'2026-09-30 17:36:31','2026-09-30 17:36:31');
/*!40000 ALTER TABLE `hr_branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_companies`
--

DROP TABLE IF EXISTS `hr_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_companies` (
  `company_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_legal_name` varchar(150) NOT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'Company',
  `company_code` varchar(50) NOT NULL,
  `company_tax_id` varchar(30) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `company_email_address` varchar(100) DEFAULT NULL,
  `company_phone_number` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `company_is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `company_created_at` timestamp NULL DEFAULT NULL,
  `company_updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`company_id`),
  UNIQUE KEY `companies_code_unique` (`company_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_companies`
--

LOCK TABLES `hr_companies` WRITE;
/*!40000 ALTER TABLE `hr_companies` DISABLE KEYS */;
INSERT INTO `hr_companies` VALUES (1,'Bistro Hospitality Group Inc.','Company','BHG-01','123-456-789-000','Dorothy Diaz','admin@rusticrestaurant.ph','+63 2 8123 4567','Ground Floor, Greenbelt 5, Ayala Center, Makati City',NULL,1,NULL,'2026-09-23 10:40:49','2026-09-24 09:37:09'),(2,'ABC Manpower & Staffing Services','Agency','ABC-STAFF','456-789-012-000','Robert Tan (Account Executive)','info@abcmanpower.ph','+63 2 8555 7788','Unit 402, Strata 100, F. Ortigas Jr. Rd, Pasig City',NULL,1,'Contracted manpower partner for kitchen and front-of-house utility personnel.','2026-09-24 07:48:10','2026-09-24 09:37:09'),(3,'Prime Hospitality Care Agency','Agency','PRIME-CARE','789-012-345-000','Camille Bautista (Staffing Coordinator)','partnerships@primecare.ph','+63 2 8999 4433','Suite 808, One Park Drive, 9th Ave, BGC, Taguig City',NULL,1,'Certified staffing agency providing licensed baristas, cashiers, and banquet servers.','2026-09-24 07:48:10','2026-09-24 09:37:09');
/*!40000 ALTER TABLE `hr_companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_departments`
--

DROP TABLE IF EXISTS `hr_departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_departments` (
  `department_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(30) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`department_id`),
  UNIQUE KEY `departments_code_unique` (`code`),
  KEY `departments_branch_id_foreign` (`branch_id`),
  CONSTRAINT `departments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `hr_branches` (`branch_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_departments`
--

LOCK TABLES `hr_departments` WRITE;
/*!40000 ALTER TABLE `hr_departments` DISABLE KEYS */;
INSERT INTO `hr_departments` VALUES (1,1,'Management','MGMT','Restaurant operational management',1,'2026-09-23 10:40:49','2026-09-23 10:40:49'),(2,1,'Front of House','FOH','Dining, service, cashiering, and hosting',1,'2026-09-23 10:40:49','2026-09-23 10:40:49'),(3,1,'Back of House','BOH','Kitchen operations, culinary and dishwashing',1,'2026-09-23 10:40:49','2026-09-23 10:40:49'),(4,1,'Finance & Admin','FIN','Accounting and HR operations',1,'2026-09-23 10:40:49','2026-09-23 10:40:49'),(46,4,'Test Department','TEST_DEPT',NULL,1,'2026-09-30 17:36:31','2026-09-30 17:36:31');
/*!40000 ALTER TABLE `hr_departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_dtr_absence_codes`
--

DROP TABLE IF EXISTS `hr_dtr_absence_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_dtr_absence_codes` (
  `dtr_absence_code_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_absence` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`dtr_absence_code_id`),
  UNIQUE KEY `dtr_absence_codes_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_dtr_absence_codes`
--

LOCK TABLES `hr_dtr_absence_codes` WRITE;
/*!40000 ALTER TABLE `hr_dtr_absence_codes` DISABLE KEYS */;
INSERT INTO `hr_dtr_absence_codes` VALUES (1,'AA','Authorized Absence','Excused absence with prior management notification/approval',1,1,'2026-09-23 22:07:21','2026-09-23 22:07:21'),(2,'UA','Unauthorized Absence','Unexcused / AWOL absence without approved leave',1,1,'2026-09-23 22:07:21','2026-09-23 22:07:21'),(3,'PL','Paid Leave','Company-approved paid leave of absence',1,1,'2026-09-23 22:07:21','2026-09-23 22:07:21'),(4,'SL','Sick Leave','Medical or illness-related leave',1,1,'2026-09-23 22:07:21','2026-09-23 22:07:21'),(5,'VL','Vacation Leave','Scheduled vacation leave',1,1,'2026-09-23 22:07:21','2026-09-23 22:07:21'),(6,'ML','Maternity Leave','Statutory maternity leave (105 days)',1,1,'2026-09-23 22:07:21','2026-09-23 22:07:21'),(7,'EL','Emergency Leave','Family or domestic emergency leave',1,1,'2026-09-23 22:07:21','2026-09-23 22:07:21'),(8,'BL','Bereavement Leave','Death in immediate family',1,1,'2026-09-23 22:07:21','2026-09-23 22:07:21'),(9,'OB','Official Business','Out-of-office assignment, training, or company errands',0,1,'2026-09-23 22:07:21','2026-09-23 22:07:21'),(10,'RD','Rest Day','Scheduled weekly rest day',0,1,'2026-09-23 22:07:21','2026-09-23 22:07:21'),(11,'LH','Legal / Regular Holiday','Nationwide regular public holiday',0,1,'2026-09-23 22:07:21','2026-09-23 22:07:21'),(12,'SH','Special Non-Working Holiday','Special declared non-working holiday',0,1,'2026-09-23 22:07:21','2026-09-23 22:07:21'),(13,'SIL','Service Incentive Leave','Mandated 5-day annual service incentive leave',1,1,'2026-09-23 22:07:21','2026-09-23 22:07:21'),(14,'SPL','Solo Parent Leave','RA 8972 Solo parent statutory leave',1,1,'2026-09-23 22:07:21','2026-09-23 22:07:21');
/*!40000 ALTER TABLE `hr_dtr_absence_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_emergency_contacts`
--

DROP TABLE IF EXISTS `hr_emergency_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_emergency_contacts` (
  `emergency_contact_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `contact_name` varchar(100) NOT NULL,
  `relationship` varchar(50) NOT NULL,
  `contact_number` varchar(30) NOT NULL,
  `address` text DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `telephone` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`emergency_contact_id`),
  KEY `emergency_contacts_employee_id_foreign` (`employee_id`),
  CONSTRAINT `emergency_contacts_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`employee_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_emergency_contacts`
--

LOCK TABLES `hr_emergency_contacts` WRITE;
/*!40000 ALTER TABLE `hr_emergency_contacts` DISABLE KEYS */;
INSERT INTO `hr_emergency_contacts` VALUES (1,1,'Family Contact of Carlos','Parent / Spouse','09189998888','Poblacion, Makati City',0,NULL,NULL,'2026-09-23 10:41:27','2026-09-30 09:06:11'),(2,2,'Family Contact of Rodrigo','Parent / Spouse','09189998888','Guadalupe Nuevo, Makati City',0,NULL,NULL,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(3,3,'Family Contact of Maria','Parent / Spouse','09189998888','San Antonio Village, Makati City',0,NULL,NULL,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(4,4,'Family Contact of Juan','Parent / Spouse','09189998888','Pio Del Pilar, Makati City',0,NULL,NULL,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(5,5,'Family Contact of Beatriz','Parent / Spouse','09189998888','Pembo, Taguig City',0,NULL,NULL,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(6,6,'Family Contact of Gabriel','Parent / Spouse','09189998888','Comembo, Taguig City',0,NULL,NULL,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(7,7,'Family Contact of Krizza','Parent / Spouse','09189998888','East Rembo, Taguig City',0,NULL,NULL,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(8,8,'Family Contact of Eduardo','Parent / Spouse','09189998888','Kamuning, Quezon City',0,NULL,NULL,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(9,9,'Family Contact of Patricia','Parent / Spouse','09189998888','Scout Rallos, Quezon City',0,NULL,NULL,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(10,10,'Family Contact of Mark','Parent / Spouse','09189998888','Teachers Village, Quezon City',0,NULL,NULL,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(11,64,'Family Contact of Dorothy','Parent / Spouse','09189998888','Legaspi Village, Makati City',0,NULL,NULL,'2026-09-24 09:37:10','2026-09-24 09:37:10'),(12,65,'Family Contact of Peter','Parent / Spouse','09189998888','Salcedo Village, Makati City',0,NULL,NULL,'2026-09-24 09:37:10','2026-09-24 09:37:10'),(13,66,'Family Contact of Elena','Parent / Spouse','09189998888','San Lorenzo Village, Makati City',0,NULL,NULL,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(14,67,'Family Contact of John','Parent / Spouse','09189998888','Poblacion, Makati City',0,NULL,NULL,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(15,68,'Family Contact of Sarah','Parent / Spouse','09189998888','Palanan, Makati City',0,NULL,NULL,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(16,69,'Family Contact of Marco','Parent / Spouse','09189998888','Guadalupe Viejo, Makati City',0,NULL,NULL,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(17,1,'Pedro Dela Cruz','Father','09187654321',NULL,0,NULL,NULL,'2026-09-30 09:05:49','2026-09-30 09:06:11'),(18,1,'Pedro Dela Cruz','Father','09187654321',NULL,1,NULL,NULL,'2026-09-30 09:06:11','2026-09-30 09:06:11');
/*!40000 ALTER TABLE `hr_emergency_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_employee_clearance`
--

DROP TABLE IF EXISTS `hr_employee_clearance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_employee_clearance` (
  `employee_clearance_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `separation_id` bigint(20) unsigned DEFAULT NULL,
  `department` varchar(50) NOT NULL,
  `accountability` text NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Pending',
  `cleared_by` bigint(20) unsigned DEFAULT NULL,
  `cleared_at` timestamp NULL DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`employee_clearance_id`),
  KEY `hr_employee_clearance_employee_id_foreign` (`employee_id`),
  KEY `hr_employee_clearance_separation_id_foreign` (`separation_id`),
  KEY `hr_employee_clearance_cleared_by_foreign` (`cleared_by`),
  CONSTRAINT `hr_employee_clearance_cleared_by_foreign` FOREIGN KEY (`cleared_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `hr_employee_clearance_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`employee_id`) ON DELETE CASCADE,
  CONSTRAINT `hr_employee_clearance_separation_id_foreign` FOREIGN KEY (`separation_id`) REFERENCES `hr_separation` (`separation_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_employee_clearance`
--

LOCK TABLES `hr_employee_clearance` WRITE;
/*!40000 ALTER TABLE `hr_employee_clearance` DISABLE KEYS */;
INSERT INTO `hr_employee_clearance` VALUES (1,1,NULL,'IT','Surrender corporate laptop and security key','Pending',NULL,NULL,NULL,'2026-09-30 09:05:50','2026-09-30 09:05:50'),(2,1,NULL,'IT','Surrender corporate laptop and security key','Pending',NULL,NULL,NULL,'2026-09-30 09:06:11','2026-09-30 09:06:11');
/*!40000 ALTER TABLE `hr_employee_clearance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_employee_disciplinary`
--

DROP TABLE IF EXISTS `hr_employee_disciplinary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_employee_disciplinary` (
  `employee_disciplinary_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `incident_date` date NOT NULL,
  `case_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `action_taken` varchar(150) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Open',
  `resolution_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`employee_disciplinary_id`),
  KEY `hr_employee_disciplinary_employee_id_foreign` (`employee_id`),
  KEY `hr_employee_disciplinary_recorded_by_foreign` (`recorded_by`),
  CONSTRAINT `hr_employee_disciplinary_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`employee_id`) ON DELETE CASCADE,
  CONSTRAINT `hr_employee_disciplinary_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_employee_disciplinary`
--

LOCK TABLES `hr_employee_disciplinary` WRITE;
/*!40000 ALTER TABLE `hr_employee_disciplinary` DISABLE KEYS */;
INSERT INTO `hr_employee_disciplinary` VALUES (1,1,'2026-02-10','Attendance Infraction','Unexcused tardiness exceeding 60 minutes','Written Counseling','Resolved',NULL,NULL,1,'2026-09-30 09:05:49','2026-09-30 09:05:49'),(2,1,'2026-02-10','Attendance Infraction','Unexcused tardiness exceeding 60 minutes','Written Counseling','Resolved',NULL,NULL,1,'2026-09-30 09:06:11','2026-09-30 09:06:11');
/*!40000 ALTER TABLE `hr_employee_disciplinary` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_employee_documents`
--

DROP TABLE IF EXISTS `hr_employee_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_employee_documents` (
  `employee_document_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `document_name` varchar(150) NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'Personal',
  `issue_date` date DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Active',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`employee_document_id`),
  KEY `employee_documents_employee_id_foreign` (`employee_id`),
  KEY `employee_documents_uploaded_by_foreign` (`uploaded_by`),
  KEY `hr_employee_documents_verified_by_foreign` (`verified_by`),
  CONSTRAINT `employee_documents_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`employee_id`) ON DELETE CASCADE,
  CONSTRAINT `employee_documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `hr_employee_documents_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_employee_documents`
--

LOCK TABLES `hr_employee_documents` WRITE;
/*!40000 ALTER TABLE `hr_employee_documents` DISABLE KEYS */;
INSERT INTO `hr_employee_documents` VALUES (1,64,'Employment Contract','General Manager Employment Contract','Personal',NULL,'employee_documents/employment_contract.pdf',NULL,'Active',0,'Executive employment agreement signed by Board of Directors.',6,'2026-09-24 09:37:11','2026-09-24 09:37:11',NULL,NULL,NULL),(2,64,'Government ID','Philippine Passport & Unified ID Copy','Personal',NULL,'employee_documents/government_id.pdf','2032-05-15','Active',0,'Primary identification on file.',6,'2026-09-24 09:37:11','2026-09-24 09:37:11',NULL,NULL,NULL),(3,66,'Employment Contract','HR Manager Employment Contract','Personal',NULL,'employee_documents/employment_contract.pdf',NULL,'Active',0,'Regular employment contract for Head of Human Resources.',6,'2026-09-24 09:37:11','2026-09-24 09:37:11',NULL,NULL,NULL),(4,66,'NBI Clearance','NBI Background Clearance 2026','Personal',NULL,'employee_documents/nbi_clearance.pdf','2027-04-10','Active',0,'Cleared with no derogatory record.',6,'2026-09-24 09:37:11','2026-09-24 09:37:11',NULL,NULL,NULL),(5,1,'Employment Contract','Makati Restaurant Manager Contract','Personal',NULL,'employee_documents/employment_contract.pdf',NULL,'Active',0,'Annual manager performance and compensation rider attached.',6,'2026-09-24 09:37:11','2026-09-24 09:37:11',NULL,NULL,NULL),(6,1,'Health Permit','Makati City Sanitary Health Permit 2026','Personal',NULL,'employee_documents/health_permit_2026.pdf','2026-12-31','Active',0,'LGU Health Certificate issued by Makati City Health Department.',6,'2026-09-24 09:37:11','2026-09-24 09:37:11',NULL,NULL,NULL),(7,2,'Food Handler Certificate','Culinary Food Safety & Sanitation Certificate','Personal',NULL,'employee_documents/food_handler_cert.pdf','2027-03-01','Active',0,'Required kitchen handler certificate for Head Cook.',6,'2026-09-24 09:37:11','2026-09-24 09:37:11',NULL,NULL,NULL),(8,2,'Health Permit','City Sanitary Health Permit 2026','Personal',NULL,'employee_documents/health_permit_2026.pdf','2026-10-15','Active',0,'Renewal due in October 2026.',6,'2026-09-24 09:37:11','2026-09-24 09:37:11',NULL,NULL,NULL),(9,3,'NBI Clearance','National Police & NBI Clearance 2026','Personal',NULL,'employee_documents/nbi_clearance.pdf','2027-02-15','Active',0,'Annual clearance verified by HR.',6,'2026-09-24 09:37:11','2026-09-24 09:37:11',NULL,NULL,NULL),(10,67,'Government ID','SSS & PhilHealth ID Verification','Personal',NULL,'employee_documents/government_id.pdf','2029-08-20','Active',0,'Verified statutory cards on file.',6,'2026-09-24 09:37:11','2026-09-24 09:37:11',NULL,NULL,NULL),(11,68,'Health Permit','Food Server Health Certificate','Personal',NULL,'employee_documents/health_permit_2026.pdf','2026-11-20','Active',0,'Endorsed by ABC Manpower & Staffing Services.',6,'2026-09-24 09:37:11','2026-09-24 09:37:11',NULL,NULL,NULL),(12,69,'Food Handler Certificate','Commercial Cookery Food Safety Certificate','Personal',NULL,'employee_documents/food_handler_cert.pdf','2027-06-30','Active',0,'HACCP food handling certification.',6,'2026-09-24 09:37:11','2026-09-24 09:37:11',NULL,NULL,NULL),(13,5,'Employment Contract','BGC Branch Manager Contract','Personal',NULL,'employee_documents/employment_contract.pdf',NULL,'Active',0,'BGC Branch Manager standard agreement.',6,'2026-09-24 09:37:11','2026-09-24 09:37:11',NULL,NULL,NULL),(14,5,'Health Permit','Taguig City Health Permit 2026','Personal',NULL,'employee_documents/health_permit_2026.pdf','2026-12-31','Active',0,'Valid through end of year.',6,'2026-09-24 09:37:11','2026-09-24 09:37:11',NULL,NULL,NULL),(15,7,'Medical Certificate','Annual Pre-Employment Physical Exam','Personal',NULL,'employee_documents/medical_cert.pdf','2027-05-01','Active',0,'Fit to work medical exam clearance.',6,'2026-09-24 09:37:11','2026-09-24 09:37:11',NULL,NULL,NULL);
/*!40000 ALTER TABLE `hr_employee_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_employee_education`
--

DROP TABLE IF EXISTS `hr_employee_education`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_employee_education` (
  `employee_education_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `education_level` varchar(50) NOT NULL,
  `school_name` varchar(150) NOT NULL,
  `school_address` varchar(255) DEFAULT NULL,
  `course_degree` varchar(150) DEFAULT NULL,
  `major` varchar(100) DEFAULT NULL,
  `year_started` varchar(10) DEFAULT NULL,
  `year_completed` varchar(10) DEFAULT NULL,
  `graduation_date` date DEFAULT NULL,
  `honors` varchar(150) DEFAULT NULL,
  `units_earned` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`employee_education_id`),
  KEY `hr_employee_education_employee_id_foreign` (`employee_id`),
  CONSTRAINT `hr_employee_education_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`employee_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_employee_education`
--

LOCK TABLES `hr_employee_education` WRITE;
/*!40000 ALTER TABLE `hr_employee_education` DISABLE KEYS */;
INSERT INTO `hr_employee_education` VALUES (1,1,'College','University of Santo Tomas','Metro Manila, Philippines','BS Hotel and Restaurant Management','Culinary Arts','2018','2022','2022-04-15','Dean\'s Lister','Full Graduate','Official Transcript of Records verified.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(2,2,'College','De La Salle University','Metro Manila, Philippines','BS Business Administration','Operations Management','2018','2022','2022-04-15',NULL,'Full Graduate','Official Transcript of Records verified.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(3,3,'College','Polytechnic University of the Philippines','Metro Manila, Philippines','BS Accountancy','Financial Accounting','2019','2023','2023-04-15',NULL,'Full Graduate','Official Transcript of Records verified.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(4,4,'Vocational','TESDA Skills Training Center','Metro Manila, Philippines','Commercial Cooking NC II','Food & Beverage Services','2021','2025','2025-04-15','Dean\'s Lister','Full Graduate','Official Transcript of Records verified.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(5,5,'College','Far Eastern University','Metro Manila, Philippines','BS Information Technology','Enterprise Systems','2018','2022','2022-04-15',NULL,'Full Graduate','Official Transcript of Records verified.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(6,6,'College','University of the East','Metro Manila, Philippines','BS Hospitality Management','Kitchen Operations','2019','2023','2023-04-15',NULL,'Full Graduate','Official Transcript of Records verified.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(7,7,'College','University of Santo Tomas','Metro Manila, Philippines','BS Hotel and Restaurant Management','Culinary Arts','2021','2025','2025-04-15','Dean\'s Lister','Full Graduate','Official Transcript of Records verified.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(8,8,'College','De La Salle University','Metro Manila, Philippines','BS Business Administration','Operations Management','2018','2022','2022-04-15',NULL,'Full Graduate','Official Transcript of Records verified.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(9,9,'College','Polytechnic University of the Philippines','Metro Manila, Philippines','BS Accountancy','Financial Accounting','2019','2023','2023-04-15',NULL,'Full Graduate','Official Transcript of Records verified.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(10,10,'Vocational','TESDA Skills Training Center','Metro Manila, Philippines','Commercial Cooking NC II','Food & Beverage Services','2020','2024','2024-04-15','Dean\'s Lister','Full Graduate','Official Transcript of Records verified.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(11,64,'College','Far Eastern University','Metro Manila, Philippines','BS Information Technology','Enterprise Systems','2017','2021','2021-04-15',NULL,'Full Graduate','Official Transcript of Records verified.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(12,65,'College','University of the East','Metro Manila, Philippines','BS Hospitality Management','Kitchen Operations','2017','2021','2021-04-15',NULL,'Full Graduate','Official Transcript of Records verified.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(13,66,'College','University of Santo Tomas','Metro Manila, Philippines','BS Hotel and Restaurant Management','Culinary Arts','2018','2022','2022-04-15','Dean\'s Lister','Full Graduate','Official Transcript of Records verified.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(14,67,'College','De La Salle University','Metro Manila, Philippines','BS Business Administration','Operations Management','2019','2023','2023-04-15',NULL,'Full Graduate','Official Transcript of Records verified.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(15,68,'College','Polytechnic University of the Philippines','Metro Manila, Philippines','BS Accountancy','Financial Accounting','2020','2024','2024-04-15',NULL,'Full Graduate','Official Transcript of Records verified.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(16,69,'Vocational','TESDA Skills Training Center','Metro Manila, Philippines','Commercial Cooking NC II','Food & Beverage Services','2018','2022','2022-04-15','Dean\'s Lister','Full Graduate','Official Transcript of Records verified.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(17,1,'College','University of the Philippines Diliman',NULL,'BS Business Administration',NULL,'2014','2018',NULL,'Cum Laude',NULL,NULL,'2026-09-30 09:04:55','2026-09-30 09:04:55'),(18,1,'College','University of the Philippines Diliman',NULL,'BS Business Administration',NULL,'2014','2018',NULL,'Cum Laude',NULL,NULL,'2026-09-30 09:05:49','2026-09-30 09:05:49'),(19,1,'College','University of the Philippines Diliman',NULL,'BS Business Administration',NULL,'2014','2018',NULL,'Cum Laude',NULL,NULL,'2026-09-30 09:06:11','2026-09-30 09:06:11');
/*!40000 ALTER TABLE `hr_employee_education` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_employee_family`
--

DROP TABLE IF EXISTS `hr_employee_family`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_employee_family` (
  `employee_family_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `relationship` varchar(50) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `sex` varchar(20) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `employer` varchar(150) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_dependent` tinyint(1) NOT NULL DEFAULT 0,
  `is_beneficiary` tinyint(1) NOT NULL DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`employee_family_id`),
  KEY `hr_employee_family_employee_id_foreign` (`employee_id`),
  CONSTRAINT `hr_employee_family_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`employee_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_employee_family`
--

LOCK TABLES `hr_employee_family` WRITE;
/*!40000 ALTER TABLE `hr_employee_family` DISABLE KEYS */;
INSERT INTO `hr_employee_family` VALUES (1,1,'Maria Mendoza','Spouse','1995-06-20','Female','Self-employed / Private Sector',NULL,'+63 917 307 9829','Poblacion, Makati City',1,1,'Primary declared dependent for statutory health benefits.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(2,2,'Maria Santos','Child','1993-06-20','Female','Self-employed / Private Sector',NULL,'+63 917 869 1713','Guadalupe Nuevo, Makati City',1,1,'Primary declared dependent for statutory health benefits.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(3,3,'Maria Garcia','Parent','1991-06-20','Female','Self-employed / Private Sector',NULL,'+63 917 216 2584','San Antonio Village, Makati City',0,1,'Primary declared dependent for statutory health benefits.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(4,4,'Maria Dela Cruz','Sibling','1989-06-20','Female','Self-employed / Private Sector',NULL,'+63 917 350 4346','Pio Del Pilar, Makati City',0,0,'Primary declared dependent for statutory health benefits.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(5,5,'Maria Valdez','Spouse','1987-06-20','Female','Self-employed / Private Sector',NULL,'+63 917 756 5173','Pembo, Taguig City',1,1,'Primary declared dependent for statutory health benefits.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(6,6,'Maria Tan','Child','1985-06-20','Female','Self-employed / Private Sector',NULL,'+63 917 317 1174','Comembo, Taguig City',1,1,'Primary declared dependent for statutory health benefits.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(7,7,'Maria Navarro','Parent','1983-06-20','Female','Self-employed / Private Sector',NULL,'+63 917 339 2078','East Rembo, Taguig City',0,1,'Primary declared dependent for statutory health benefits.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(8,8,'Maria Ramos','Sibling','1981-06-20','Female','Self-employed / Private Sector',NULL,'+63 917 721 1535','Kamuning, Quezon City',0,0,'Primary declared dependent for statutory health benefits.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(9,9,'Maria Lim','Spouse','1979-06-20','Female','Self-employed / Private Sector',NULL,'+63 917 422 6408','Scout Rallos, Quezon City',1,1,'Primary declared dependent for statutory health benefits.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(10,10,'Maria Villanueva','Child','1977-06-20','Female','Self-employed / Private Sector',NULL,'+63 917 790 1231','Teachers Village, Quezon City',1,1,'Primary declared dependent for statutory health benefits.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(11,64,'Maria Diaz','Parent','1975-06-20','Female','Self-employed / Private Sector',NULL,'+63 917 410 7405','Legaspi Village, Makati City',0,1,'Primary declared dependent for statutory health benefits.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(12,65,'Maria Administrator','Sibling','1973-06-20','Female','Self-employed / Private Sector',NULL,'+63 917 681 2379','Salcedo Village, Makati City',0,0,'Primary declared dependent for statutory health benefits.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(13,66,'Maria Reyes','Spouse','1971-06-20','Female','Self-employed / Private Sector',NULL,'+63 917 813 7213','San Lorenzo Village, Makati City',1,1,'Primary declared dependent for statutory health benefits.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(14,67,'Maria Cashier','Child','1969-06-20','Female','Self-employed / Private Sector',NULL,'+63 917 364 1414','Poblacion, Makati City',1,1,'Primary declared dependent for statutory health benefits.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(15,68,'Maria Staff','Parent','1967-06-20','Female','Self-employed / Private Sector',NULL,'+63 917 792 1760','Palanan, Makati City',0,1,'Primary declared dependent for statutory health benefits.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(16,69,'Maria Rossi','Sibling','1965-06-20','Female','Self-employed / Private Sector',NULL,'+63 917 567 2933','Guadalupe Viejo, Makati City',0,0,'Primary declared dependent for statutory health benefits.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(17,1,'Clara Dela Cruz','Spouse','1995-04-10','Female','Teacher','DepEd','09171234567',NULL,1,1,NULL,'2026-09-30 09:03:38','2026-09-30 09:03:38'),(18,1,'Clara Dela Cruz','Spouse','1995-04-10','Female','Teacher','DepEd','09171234567',NULL,1,1,NULL,'2026-09-30 09:04:55','2026-09-30 09:04:55'),(19,1,'Clara Dela Cruz','Spouse','1995-04-10','Female','Teacher','DepEd','09171234567',NULL,1,1,NULL,'2026-09-30 09:05:49','2026-09-30 09:05:49'),(20,1,'Clara Dela Cruz','Spouse','1995-04-10','Female','Teacher','DepEd','09171234567',NULL,1,1,NULL,'2026-09-30 09:06:11','2026-09-30 09:06:11');
/*!40000 ALTER TABLE `hr_employee_family` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_employee_permissions`
--

DROP TABLE IF EXISTS `hr_employee_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_employee_permissions` (
  `employee_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`employee_id`,`permission_id`),
  KEY `employee_permissions_permission_id_foreign` (`permission_id`),
  CONSTRAINT `employee_permissions_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`employee_id`) ON DELETE CASCADE,
  CONSTRAINT `employee_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_employee_permissions`
--

LOCK TABLES `hr_employee_permissions` WRITE;
/*!40000 ALTER TABLE `hr_employee_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr_employee_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_employee_schedules`
--

DROP TABLE IF EXISTS `hr_employee_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_employee_schedules` (
  `employee_schedule_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `shift_template_id` bigint(20) unsigned DEFAULT NULL,
  `schedule_date` date NOT NULL,
  `custom_start_time` time DEFAULT NULL,
  `custom_end_time` time DEFAULT NULL,
  `is_rest_day` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`employee_schedule_id`),
  UNIQUE KEY `employee_schedules_employee_id_schedule_date_unique` (`employee_id`,`schedule_date`),
  KEY `employee_schedules_branch_id_foreign` (`branch_id`),
  KEY `employee_schedules_shift_template_id_foreign` (`shift_template_id`),
  CONSTRAINT `employee_schedules_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `hr_branches` (`branch_id`) ON DELETE SET NULL,
  CONSTRAINT `employee_schedules_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`employee_id`) ON DELETE CASCADE,
  CONSTRAINT `employee_schedules_shift_template_id_foreign` FOREIGN KEY (`shift_template_id`) REFERENCES `hr_shift_templates` (`shift_template_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3140 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_employee_schedules`
--

LOCK TABLES `hr_employee_schedules` WRITE;
/*!40000 ALTER TABLE `hr_employee_schedules` DISABLE KEYS */;
INSERT INTO `hr_employee_schedules` VALUES (1,1,1,1,'2026-09-16','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(2,1,1,1,'2026-09-17','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(3,1,1,1,'2026-09-18','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(4,1,1,1,'2026-09-19','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(5,1,1,1,'2026-09-20','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(6,1,1,1,'2026-09-21','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-24 09:37:11'),(7,1,1,1,'2026-09-22','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-24 09:37:11'),(8,1,1,1,'2026-09-23','06:00:00','15:00:00',1,'Scheduled rest day','2026-09-23 10:41:27','2026-09-24 09:37:11'),(9,2,1,2,'2026-09-16','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(10,2,1,2,'2026-09-17','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(11,2,1,2,'2026-09-18','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(12,2,1,2,'2026-09-19','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(13,2,1,2,'2026-09-20','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(14,2,1,2,'2026-09-21','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(15,2,1,2,'2026-09-22','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-24 09:37:11'),(16,2,1,2,'2026-09-23','14:00:00','23:00:00',1,'Scheduled rest day','2026-09-23 10:41:27','2026-09-24 09:37:11'),(17,3,1,3,'2026-09-16','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(18,3,1,3,'2026-09-17','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(19,3,1,3,'2026-09-18','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(20,3,1,3,'2026-09-19','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(21,3,1,3,'2026-09-20','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(22,3,1,3,'2026-09-21','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(23,3,1,3,'2026-09-22','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-24 09:37:11'),(24,3,1,3,'2026-09-23','22:00:00','07:00:00',1,'Scheduled rest day','2026-09-23 10:41:27','2026-09-24 09:37:11'),(25,4,1,1,'2026-09-16','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(26,4,1,1,'2026-09-17','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(27,4,1,1,'2026-09-18','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(28,4,1,1,'2026-09-19','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(29,4,1,1,'2026-09-20','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(30,4,1,1,'2026-09-21','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(31,4,1,1,'2026-09-22','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-24 09:37:11'),(32,4,1,1,'2026-09-23','06:00:00','15:00:00',1,'Scheduled rest day','2026-09-23 10:41:27','2026-09-24 09:37:11'),(33,5,2,2,'2026-09-16','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(34,5,2,2,'2026-09-17','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(35,5,2,2,'2026-09-18','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(36,5,2,2,'2026-09-19','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(37,5,2,2,'2026-09-20','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(38,5,2,2,'2026-09-21','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(39,5,2,2,'2026-09-22','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-24 09:37:11'),(40,5,2,2,'2026-09-23','14:00:00','23:00:00',1,'Scheduled rest day','2026-09-23 10:41:27','2026-09-24 09:37:11'),(41,6,2,3,'2026-09-16','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(42,6,2,3,'2026-09-17','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(43,6,2,3,'2026-09-18','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(44,6,2,3,'2026-09-19','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(45,6,2,3,'2026-09-20','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(46,6,2,3,'2026-09-21','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(47,6,2,3,'2026-09-22','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-24 09:37:11'),(48,6,2,3,'2026-09-23','22:00:00','07:00:00',1,'Scheduled rest day','2026-09-23 10:41:27','2026-09-24 09:37:11'),(49,7,2,1,'2026-09-16','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(50,7,2,1,'2026-09-17','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(51,7,2,1,'2026-09-18','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(52,7,2,1,'2026-09-19','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(53,7,2,1,'2026-09-20','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(54,7,2,1,'2026-09-21','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(55,7,2,1,'2026-09-22','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-24 09:37:11'),(56,7,2,1,'2026-09-23','06:00:00','15:00:00',1,'Scheduled rest day','2026-09-23 10:41:27','2026-09-24 09:37:11'),(57,8,3,2,'2026-09-16','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(58,8,3,2,'2026-09-17','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(59,8,3,2,'2026-09-18','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(60,8,3,2,'2026-09-19','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(61,8,3,2,'2026-09-20','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(62,8,3,2,'2026-09-21','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(63,8,3,2,'2026-09-22','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-24 09:37:11'),(64,8,3,2,'2026-09-23','14:00:00','23:00:00',1,'Scheduled rest day','2026-09-23 10:41:27','2026-09-24 09:37:11'),(65,9,3,3,'2026-09-16','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(66,9,3,3,'2026-09-17','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(67,9,3,3,'2026-09-18','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(68,9,3,3,'2026-09-19','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(69,9,3,3,'2026-09-20','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(70,9,3,3,'2026-09-21','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(71,9,3,3,'2026-09-22','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-24 09:37:11'),(72,9,3,3,'2026-09-23','22:00:00','07:00:00',1,'Scheduled rest day','2026-09-23 10:41:27','2026-09-24 09:37:11'),(73,10,3,1,'2026-09-16','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(74,10,3,1,'2026-09-17','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(75,10,3,1,'2026-09-18','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(76,10,3,1,'2026-09-19','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(77,10,3,1,'2026-09-20','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(78,10,3,1,'2026-09-21','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-23 10:41:27'),(79,10,3,1,'2026-09-22','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-23 10:41:27','2026-09-24 09:37:11'),(80,10,3,1,'2026-09-23','06:00:00','15:00:00',1,'Scheduled rest day','2026-09-23 10:41:27','2026-09-24 09:37:11'),(81,1,1,1,'2026-09-24','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-24 00:28:33','2026-09-24 09:37:11'),(82,1,1,1,'2026-09-25',NULL,NULL,0,NULL,'2026-09-24 00:28:33','2026-09-24 00:28:33'),(83,1,1,NULL,'2026-09-26',NULL,NULL,1,NULL,'2026-09-24 00:28:33','2026-09-24 00:28:33'),(84,1,1,NULL,'2026-09-27',NULL,NULL,1,NULL,'2026-09-24 00:28:33','2026-09-24 00:28:33'),(85,1,1,4,'2026-10-01',NULL,NULL,0,NULL,'2026-09-24 00:28:33','2026-09-26 22:11:41'),(86,1,1,4,'2026-10-02',NULL,NULL,0,NULL,'2026-09-24 00:28:33','2026-09-26 22:11:41'),(87,1,1,NULL,'2026-10-03',NULL,NULL,1,NULL,'2026-09-24 00:28:33','2026-09-24 00:28:33'),(88,64,1,1,'2026-09-17','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(89,64,1,1,'2026-09-18','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(90,64,1,1,'2026-09-19','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(91,64,1,1,'2026-09-20','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(92,64,1,1,'2026-09-21','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(93,64,1,1,'2026-09-22','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(94,64,1,1,'2026-09-23','06:00:00','15:00:00',1,'Scheduled rest day','2026-09-24 09:37:11','2026-09-24 09:37:11'),(95,64,1,1,'2026-09-24','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(96,65,1,2,'2026-09-17','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(97,65,1,2,'2026-09-18','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(98,65,1,2,'2026-09-19','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(99,65,1,2,'2026-09-20','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(100,65,1,2,'2026-09-21','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(101,65,1,2,'2026-09-22','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(102,65,1,2,'2026-09-23','14:00:00','23:00:00',1,'Scheduled rest day','2026-09-24 09:37:11','2026-09-24 09:37:11'),(103,65,1,2,'2026-09-24','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(104,66,1,3,'2026-09-17','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(105,66,1,3,'2026-09-18','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(106,66,1,3,'2026-09-19','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(107,66,1,3,'2026-09-20','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(108,66,1,3,'2026-09-21','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(109,66,1,3,'2026-09-22','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(110,66,1,3,'2026-09-23','22:00:00','07:00:00',1,'Scheduled rest day','2026-09-24 09:37:11','2026-09-24 09:37:11'),(111,66,1,3,'2026-09-24','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(112,2,1,2,'2026-09-24','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(113,3,1,3,'2026-09-24','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(114,67,1,1,'2026-09-17','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(115,67,1,1,'2026-09-18','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(116,67,1,1,'2026-09-19','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(117,67,1,1,'2026-09-20','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(118,67,1,1,'2026-09-21','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(119,67,1,1,'2026-09-22','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(120,67,1,1,'2026-09-23','06:00:00','15:00:00',1,'Scheduled rest day','2026-09-24 09:37:11','2026-09-24 09:37:11'),(121,67,1,1,'2026-09-24','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(122,68,1,2,'2026-09-17','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(123,68,1,2,'2026-09-18','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(124,68,1,2,'2026-09-19','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(125,68,1,2,'2026-09-20','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(126,68,1,2,'2026-09-21','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(127,68,1,2,'2026-09-22','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(128,68,1,2,'2026-09-23','14:00:00','23:00:00',1,'Scheduled rest day','2026-09-24 09:37:11','2026-09-24 09:37:11'),(129,68,1,2,'2026-09-24','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(130,69,1,3,'2026-09-17','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(131,69,1,3,'2026-09-18','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(132,69,1,3,'2026-09-19','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(133,69,1,3,'2026-09-20','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(134,69,1,3,'2026-09-21','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(135,69,1,3,'2026-09-22','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(136,69,1,3,'2026-09-23','22:00:00','07:00:00',1,'Scheduled rest day','2026-09-24 09:37:11','2026-09-24 09:37:11'),(137,69,1,3,'2026-09-24','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(138,4,1,1,'2026-09-24','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(139,5,2,2,'2026-09-24','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(140,6,2,3,'2026-09-24','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(141,7,2,1,'2026-09-24','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(142,8,3,2,'2026-09-24','14:00:00','23:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(143,9,3,3,'2026-09-24','22:00:00','07:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(144,10,3,1,'2026-09-24','06:00:00','15:00:00',0,'Regular restaurant shift','2026-09-24 09:37:11','2026-09-24 09:37:11'),(145,2,1,NULL,'2026-09-27',NULL,NULL,1,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(146,3,1,NULL,'2026-09-27',NULL,NULL,1,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(147,5,2,NULL,'2026-09-27',NULL,NULL,1,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(148,6,2,NULL,'2026-09-27',NULL,NULL,1,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(149,8,3,NULL,'2026-09-27',NULL,NULL,1,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(150,9,3,NULL,'2026-09-27',NULL,NULL,1,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(151,10,3,NULL,'2026-09-27',NULL,NULL,1,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(152,64,1,NULL,'2026-09-27',NULL,NULL,1,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(153,65,1,NULL,'2026-09-27',NULL,NULL,1,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(154,66,1,NULL,'2026-09-27',NULL,NULL,1,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(155,67,1,NULL,'2026-09-27',NULL,NULL,1,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(156,68,1,NULL,'2026-09-27',NULL,NULL,1,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(157,69,1,NULL,'2026-09-27',NULL,NULL,1,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(158,1,1,4,'2026-09-28',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(159,2,1,4,'2026-09-28',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(160,3,1,4,'2026-09-28',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(162,6,2,4,'2026-09-28',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(163,8,3,4,'2026-09-28',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(164,9,3,4,'2026-09-28',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(165,10,3,4,'2026-09-28',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(167,65,1,4,'2026-09-28',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(168,66,1,4,'2026-09-28',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(169,67,1,4,'2026-09-28',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(170,68,1,4,'2026-09-28',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(171,69,1,4,'2026-09-28',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(172,1,1,4,'2026-09-29',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(173,2,1,4,'2026-09-29',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(174,3,1,4,'2026-09-29',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(175,5,2,4,'2026-09-29',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(176,6,2,4,'2026-09-29',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(177,8,3,4,'2026-09-29',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(178,9,3,4,'2026-09-29',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(179,10,3,4,'2026-09-29',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(180,64,1,4,'2026-09-29',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(181,65,1,4,'2026-09-29',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(182,66,1,4,'2026-09-29',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(183,67,1,4,'2026-09-29',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(184,68,1,4,'2026-09-29',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(185,69,1,4,'2026-09-29',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(187,2,1,4,'2026-09-30',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(188,3,1,4,'2026-09-30',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(189,5,2,8,'2026-09-30',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-30 05:24:58'),(190,6,2,4,'2026-09-30',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(191,8,3,4,'2026-09-30',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(192,9,3,4,'2026-09-30',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(193,10,3,4,'2026-09-30',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(194,64,1,7,'2026-09-30',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-30 07:32:05'),(195,65,1,4,'2026-09-30',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(196,66,1,4,'2026-09-30',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(197,67,1,4,'2026-09-30',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(198,68,1,4,'2026-09-30',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(199,69,1,4,'2026-09-30',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(200,2,1,4,'2026-10-01',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(201,3,1,4,'2026-10-01',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(202,5,2,1,'2026-10-01',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-29 23:45:38'),(203,6,2,4,'2026-10-01',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(204,8,3,4,'2026-10-01',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(205,9,3,4,'2026-10-01',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(206,10,3,4,'2026-10-01',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(208,65,1,4,'2026-10-01',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(209,66,1,4,'2026-10-01',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(210,67,1,4,'2026-10-01',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(211,68,1,4,'2026-10-01',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(212,69,1,4,'2026-10-01',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(213,2,1,4,'2026-10-02',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(214,3,1,4,'2026-10-02',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(215,5,2,4,'2026-10-02',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(216,6,2,4,'2026-10-02',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(217,8,3,4,'2026-10-02',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(218,9,3,4,'2026-10-02',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(219,10,3,4,'2026-10-02',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(220,64,1,4,'2026-10-02',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(221,65,1,4,'2026-10-02',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(222,66,1,4,'2026-10-02',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(223,67,1,4,'2026-10-02',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(224,68,1,4,'2026-10-02',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(225,69,1,4,'2026-10-02',NULL,NULL,0,NULL,'2026-09-26 22:11:41','2026-09-26 22:11:41'),(373,8,3,NULL,'2026-10-03',NULL,NULL,1,'RESTDAY','2026-09-29 04:49:05','2026-09-29 04:49:05'),(591,5,2,NULL,'2026-09-28','08:00:00','17:00:00',0,'OPENING','2026-09-29 07:48:26','2026-09-29 07:48:26'),(664,64,1,NULL,'2026-09-28','08:00:00','17:00:00',0,'OPENING','2026-09-29 08:00:25','2026-09-29 08:00:25'),(713,1,1,NULL,'2026-09-30','08:00:00','17:00:00',0,'OPENING','2026-09-29 08:17:34','2026-09-29 08:17:34'),(738,64,1,NULL,'2026-10-01','08:00:00','17:00:00',0,'OPENING','2026-09-29 08:23:32','2026-09-29 08:23:32'),(3126,5,2,NULL,'2026-10-05','08:00:00','17:00:00',0,'OPENING','2026-10-09 02:18:29','2026-10-09 02:18:29'),(3127,5,2,NULL,'2026-10-06',NULL,NULL,1,'RESTDAY','2026-10-09 02:18:30','2026-10-09 02:18:30'),(3128,5,2,NULL,'2026-10-07','12:00:00','21:00:00',0,'LATE DAY','2026-10-09 02:18:31','2026-10-09 02:18:31'),(3129,5,2,NULL,'2026-10-08','12:00:00','21:00:00',0,'LATE DAY','2026-10-09 02:18:32','2026-10-09 02:18:32'),(3130,5,2,NULL,'2026-10-09',NULL,NULL,1,'RESTDAY','2026-10-09 02:18:34','2026-10-09 02:18:34'),(3131,5,2,NULL,'2026-10-10','13:00:00','22:00:00',0,'CLOSING','2026-10-09 02:18:35','2026-10-09 02:18:35'),(3132,5,2,NULL,'2026-10-11','13:00:00','22:00:00',0,'CLOSING','2026-10-09 02:18:36','2026-10-09 02:18:36'),(3133,1,1,NULL,'2026-10-11','13:00:00','22:00:00',0,'CLOSING','2026-10-09 02:18:37','2026-10-09 02:18:37'),(3134,1,1,NULL,'2026-10-05',NULL,NULL,1,'RESTDAY','2026-10-09 02:18:38','2026-10-09 02:18:38'),(3135,1,1,NULL,'2026-10-06',NULL,NULL,1,'RESTDAY','2026-10-09 02:18:39','2026-10-09 02:18:39'),(3136,1,1,NULL,'2026-10-07',NULL,NULL,1,'RESTDAY','2026-10-09 02:18:39','2026-10-09 02:18:39'),(3137,1,1,NULL,'2026-10-08',NULL,NULL,1,'RESTDAY','2026-10-09 02:18:40','2026-10-09 02:18:40'),(3138,1,1,NULL,'2026-10-09','10:00:00','19:00:00',0,'MID DAY','2026-10-09 02:18:41','2026-10-09 02:18:41'),(3139,1,1,NULL,'2026-10-10',NULL,NULL,1,'RESTDAY','2026-10-09 02:18:41','2026-10-09 02:18:41');
/*!40000 ALTER TABLE `hr_employee_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_employees`
--

DROP TABLE IF EXISTS `hr_employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_employees` (
  `employee_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_code` varchar(30) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `first_name` varchar(60) NOT NULL,
  `middle_name` varchar(60) DEFAULT NULL,
  `last_name` varchar(60) NOT NULL,
  `suffix` varchar(15) DEFAULT NULL,
  `preferred_name` varchar(100) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `birth_place` varchar(150) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `civil_status` enum('Single','Married','Widowed','Divorced','Separated') NOT NULL DEFAULT 'Single',
  `nationality` varchar(50) NOT NULL DEFAULT 'Filipino',
  `mobile_number` varchar(30) DEFAULT NULL,
  `telephone_number` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `company_email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `permanent_address` text DEFAULT NULL,
  `family_dependents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`family_dependents`)),
  `education_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`education_history`)),
  `salary_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`salary_history`)),
  `photo` varchar(255) DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_branch_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`assigned_branch_ids`)),
  `work_location` varchar(150) DEFAULT NULL,
  `work_schedule` varchar(150) DEFAULT NULL,
  `default_shift_template_id` bigint(20) unsigned DEFAULT NULL,
  `job_level` varchar(50) DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_department_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`assigned_department_ids`)),
  `position_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_position_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`assigned_position_ids`)),
  `supervisor_id` bigint(20) unsigned DEFAULT NULL,
  `date_hired` date NOT NULL,
  `employment_status` enum('Active','Probationary','On Leave','Suspended','Resigned','Terminated','Retired') NOT NULL DEFAULT 'Probationary',
  `employment_type` enum('Regular','Probationary','Part-time','Casual','Contractual') NOT NULL DEFAULT 'Probationary',
  `company_name` varchar(150) DEFAULT NULL,
  `agency_name` varchar(150) DEFAULT NULL,
  `employment_source` varchar(30) DEFAULT NULL,
  `company_agency_name` varchar(150) DEFAULT NULL,
  `date_of_regularization` date DEFAULT NULL,
  `probation_end_date` date DEFAULT NULL,
  `date_separated` date DEFAULT NULL,
  `contract_start_date` date DEFAULT NULL,
  `contract_end_date` date DEFAULT NULL,
  `sss_number` varchar(30) DEFAULT NULL,
  `sss_verified` tinyint(1) NOT NULL DEFAULT 0,
  `sss_membership_status` varchar(50) NOT NULL DEFAULT 'Active',
  `philhealth_number` varchar(30) DEFAULT NULL,
  `philhealth_verified` tinyint(1) NOT NULL DEFAULT 0,
  `philhealth_membership_status` varchar(50) NOT NULL DEFAULT 'Active',
  `pagibig_number` varchar(30) DEFAULT NULL,
  `pagibig_verified` tinyint(1) NOT NULL DEFAULT 0,
  `pagibig_membership_status` varchar(50) NOT NULL DEFAULT 'Active',
  `tin` varchar(30) DEFAULT NULL,
  `tin_verified` tinyint(1) NOT NULL DEFAULT 0,
  `rdo_code` varchar(20) DEFAULT NULL,
  `philsys_id` varchar(50) DEFAULT NULL,
  `passport_number` varchar(50) DEFAULT NULL,
  `driver_license` varchar(50) DEFAULT NULL,
  `other_gov_id_type` varchar(50) DEFAULT NULL,
  `other_gov_id_number` varchar(50) DEFAULT NULL,
  `other_gov_id_verified` tinyint(1) NOT NULL DEFAULT 0,
  `basic_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payroll_type` enum('Daily','Monthly') NOT NULL DEFAULT 'Monthly',
  `salary_type` enum('Monthly','Daily','Hourly') NOT NULL DEFAULT 'Monthly',
  `pay_frequency` enum('Semi-Monthly','Monthly','Weekly') NOT NULL DEFAULT 'Semi-Monthly',
  `allowances` decimal(10,2) NOT NULL DEFAULT 0.00,
  `other_compensation` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`employee_id`),
  UNIQUE KEY `employees_employee_id_unique` (`employee_code`),
  KEY `employees_user_id_foreign` (`user_id`),
  KEY `employees_branch_id_foreign` (`branch_id`),
  KEY `employees_department_id_foreign` (`department_id`),
  KEY `employees_position_id_foreign` (`position_id`),
  KEY `employees_supervisor_id_foreign` (`supervisor_id`),
  KEY `employees_company_id_foreign` (`company_id`),
  CONSTRAINT `employees_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `hr_branches` (`branch_id`) ON DELETE SET NULL,
  CONSTRAINT `employees_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `hr_companies` (`company_id`) ON DELETE SET NULL,
  CONSTRAINT `employees_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`department_id`) ON DELETE SET NULL,
  CONSTRAINT `employees_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `hr_positions` (`position_id`) ON DELETE SET NULL,
  CONSTRAINT `employees_supervisor_id_foreign` FOREIGN KEY (`supervisor_id`) REFERENCES `hr_employees` (`employee_id`) ON DELETE SET NULL,
  CONSTRAINT `employees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1355 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_employees`
--

LOCK TABLES `hr_employees` WRITE;
/*!40000 ALTER TABLE `hr_employees` DISABLE KEYS */;
INSERT INTO `hr_employees` VALUES (1,'EMP-2026-001',7,'Carlos',NULL,'Mendoza',NULL,'Carlos','1988-04-12','City of Manila, Philippines','Male','Married','Filipino','09171234501',NULL,'carlos.mendoza@rusticrestaurant.ph','carlos.mendoza@rms.ph','Poblacion, Makati City','Poblacion, Makati City',NULL,NULL,'[{\"effective_date\":\"2026-10-01\",\"previous_salary\":38000,\"new_salary\":38500,\"adjustment_type\":\"Wage Distortion Adjustment\",\"reason\":\"Wage Distortion Adjustment (The Pineda Formula) - Test Unit Simulation\",\"approved_by\":\"System Administrator\",\"applied_at\":\"2026-10-01T18:52:59+00:00\"}]',NULL,1,NULL,NULL,NULL,NULL,NULL,1,1,NULL,1,NULL,NULL,'2023-01-15','Active','Regular','Bistro Hospitality Group Inc.',NULL,'Direct','Bistro Hospitality Group Inc.','2023-07-15',NULL,NULL,NULL,NULL,'34-1234567-8',1,'Active','12-345678901-2',1,'Active','1234-5678-9012',1,'Active','123-456-789-000',1,NULL,NULL,NULL,NULL,NULL,NULL,0,38000.00,'Monthly','Monthly','Semi-Monthly',5000.00,NULL,NULL,'2026-09-23 10:41:27','2026-10-01 10:52:59'),(2,'EMP-2026-002',NULL,'Rodrigo',NULL,'Santos',NULL,'Rodrigo','1992-08-20','City of Manila, Philippines','Male','Single','Filipino','09171234502',NULL,'rodrigo.santos@rusticrestaurant.ph','rodrigo.santos@rms.ph','Guadalupe Nuevo, Makati City','Guadalupe Nuevo, Makati City',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,1,3,NULL,3,NULL,NULL,'2023-03-01','Active','Regular','Bistro Hospitality Group Inc.',NULL,'Direct','Bistro Hospitality Group Inc.','2023-09-01',NULL,NULL,NULL,NULL,'34-2345678-9',1,'Active','12-456789012-3',1,'Active','1210-8765-4321',1,'Active','345-678-901-000',1,NULL,NULL,NULL,NULL,NULL,NULL,0,32000.00,'Monthly','Monthly','Semi-Monthly',3000.00,NULL,NULL,'2026-09-23 10:41:27','2026-09-30 08:47:10'),(3,'EMP-2026-003',NULL,'Maria',NULL,'Garcia',NULL,'Maria','1998-11-05','City of Manila, Philippines','Female','Single','Filipino','09171234503',NULL,'maria.garcia@rusticrestaurant.ph','maria.garcia@rms.ph','San Antonio Village, Makati City','San Antonio Village, Makati City',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,1,2,NULL,7,NULL,NULL,'2024-02-15','Active','Regular','Bistro Hospitality Group Inc.',NULL,'Direct','Bistro Hospitality Group Inc.','2024-08-15',NULL,NULL,NULL,NULL,'34-3456789-0',1,'Active','12-567890123-4',1,'Active','1210-7654-3210',1,'Active','456-789-012-000',1,NULL,NULL,NULL,NULL,NULL,NULL,0,20000.00,'Monthly','Monthly','Semi-Monthly',1500.00,NULL,NULL,'2026-09-23 10:41:27','2026-09-30 08:47:10'),(4,'EMP-2026-004',NULL,'Juan',NULL,'Dela Cruz',NULL,'Juan','2001-02-14','City of Manila, Philippines','Male','Single','Filipino','09171234504',NULL,'juan.delacruz@rusticrestaurant.ph','juan.dela cruz@rms.ph','Pio Del Pilar, Makati City','Pio Del Pilar, Makati City',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,2,2,NULL,8,NULL,NULL,'2026-04-01','Probationary','Probationary','ABC Manpower & Staffing Services','ABC Manpower & Staffing Services','Agency','ABC Manpower & Staffing Services',NULL,NULL,NULL,'2026-04-01','2026-10-01','34-4567890-1',1,'Active','12-678901234-5',1,'Active','1210-6543-2109',1,'Active','567-890-123-000',1,NULL,NULL,NULL,NULL,NULL,NULL,0,17000.00,'Monthly','Monthly','Semi-Monthly',1000.00,NULL,NULL,'2026-09-23 10:41:27','2026-09-30 08:47:10'),(5,'EMP-2026-005',8,'Beatriz',NULL,'Valdez',NULL,'Beatriz','1990-07-22','City of Manila, Philippines','Female','Married','Filipino','09171234505',NULL,'beatriz.valdez@rusticrestaurant.ph','beatriz.valdez@rms.ph','Pembo, Taguig City','Pembo, Taguig City',NULL,NULL,NULL,NULL,2,NULL,NULL,NULL,NULL,NULL,1,1,NULL,1,NULL,NULL,'2023-06-01','Active','Regular','Bistro Hospitality Group Inc.',NULL,'Direct','Bistro Hospitality Group Inc.','2023-12-01',NULL,NULL,NULL,NULL,'34-5678901-2',1,'Active','12-789012345-6',1,'Active','1210-5432-1098',1,'Active','678-901-234-000',1,NULL,NULL,NULL,NULL,NULL,NULL,0,45000.00,'Monthly','Monthly','Semi-Monthly',5000.00,NULL,NULL,'2026-09-23 10:41:27','2026-09-30 08:47:10'),(6,'EMP-2026-006',NULL,'Gabriel',NULL,'Tan',NULL,'Gabriel','1995-09-30','City of Manila, Philippines','Male','Single','Filipino','09171234506',NULL,'gabriel.tan@rusticrestaurant.ph','gabriel.tan@rms.ph','Comembo, Taguig City','Comembo, Taguig City',NULL,NULL,NULL,NULL,2,NULL,NULL,NULL,NULL,NULL,1,3,NULL,4,NULL,NULL,'2024-01-10','Active','Regular','Bistro Hospitality Group Inc.',NULL,'Direct','Bistro Hospitality Group Inc.','2024-07-10',NULL,NULL,NULL,NULL,'34-6789012-3',1,'Active','12-890123456-7',1,'Active','1210-4321-0987',1,'Active','789-012-345-000',1,NULL,NULL,NULL,NULL,NULL,NULL,0,22000.00,'Monthly','Monthly','Semi-Monthly',1500.00,NULL,NULL,'2026-09-23 10:41:27','2026-09-30 08:47:10'),(7,'EMP-2026-007',NULL,'Krizza',NULL,'Navarro',NULL,'Krizza','2000-05-18','City of Manila, Philippines','Female','Single','Filipino','09171234507',NULL,'krizza.navarro@rusticrestaurant.ph','krizza.navarro@rms.ph','East Rembo, Taguig City','East Rembo, Taguig City',NULL,NULL,NULL,NULL,2,NULL,NULL,NULL,NULL,NULL,3,2,NULL,9,NULL,NULL,'2026-05-01','Probationary','Probationary','Prime Hospitality Care Agency','Prime Hospitality Care Agency','Agency','Prime Hospitality Care Agency',NULL,NULL,NULL,'2026-05-01','2026-11-01','34-7890123-4',1,'Active','12-901234567-8',1,'Active','1210-3210-9876',1,'Active','890-123-456-000',1,NULL,NULL,NULL,NULL,NULL,NULL,0,18000.00,'Monthly','Monthly','Semi-Monthly',1000.00,NULL,NULL,'2026-09-23 10:41:27','2026-09-30 08:47:10'),(8,'EMP-2026-008',21,'Eduardo',NULL,'Ramos',NULL,'Eduardo','1993-12-08','City of Manila, Philippines','Male','Married','Filipino','09171234508',NULL,'eduardo.ramos@rusticrestaurant.ph','eduardo.ramos@rms.ph','Kamuning, Quezon City','Kamuning, Quezon City',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,1,1,NULL,1,NULL,NULL,'2023-08-01','Active','Regular','Bistro Hospitality Group Inc.',NULL,'Direct','Bistro Hospitality Group Inc.','2024-02-01',NULL,NULL,NULL,NULL,'34-8901234-5',1,'Active','12-012345678-9',1,'Active','1210-2109-8765',1,'Active','901-234-567-000',1,NULL,NULL,NULL,NULL,NULL,NULL,0,45000.00,'Monthly','Monthly','Semi-Monthly',5000.00,NULL,NULL,'2026-09-23 10:41:27','2026-09-30 08:47:10'),(9,'EMP-2026-009',NULL,'Patricia',NULL,'Lim',NULL,'Patricia','1999-03-25','City of Manila, Philippines','Female','Single','Filipino','09171234509',NULL,'patricia.lim@rusticrestaurant.ph','patricia.lim@rms.ph','Scout Rallos, Quezon City','Scout Rallos, Quezon City',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,2,2,NULL,8,NULL,NULL,'2024-04-10','Active','Regular','ABC Manpower & Staffing Services','ABC Manpower & Staffing Services','Agency','ABC Manpower & Staffing Services','2024-10-10',NULL,NULL,NULL,NULL,'34-9012345-6',1,'Active','12-123450987-1',1,'Active','1210-1098-7654',1,'Active','012-345-678-000',1,NULL,NULL,NULL,NULL,NULL,NULL,0,17500.00,'Monthly','Monthly','Semi-Monthly',1000.00,NULL,NULL,'2026-09-23 10:41:27','2026-09-30 08:47:10'),(10,'EMP-2026-010',NULL,'Mark',NULL,'Villanueva',NULL,'Mark','1997-06-15','City of Manila, Philippines','Male','Single','Filipino','09171234510',NULL,'mark.villanueva@rusticrestaurant.ph','mark.villanueva@rms.ph','Teachers Village, Quezon City','Teachers Village, Quezon City',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,3,3,NULL,6,NULL,NULL,'2025-01-15','Active','Regular','Prime Hospitality Care Agency','Prime Hospitality Care Agency','Agency','Prime Hospitality Care Agency','2025-07-15',NULL,NULL,NULL,NULL,'34-0123456-7',1,'Active','12-234561098-2',1,'Active','1210-0987-6543',1,'Active','123-789-456-000',1,NULL,NULL,NULL,NULL,NULL,NULL,0,16000.00,'Monthly','Monthly','Semi-Monthly',1000.00,NULL,NULL,'2026-09-23 10:41:27','2026-09-30 08:47:10'),(64,'EMP-2026-000',2,'Dorothy',NULL,'Diaz',NULL,'Dorothy','1985-06-15','City of Manila, Philippines','Female','Single','Filipino','09171234000',NULL,'dorothy@rms.local','dorothy.diaz@rms.ph','Legaspi Village, Makati City','Legaspi Village, Makati City',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,1,1,NULL,10,NULL,NULL,'2022-01-01','Active','Regular','Bistro Hospitality Group Inc.',NULL,'Direct','Bistro Hospitality Group Inc.','2022-07-01',NULL,NULL,NULL,NULL,'34-1111111-1',1,'Active','12-111111111-1',1,'Active','1210-1111-1111',1,'Active','111-222-333-000',1,NULL,NULL,NULL,NULL,NULL,NULL,0,75000.00,'Monthly','Monthly','Semi-Monthly',10000.00,NULL,NULL,'2026-09-24 09:37:10','2026-09-30 08:47:10'),(65,'EMP-2026-ADM',1,'Peter',NULL,'Administrator',NULL,'Peter','1989-10-10','City of Manila, Philippines','Male','Married','Filipino','09171234001',NULL,'admin@rms.local','peter.administrator@rms.ph','Salcedo Village, Makati City','Salcedo Village, Makati City',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,1,1,NULL,11,NULL,NULL,'2022-03-01','Active','Regular','Bistro Hospitality Group Inc.',NULL,'Direct','Bistro Hospitality Group Inc.','2022-09-01',NULL,NULL,NULL,NULL,'34-2222222-2',1,'Active','12-222222222-2',1,'Active','1210-2222-2222',1,'Active','222-333-444-000',1,NULL,NULL,NULL,NULL,NULL,NULL,0,60000.00,'Monthly','Monthly','Semi-Monthly',8000.00,NULL,NULL,'2026-09-24 09:37:10','2026-09-30 08:47:10'),(66,'EMP-2026-HRA',6,'Elena',NULL,'Reyes',NULL,'Elena','1991-03-20','City of Manila, Philippines','Female','Single','Filipino','09171234002',NULL,'hr@rusticrestaurant.ph','elena.reyes@rms.ph','San Lorenzo Village, Makati City','San Lorenzo Village, Makati City',NULL,NULL,NULL,NULL,1,'[1]',NULL,NULL,NULL,NULL,1,2,'[4,2]',12,'[12]',NULL,'2023-01-01','Active','Regular','Bistro Hospitality Group Inc.','ABC Manpower & Staffing Services','Company','Bistro Hospitality Group Inc.','2023-07-01',NULL,NULL,NULL,NULL,'34-3333333-3',1,'Active','12-333333333-3',1,'Active','1210-3333-3333',1,'Active','333-444-555-000',1,NULL,NULL,NULL,NULL,NULL,NULL,0,50000.00,'Monthly','Monthly','Semi-Monthly',6000.00,NULL,NULL,'2026-09-24 09:37:11','2026-09-30 08:47:10'),(67,'EMP-2026-CSH',3,'John',NULL,'Cashier',NULL,'John','1997-04-18','City of Manila, Philippines','Male','Single','Filipino','09171234003',NULL,'cashier@rms.local','john.cashier@rms.ph','Poblacion, Makati City','Poblacion, Makati City',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,1,2,NULL,7,NULL,NULL,'2024-03-01','Active','Regular','Bistro Hospitality Group Inc.',NULL,'Direct','Bistro Hospitality Group Inc.','2024-09-01',NULL,NULL,NULL,NULL,'34-3344556-7',1,'Active','12-334455667-8',1,'Active','1210-3344-5566',1,'Active','334-455-667-000',1,NULL,NULL,NULL,NULL,NULL,NULL,0,21000.00,'Monthly','Monthly','Semi-Monthly',1500.00,NULL,NULL,'2026-09-24 09:37:11','2026-09-30 08:47:10'),(68,'EMP-2026-STF',4,'Sarah',NULL,'Staff',NULL,'Sarah','2000-09-12','City of Manila, Philippines','Female','Single','Filipino','09171234004',NULL,'staff@rms.local','sarah.staff@rms.ph','Palanan, Makati City','Palanan, Makati City',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,2,2,NULL,8,NULL,NULL,'2025-02-01','Active','Contractual','ABC Manpower & Staffing Services','ABC Manpower & Staffing Services','Agency','ABC Manpower & Staffing Services',NULL,NULL,NULL,'2025-02-01','2026-12-31','34-4455667-8',1,'Active','12-445566778-9',1,'Active','1210-4455-6677',1,'Active','445-566-778-000',1,NULL,NULL,NULL,NULL,NULL,NULL,0,17500.00,'Monthly','Monthly','Semi-Monthly',1000.00,NULL,NULL,'2026-09-24 09:37:11','2026-09-30 08:47:10'),(69,'EMP-2026-KTC',5,'Marco',NULL,'Rossi',NULL,'Marco','1993-11-28','City of Manila, Philippines','Male','Single','Filipino','09171234005',NULL,'kitchen@rms.local','marco.rossi@rms.ph','Guadalupe Viejo, Makati City','Guadalupe Viejo, Makati City',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,1,3,NULL,4,NULL,NULL,'2023-09-15','Active','Regular','Bistro Hospitality Group Inc.',NULL,'Direct','Bistro Hospitality Group Inc.','2024-03-15',NULL,NULL,NULL,NULL,'34-5566778-9',1,'Active','12-556677889-0',1,'Active','1210-5566-7788',1,'Active','556-677-889-000',1,NULL,NULL,NULL,NULL,NULL,NULL,0,24000.00,'Monthly','Monthly','Semi-Monthly',2000.00,NULL,NULL,'2026-09-24 09:37:11','2026-09-30 08:47:10'),(602,'EMP-2026-507',NULL,'Miguel',NULL,'Torres',NULL,NULL,NULL,NULL,'Male','Single','Filipino','09175551234',NULL,'miguel.torres@email.com','miguel.torres@email.com','Signal Village, Taguig City','Signal Village, Taguig City',NULL,NULL,NULL,NULL,1,'[1]',NULL,NULL,NULL,'Staff',NULL,1,'[1]',1,'[1]',NULL,'2026-10-01','Probationary','Probationary',NULL,NULL,'Company',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'Active',NULL,0,'Active',NULL,0,'Active',NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,0,18000.00,'Daily','Daily','Semi-Monthly',0.00,NULL,NULL,'2026-09-30 19:16:45','2026-10-02 03:50:33');
/*!40000 ALTER TABLE `hr_employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_employment_histories`
--

DROP TABLE IF EXISTS `hr_employment_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_employment_histories` (
  `employment_history_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `previous_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `effective_date` date NOT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`employment_history_id`),
  KEY `employment_histories_employee_id_foreign` (`employee_id`),
  KEY `employment_histories_recorded_by_foreign` (`recorded_by`),
  CONSTRAINT `employment_histories_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`employee_id`) ON DELETE CASCADE,
  CONSTRAINT `employment_histories_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=336 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_employment_histories`
--

LOCK TABLES `hr_employment_histories` WRITE;
/*!40000 ALTER TABLE `hr_employment_histories` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr_employment_histories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_holiday_locations`
--

DROP TABLE IF EXISTS `hr_holiday_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_holiday_locations` (
  `holiday_location_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `holiday_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `city_municipality` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`holiday_location_id`),
  KEY `hr_holiday_locations_holiday_id_foreign` (`holiday_id`),
  CONSTRAINT `hr_holiday_locations_holiday_id_foreign` FOREIGN KEY (`holiday_id`) REFERENCES `hr_holidays` (`holiday_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_holiday_locations`
--

LOCK TABLES `hr_holiday_locations` WRITE;
/*!40000 ALTER TABLE `hr_holiday_locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr_holiday_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_holiday_pay_rules`
--

DROP TABLE IF EXISTS `hr_holiday_pay_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_holiday_pay_rules` (
  `holiday_pay_rule_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `holiday_type` varchar(50) NOT NULL,
  `year` smallint(5) unsigned DEFAULT NULL,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `unworked_rate` decimal(6,2) NOT NULL DEFAULT 100.00,
  `worked_rate` decimal(6,2) NOT NULL DEFAULT 200.00,
  `rest_day_worked_rate` decimal(6,2) NOT NULL DEFAULT 260.00,
  `overtime_multiplier` decimal(6,2) NOT NULL DEFAULT 1.30,
  `rest_day_overtime_multiplier` decimal(6,2) NOT NULL DEFAULT 1.69,
  `calculation_rule_description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`holiday_pay_rule_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_holiday_pay_rules`
--

LOCK TABLES `hr_holiday_pay_rules` WRITE;
/*!40000 ALTER TABLE `hr_holiday_pay_rules` DISABLE KEYS */;
INSERT INTO `hr_holiday_pay_rules` VALUES (1,'Regular Holiday',2026,NULL,NULL,100.00,200.00,260.00,1.30,1.69,'Unworked: 100% of basic daily wage. Worked: 200% for first 8 hrs + 30% of hourly rate for OT. If rest day: 260% + 30% OT.',1,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(2,'Special Non-Working',2026,NULL,NULL,0.00,130.00,150.00,1.30,1.69,'Unworked: \"No work, no pay\" unless company policy or CBA states otherwise. Worked: 130% for first 8 hrs. If rest day: 150%. OT: +30% of day hourly rate.',1,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(3,'Special Working',2026,NULL,NULL,100.00,100.00,130.00,1.25,1.69,'Entitled only to basic wage rate. If rest day: 130%.',1,'2026-10-02 03:56:24','2026-10-02 03:56:24');
/*!40000 ALTER TABLE `hr_holiday_pay_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_holidays`
--

DROP TABLE IF EXISTS `hr_holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_holidays` (
  `holiday_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `date` date NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `holiday_type` varchar(50) NOT NULL DEFAULT 'Regular Holiday',
  `scope` varchar(50) NOT NULL DEFAULT 'Nationwide',
  `region` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `city_municipality` varchar(100) DEFAULT NULL,
  `applicable_branches` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_branches`)),
  `payroll_treatment` varchar(255) DEFAULT NULL,
  `official_reference` varchar(255) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`holiday_id`),
  KEY `hr_holidays_year_holiday_type_index` (`year`,`holiday_type`),
  KEY `hr_holidays_date_is_active_index` (`date`,`is_active`),
  KEY `hr_holidays_year_index` (`year`)
) ENGINE=InnoDB AUTO_INCREMENT=435 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_holidays`
--

LOCK TABLES `hr_holidays` WRITE;
/*!40000 ALTER TABLE `hr_holidays` DISABLE KEYS */;
INSERT INTO `hr_holidays` VALUES (1,'New Year\'s Day','2026-01-01',2026,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked (+30% rest day)','Proclamation No. 727, DOLE Advisory','Office of the President','First day of the Gregorian year celebration.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(2,'Chinese Lunar New Year','2026-02-17',2026,'Special Non-Working','Nationwide',NULL,NULL,NULL,NULL,'No work, no pay / 130% worked (+50% rest day)','Proclamation No. 727','Office of the President','Spring Festival celebrations and cultural holiday.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(3,'Maundy Thursday','2026-04-02',2026,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked','Proclamation No. 727','DOLE Labor Code Art. 94','Holy Week solemn observation.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(4,'Good Friday','2026-04-03',2026,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked','Proclamation No. 727','DOLE Labor Code Art. 94','Holy Week observance.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(5,'Black Saturday','2026-04-04',2026,'Special Non-Working','Nationwide',NULL,NULL,NULL,NULL,'No work no pay / 130% worked','Proclamation No. 727','Office of the President','Easter Vigil observation.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(6,'Araw ng Kagitingan (Day of Valor)','2026-04-09',2026,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked','Proclamation No. 727 / RA 3022','National Government','Commemoration of the Fall of Bataan heroics.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(7,'Labor Day','2026-05-01',2026,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked','Labor Code Art. 94','DOLE National Statutory','National tribute to Filipino workers and labor force.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(8,'Independence Day','2026-06-12',2026,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked','RA 4166 / Proclamation No. 727','National Government','Philippine Declaration of Independence.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(9,'Ninoy Aquino Day','2026-08-21',2026,'Special Non-Working','Nationwide',NULL,NULL,NULL,NULL,'No work no pay / 130% worked','RA 9256','Office of the President','Commemoration of the martyrdom of Senator Benigno Aquino Jr.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(10,'National Heroes Day','2026-08-31',2026,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked','RA 9492 / Proclamation No. 727','National Government','Last Monday of August honoring all heroes.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(11,'All Saints\' Day','2026-11-01',2026,'Special Non-Working','Nationwide',NULL,NULL,NULL,NULL,'No work no pay / 130% worked (+50% rest day)','Proclamation No. 727','Office of the President','Solemn remembrance of departed loved ones.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(12,'All Souls Day Special Working Day','2026-11-02',2026,'Special Working','Nationwide',NULL,NULL,NULL,NULL,'Standard daily rate 100%','Proclamation No. 727','Office of the President','Designated special working day.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(13,'Bonifacio Day','2026-11-30',2026,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked','Act No. 2946 / Proclamation No. 727','National Government','Birth anniversary of Andres Bonifacio, Father of Katipunan.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(14,'Feast of the Immaculate Conception','2026-12-08',2026,'Special Non-Working','Nationwide',NULL,NULL,NULL,NULL,'No work no pay / 130% worked','RA 10966','National Government','Principal Patroness of the Philippines.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(15,'Christmas Eve','2026-12-24',2026,'Special Non-Working','Nationwide',NULL,NULL,NULL,NULL,'No work no pay / 130% worked','Proclamation No. 727','Office of the President','Traditional Philippine holiday preparation for Noche Buena.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(16,'Christmas Day','2026-12-25',2026,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked','Proclamation No. 727','Office of the President','Nativity of Jesus Christ.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(17,'Rizal Day','2026-12-30',2026,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked','Decree of Dec 20, 1898 / Proclamation No. 727','National Government','Martyrdom anniversary of Dr. Jose P. Rizal.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(18,'New Year\'s Day','2027-01-01',2027,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked','DOLE 2027 Advisory','DOLE Advisory No. 01-2027','New Year celebration and public holiday.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(19,'Chinese New Year','2027-02-06',2027,'Special Non-Working','Nationwide',NULL,NULL,NULL,NULL,'130% worked / 150% on rest day','DOLE 2027 Advisory','Office of the President','Lunar New Year celebrations.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(20,'Maundy Thursday','2027-03-25',2027,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked','DOLE 2027 Advisory','DOLE Labor Code','Holy Week Maundy Thursday.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(21,'Good Friday','2027-03-26',2027,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked','DOLE 2027 Advisory','DOLE Labor Code','Holy Week Good Friday.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(22,'Black Saturday','2027-03-27',2027,'Special Non-Working','Nationwide',NULL,NULL,NULL,NULL,'130% worked / 150% on rest day','DOLE 2027 Advisory','Office of the President','Holy Saturday before Easter Sunday.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(23,'Araw ng Kagitingan','2027-04-09',2027,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked','DOLE 2027 Advisory','National Government','Day of Valor national holiday.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(24,'Labor Day','2027-05-01',2027,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked','DOLE 2027 Advisory','DOLE','National Labor Day.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(25,'Independence Day','2027-06-12',2027,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked','DOLE 2027 Advisory','National Government','129th Philippine Independence Day.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(26,'Ninoy Aquino Day','2027-08-21',2027,'Special Non-Working','Nationwide',NULL,NULL,NULL,NULL,'130% worked / 150% on rest day','DOLE 2027 Advisory','Office of the President','Ninoy Aquino Day memorial.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(27,'National Heroes Day','2027-08-30',2027,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked','DOLE 2027 Advisory','National Government','National Heroes Day celebration.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(28,'All Saints\' Day','2027-11-01',2027,'Special Non-Working','Nationwide',NULL,NULL,NULL,NULL,'130% worked / 150% on rest day','DOLE 2027 Advisory','Office of the President','All Saints Day observances.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(29,'All Souls Day Special Working Day','2027-11-02',2027,'Special Working','Nationwide',NULL,NULL,NULL,NULL,'100% standard rate (no premium)','DOLE 2027 Advisory','Office of the President','DOLE 2027 designated special working day.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(30,'Bonifacio Day','2027-11-30',2027,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked','DOLE 2027 Advisory','National Government','Andres Bonifacio birthday commemorative.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(31,'Feast of the Immaculate Conception','2027-12-08',2027,'Special Non-Working','Nationwide',NULL,NULL,NULL,NULL,'130% worked / 150% on rest day','DOLE 2027 Advisory','National Government','Feast of the Immaculate Conception of Mary.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(32,'Christmas Eve','2027-12-24',2027,'Special Non-Working','Nationwide',NULL,NULL,NULL,NULL,'130% worked / 150% on rest day','DOLE 2027 Advisory','Office of the President','Eve of Christmas Day.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(33,'Christmas Day','2027-12-25',2027,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked','DOLE 2027 Advisory','Office of the President','Christmas Day.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(34,'Rizal Day','2027-12-30',2027,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked','DOLE 2027 Advisory','National Government','Rizal Day national hero commemoration.',1,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(417,'New Year\'s Day','2028-01-01',2028,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked (+30% rest day)','DOLE Statutory Holiday Proclamation for 2028',NULL,NULL,1,NULL,NULL,'2026-10-09 08:29:22','2026-10-09 08:29:22'),(418,'Chinese New Year','2028-02-10',2028,'Special Non-Working','Nationwide',NULL,NULL,NULL,NULL,'No work no pay / 130% worked (+50% rest day)','DOLE Statutory Holiday Proclamation for 2028',NULL,NULL,1,NULL,NULL,'2026-10-09 08:29:22','2026-10-09 08:29:22'),(419,'EDSA People Power Anniversary','2028-02-25',2028,'Special Non-Working','Nationwide',NULL,NULL,NULL,NULL,'No work no pay / 130% worked (+50% rest day)','DOLE Statutory Holiday Proclamation for 2028',NULL,NULL,1,NULL,NULL,'2026-10-09 08:29:22','2026-10-09 08:29:22'),(420,'Maundy Thursday','2028-04-01',2028,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked (+30% rest day)','DOLE Statutory Holiday Proclamation for 2028',NULL,NULL,1,NULL,NULL,'2026-10-09 08:29:22','2026-10-09 08:29:22'),(421,'Good Friday','2028-04-02',2028,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked (+30% rest day)','DOLE Statutory Holiday Proclamation for 2028',NULL,NULL,1,NULL,NULL,'2026-10-09 08:29:22','2026-10-09 08:29:22'),(422,'Black Saturday','2028-04-03',2028,'Special Non-Working','Nationwide',NULL,NULL,NULL,NULL,'No work no pay / 130% worked (+50% rest day)','DOLE Statutory Holiday Proclamation for 2028',NULL,NULL,1,NULL,NULL,'2026-10-09 08:29:22','2026-10-09 08:29:22'),(423,'Araw ng Kagitingan','2028-04-09',2028,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked (+30% rest day)','DOLE Statutory Holiday Proclamation for 2028',NULL,NULL,1,NULL,NULL,'2026-10-09 08:29:22','2026-10-09 08:29:22'),(424,'Labor Day','2028-05-01',2028,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked (+30% rest day)','DOLE Statutory Holiday Proclamation for 2028',NULL,NULL,1,NULL,NULL,'2026-10-09 08:29:22','2026-10-09 08:29:22'),(425,'Independence Day','2028-06-12',2028,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked (+30% rest day)','DOLE Statutory Holiday Proclamation for 2028',NULL,NULL,1,NULL,NULL,'2026-10-09 08:29:22','2026-10-09 08:29:22'),(426,'Ninoy Aquino Day','2028-08-21',2028,'Special Non-Working','Nationwide',NULL,NULL,NULL,NULL,'No work no pay / 130% worked (+50% rest day)','DOLE Statutory Holiday Proclamation for 2028',NULL,NULL,1,NULL,NULL,'2026-10-09 08:29:22','2026-10-09 08:29:22'),(427,'National Heroes Day','2028-08-31',2028,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked (+30% rest day)','DOLE Statutory Holiday Proclamation for 2028',NULL,NULL,1,NULL,NULL,'2026-10-09 08:29:22','2026-10-09 08:29:22'),(428,'All Saints\' Day','2028-11-01',2028,'Special Non-Working','Nationwide',NULL,NULL,NULL,NULL,'No work no pay / 130% worked (+50% rest day)','DOLE Statutory Holiday Proclamation for 2028',NULL,NULL,1,NULL,NULL,'2026-10-09 08:29:22','2026-10-09 08:29:22'),(429,'All Souls\' Day','2028-11-02',2028,'Special Working','Nationwide',NULL,NULL,NULL,NULL,'100% standard rate (no premium)','DOLE Statutory Holiday Proclamation for 2028',NULL,NULL,1,NULL,NULL,'2026-10-09 08:29:22','2026-10-09 08:29:22'),(430,'Bonifacio Day','2028-11-30',2028,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked (+30% rest day)','DOLE Statutory Holiday Proclamation for 2028',NULL,NULL,1,NULL,NULL,'2026-10-09 08:29:22','2026-10-09 08:29:22'),(431,'Feast of the Immaculate Conception','2028-12-08',2028,'Special Non-Working','Nationwide',NULL,NULL,NULL,NULL,'No work no pay / 130% worked (+50% rest day)','DOLE Statutory Holiday Proclamation for 2028',NULL,NULL,1,NULL,NULL,'2026-10-09 08:29:22','2026-10-09 08:29:22'),(432,'Christmas Day','2028-12-25',2028,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked (+30% rest day)','DOLE Statutory Holiday Proclamation for 2028',NULL,NULL,1,NULL,NULL,'2026-10-09 08:29:22','2026-10-09 08:29:22'),(433,'Rizal Day','2028-12-30',2028,'Regular Holiday','Nationwide',NULL,NULL,NULL,NULL,'100% unworked / 200% worked (+30% rest day)','DOLE Statutory Holiday Proclamation for 2028',NULL,NULL,1,NULL,NULL,'2026-10-09 08:29:22','2026-10-09 08:29:22'),(434,'Last Day of the Year','2028-12-31',2028,'Special Non-Working','Nationwide',NULL,NULL,NULL,NULL,'No work no pay / 130% worked (+50% rest day)','DOLE Statutory Holiday Proclamation for 2028',NULL,NULL,1,NULL,NULL,'2026-10-09 08:29:22','2026-10-09 08:29:22');
/*!40000 ALTER TABLE `hr_holidays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_interviews`
--

DROP TABLE IF EXISTS `hr_interviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_interviews` (
  `interview_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `applicant_id` bigint(20) unsigned NOT NULL,
  `interview_date` datetime NOT NULL,
  `interview_time` varchar(20) DEFAULT NULL,
  `interviewer_id` bigint(20) unsigned DEFAULT NULL,
  `interviewer_name` varchar(100) DEFAULT NULL,
  `location_or_link` varchar(255) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `interview_type` varchar(50) NOT NULL DEFAULT 'Phone',
  `interview_stage` varchar(50) NOT NULL DEFAULT 'HR Interview',
  `notes` text DEFAULT NULL,
  `rating` tinyint(3) unsigned DEFAULT NULL,
  `recommendation` text DEFAULT NULL,
  `scorecard` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`scorecard`)),
  `status` varchar(50) NOT NULL DEFAULT 'Scheduled',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`interview_id`),
  KEY `interviews_applicant_id_foreign` (`applicant_id`),
  KEY `interviews_interviewer_id_foreign` (`interviewer_id`),
  CONSTRAINT `interviews_applicant_id_foreign` FOREIGN KEY (`applicant_id`) REFERENCES `hr_applicants` (`applicant_id`) ON DELETE CASCADE,
  CONSTRAINT `interviews_interviewer_id_foreign` FOREIGN KEY (`interviewer_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_interviews`
--

LOCK TABLES `hr_interviews` WRITE;
/*!40000 ALTER TABLE `hr_interviews` DISABLE KEYS */;
INSERT INTO `hr_interviews` VALUES (1,1,'2026-09-25 15:00:00',NULL,8,'Beatriz Valdez',NULL,NULL,'Technical / Practical','HR Interview','Practical cooking demonstration (knife skills and line coordination).',NULL,NULL,NULL,'Scheduled','2026-09-23 10:41:27','2026-09-23 10:41:27');
/*!40000 ALTER TABLE `hr_interviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_job_levels`
--

DROP TABLE IF EXISTS `hr_job_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_job_levels` (
  `job_level_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `level` int(10) unsigned NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`job_level_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_job_levels`
--

LOCK TABLES `hr_job_levels` WRITE;
/*!40000 ALTER TABLE `hr_job_levels` DISABLE KEYS */;
INSERT INTO `hr_job_levels` VALUES (1,'Executive / GM',1,'Top restaurant management','2026-09-23 10:40:49','2026-09-23 10:40:49'),(2,'Supervisory / Shift Lead',2,'Shift supervisors and lead cooks','2026-09-23 10:40:49','2026-09-23 10:40:49'),(3,'Rank and File',3,'Kitchen helpers, servers, cashiers','2026-09-23 10:40:49','2026-09-23 10:40:49');
/*!40000 ALTER TABLE `hr_job_levels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_job_vacancies`
--

DROP TABLE IF EXISTS `hr_job_vacancies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_job_vacancies` (
  `job_vacancy_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `position_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `number_of_openings` int(10) unsigned NOT NULL DEFAULT 1,
  `employment_type` enum('Regular','Probationary','Part-time','Casual','Contractual') NOT NULL DEFAULT 'Regular',
  `work_setup` varchar(30) NOT NULL DEFAULT 'On-site',
  `salary_range_min` decimal(10,2) DEFAULT NULL,
  `salary_range_max` decimal(10,2) DEFAULT NULL,
  `job_description` text DEFAULT NULL,
  `responsibilities` text DEFAULT NULL,
  `qualifications` text DEFAULT NULL,
  `required_skills` text DEFAULT NULL,
  `preferred_skills` text DEFAULT NULL,
  `benefits` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `opening_date` date NOT NULL,
  `closing_date` date DEFAULT NULL,
  `hiring_manager_id` bigint(20) unsigned DEFAULT NULL,
  `hiring_manager_name` varchar(100) DEFAULT NULL,
  `recruiter_id` bigint(20) unsigned DEFAULT NULL,
  `recruiter_name` varchar(100) DEFAULT NULL,
  `application_questions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`application_questions`)),
  `status` enum('Draft','Open','On Hold','Closed','Filled') NOT NULL DEFAULT 'Open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`job_vacancy_id`),
  KEY `job_vacancies_position_id_foreign` (`position_id`),
  KEY `job_vacancies_department_id_foreign` (`department_id`),
  KEY `job_vacancies_branch_id_foreign` (`branch_id`),
  CONSTRAINT `job_vacancies_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `hr_branches` (`branch_id`) ON DELETE SET NULL,
  CONSTRAINT `job_vacancies_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`department_id`) ON DELETE SET NULL,
  CONSTRAINT `job_vacancies_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `hr_positions` (`position_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_job_vacancies`
--

LOCK TABLES `hr_job_vacancies` WRITE;
/*!40000 ALTER TABLE `hr_job_vacancies` DISABLE KEYS */;
INSERT INTO `hr_job_vacancies` VALUES (1,'Line Cook / Griller',4,3,2,2,'Regular','On-site',20000.00,25000.00,'Responsible for line preparation, grilling, and compliance with kitchen ticket times.',NULL,NULL,NULL,NULL,NULL,'Minimum 1 year culinary experience in commercial restaurants. NC II Cookery preferred.','2026-09-01',NULL,NULL,NULL,NULL,NULL,NULL,'Open','2026-09-23 10:41:27','2026-09-23 10:41:27');
/*!40000 ALTER TABLE `hr_job_vacancies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_leave_balances`
--

DROP TABLE IF EXISTS `hr_leave_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_leave_balances` (
  `leave_balance_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `leave_type_id` bigint(20) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `beginning_balance` decimal(5,2) NOT NULL DEFAULT 0.00,
  `earned` decimal(5,2) NOT NULL DEFAULT 0.00,
  `used` decimal(5,2) NOT NULL DEFAULT 0.00,
  `remaining` decimal(5,2) NOT NULL DEFAULT 0.00,
  `encashed` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`leave_balance_id`),
  UNIQUE KEY `leave_balances_employee_id_leave_type_id_year_unique` (`employee_id`,`leave_type_id`,`year`),
  KEY `leave_balances_leave_type_id_foreign` (`leave_type_id`),
  CONSTRAINT `leave_balances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`employee_id`) ON DELETE CASCADE,
  CONSTRAINT `leave_balances_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `hr_leave_types` (`leave_type_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_leave_balances`
--

LOCK TABLES `hr_leave_balances` WRITE;
/*!40000 ALTER TABLE `hr_leave_balances` DISABLE KEYS */;
INSERT INTO `hr_leave_balances` VALUES (1,1,1,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-23 10:41:27','2026-09-24 09:18:07'),(2,1,2,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(3,1,3,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(4,1,4,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(5,1,5,2026,105.00,0.00,0.00,105.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(6,1,6,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(7,1,7,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(8,1,8,2026,3.00,0.00,0.00,3.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(9,1,9,2026,0.00,0.00,0.00,0.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(10,2,1,2026,15.00,0.00,2.00,13.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(11,2,2,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(12,2,3,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(13,2,4,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(14,2,5,2026,105.00,0.00,0.00,105.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(15,2,6,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(16,2,7,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(17,2,8,2026,3.00,0.00,0.00,3.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(18,2,9,2026,0.00,0.00,0.00,0.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(19,3,1,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(20,3,2,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(21,3,3,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(22,3,4,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(23,3,5,2026,105.00,0.00,0.00,105.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(24,3,6,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(25,3,7,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(26,3,8,2026,3.00,0.00,0.00,3.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(27,3,9,2026,0.00,0.00,0.00,0.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(28,4,1,2026,15.00,0.00,2.00,13.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(29,4,2,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(30,4,3,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(31,4,4,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(32,4,5,2026,105.00,0.00,0.00,105.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(33,4,6,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(34,4,7,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(35,4,8,2026,3.00,0.00,0.00,3.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(36,4,9,2026,0.00,0.00,0.00,0.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(37,5,1,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(38,5,2,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(39,5,3,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(40,5,4,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(41,5,5,2026,105.00,0.00,0.00,105.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(42,5,6,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(43,5,7,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(44,5,8,2026,3.00,0.00,0.00,3.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(45,5,9,2026,0.00,0.00,0.00,0.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(46,6,1,2026,15.00,0.00,2.00,13.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(47,6,2,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(48,6,3,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(49,6,4,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(50,6,5,2026,105.00,0.00,0.00,105.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(51,6,6,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(52,6,7,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(53,6,8,2026,3.00,0.00,0.00,3.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(54,6,9,2026,0.00,0.00,0.00,0.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(55,7,1,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(56,7,2,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(57,7,3,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(58,7,4,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(59,7,5,2026,105.00,0.00,0.00,105.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(60,7,6,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(61,7,7,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(62,7,8,2026,3.00,0.00,0.00,3.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(63,7,9,2026,0.00,0.00,0.00,0.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(64,8,1,2026,15.00,0.00,2.00,13.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(65,8,2,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(66,8,3,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(67,8,4,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(68,8,5,2026,105.00,0.00,0.00,105.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(69,8,6,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(70,8,7,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(71,8,8,2026,3.00,0.00,0.00,3.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(72,8,9,2026,0.00,0.00,0.00,0.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(73,9,1,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(74,9,2,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(75,9,3,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(76,9,4,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(77,9,5,2026,105.00,0.00,0.00,105.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(78,9,6,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(79,9,7,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(80,9,8,2026,3.00,0.00,0.00,3.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(81,9,9,2026,0.00,0.00,0.00,0.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(82,10,1,2026,15.00,0.00,2.00,13.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(83,10,2,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(84,10,3,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(85,10,4,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(86,10,5,2026,105.00,0.00,0.00,105.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(87,10,6,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(88,10,7,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(89,10,8,2026,3.00,0.00,0.00,3.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(90,10,9,2026,0.00,0.00,0.00,0.00,0.00,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(91,64,1,2026,15.00,0.00,2.00,13.00,0.00,'2026-09-24 09:37:10','2026-09-24 09:37:10'),(92,64,2,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-24 09:37:10','2026-09-24 09:37:10'),(93,64,3,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-24 09:37:10','2026-09-24 09:37:10'),(94,64,4,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-24 09:37:10','2026-09-24 09:37:10'),(95,64,5,2026,105.00,0.00,0.00,105.00,0.00,'2026-09-24 09:37:10','2026-09-24 09:37:10'),(96,64,6,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-24 09:37:10','2026-09-24 09:37:10'),(97,64,7,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-24 09:37:10','2026-09-24 09:37:10'),(98,64,8,2026,3.00,0.00,0.00,3.00,0.00,'2026-09-24 09:37:10','2026-09-24 09:37:10'),(99,64,9,2026,0.00,0.00,0.00,0.00,0.00,'2026-09-24 09:37:10','2026-09-24 09:37:10'),(100,65,1,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-24 09:37:10','2026-09-24 09:37:10'),(101,65,2,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-24 09:37:10','2026-09-24 09:37:10'),(102,65,3,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-24 09:37:10','2026-09-24 09:37:10'),(103,65,4,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(104,65,5,2026,105.00,0.00,0.00,105.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(105,65,6,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(106,65,7,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(107,65,8,2026,3.00,0.00,0.00,3.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(108,65,9,2026,0.00,0.00,0.00,0.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(109,66,1,2026,15.00,0.00,2.00,13.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(110,66,2,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(111,66,3,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(112,66,4,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(113,66,5,2026,105.00,0.00,0.00,105.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(114,66,6,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(115,66,7,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(116,66,8,2026,3.00,0.00,0.00,3.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(117,66,9,2026,0.00,0.00,0.00,0.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(118,67,1,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(119,67,2,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(120,67,3,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(121,67,4,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(122,67,5,2026,105.00,0.00,0.00,105.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(123,67,6,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(124,67,7,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(125,67,8,2026,3.00,0.00,0.00,3.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(126,67,9,2026,0.00,0.00,0.00,0.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(127,68,1,2026,15.00,0.00,2.00,13.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(128,68,2,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(129,68,3,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(130,68,4,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(131,68,5,2026,105.00,0.00,0.00,105.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(132,68,6,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(133,68,7,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(134,68,8,2026,3.00,0.00,0.00,3.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(135,68,9,2026,0.00,0.00,0.00,0.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(136,69,1,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(137,69,2,2026,15.00,0.00,0.00,15.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(138,69,3,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(139,69,4,2026,5.00,0.00,0.00,5.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(140,69,5,2026,105.00,0.00,0.00,105.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(141,69,6,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(142,69,7,2026,7.00,0.00,0.00,7.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(143,69,8,2026,3.00,0.00,0.00,3.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(144,69,9,2026,0.00,0.00,0.00,0.00,0.00,'2026-09-24 09:37:11','2026-09-24 09:37:11');
/*!40000 ALTER TABLE `hr_leave_balances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_leave_requests`
--

DROP TABLE IF EXISTS `hr_leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_leave_requests` (
  `leave_request_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `leave_type_id` bigint(20) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `number_of_days` decimal(4,1) NOT NULL DEFAULT 1.0,
  `reason` text NOT NULL,
  `supporting_document` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
  `approver_id` bigint(20) unsigned DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`leave_request_id`),
  KEY `leave_requests_employee_id_foreign` (`employee_id`),
  KEY `leave_requests_leave_type_id_foreign` (`leave_type_id`),
  KEY `leave_requests_approver_id_foreign` (`approver_id`),
  CONSTRAINT `leave_requests_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `leave_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`employee_id`) ON DELETE CASCADE,
  CONSTRAINT `leave_requests_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `hr_leave_types` (`leave_type_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_leave_requests`
--

LOCK TABLES `hr_leave_requests` WRITE;
/*!40000 ALTER TABLE `hr_leave_requests` DISABLE KEYS */;
INSERT INTO `hr_leave_requests` VALUES (1,3,1,'2026-09-22','2026-09-23',2.0,'Annual family vacation leave',NULL,'Approved',7,'2026-09-20 06:00:00',NULL,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(2,4,1,'2026-09-27','2026-09-27',1.0,'Personal errand and rest',NULL,'Pending',NULL,NULL,NULL,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(3,66,1,'2026-09-22','2026-09-23',2.0,'Annual family vacation leave',NULL,'Approved',7,'2026-09-20 06:00:00',NULL,'2026-09-24 09:37:11','2026-09-24 09:37:11'),(4,1,1,'2026-09-27','2026-09-27',1.0,'Personal errand and rest',NULL,'Pending',NULL,NULL,NULL,'2026-09-24 09:37:11','2026-09-24 09:37:11');
/*!40000 ALTER TABLE `hr_leave_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_leave_types`
--

DROP TABLE IF EXISTS `hr_leave_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_leave_types` (
  `leave_type_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(30) NOT NULL,
  `description` text DEFAULT NULL,
  `is_paid` tinyint(1) NOT NULL DEFAULT 1,
  `default_credits` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_cumulative` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`leave_type_id`),
  UNIQUE KEY `leave_types_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_leave_types`
--

LOCK TABLES `hr_leave_types` WRITE;
/*!40000 ALTER TABLE `hr_leave_types` DISABLE KEYS */;
INSERT INTO `hr_leave_types` VALUES (1,'Vacation Leave','VL',NULL,1,15.00,0,'2026-09-23 10:40:59','2026-09-23 10:40:59'),(2,'Sick Leave','SL',NULL,1,15.00,0,'2026-09-23 10:40:59','2026-09-23 10:40:59'),(3,'Emergency Leave','EL',NULL,1,5.00,0,'2026-09-23 10:40:59','2026-09-23 10:40:59'),(4,'Service Incentive Leave','SIL',NULL,1,5.00,0,'2026-09-23 10:40:59','2026-09-23 10:40:59'),(5,'Maternity Leave','ML',NULL,1,105.00,0,'2026-09-23 10:40:59','2026-09-23 10:40:59'),(6,'Paternity Leave','PL',NULL,1,7.00,0,'2026-09-23 10:40:59','2026-09-23 10:40:59'),(7,'Solo Parent Leave','SPL',NULL,1,7.00,0,'2026-09-23 10:40:59','2026-09-23 10:40:59'),(8,'Bereavement Leave','BL',NULL,1,3.00,0,'2026-09-23 10:40:59','2026-09-23 10:40:59'),(9,'Other Company Leave','OCL',NULL,0,0.00,0,'2026-09-23 10:40:59','2026-09-23 10:40:59');
/*!40000 ALTER TABLE `hr_leave_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_payroll_adjustments`
--

DROP TABLE IF EXISTS `hr_payroll_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_payroll_adjustments` (
  `payroll_adjustment_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payroll_record_id` bigint(20) unsigned NOT NULL,
  `adjustment_type` enum('Earning','Deduction') NOT NULL,
  `name` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`payroll_adjustment_id`),
  KEY `payroll_adjustments_payroll_record_id_foreign` (`payroll_record_id`),
  KEY `payroll_adjustments_approved_by_foreign` (`approved_by`),
  CONSTRAINT `payroll_adjustments_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_adjustments_payroll_record_id_foreign` FOREIGN KEY (`payroll_record_id`) REFERENCES `hr_payroll_records` (`payroll_record_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_payroll_adjustments`
--

LOCK TABLES `hr_payroll_adjustments` WRITE;
/*!40000 ALTER TABLE `hr_payroll_adjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr_payroll_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_payroll_periods`
--

DROP TABLE IF EXISTS `hr_payroll_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_payroll_periods` (
  `payroll_period_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `payout_date` date NOT NULL,
  `pay_frequency` enum('Semi-Monthly','Monthly','Weekly') NOT NULL DEFAULT 'Semi-Monthly',
  `status` enum('Draft','Review','Approved','Finalized') NOT NULL DEFAULT 'Draft',
  `processed_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `finalized_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`payroll_period_id`),
  KEY `payroll_periods_processed_by_foreign` (`processed_by`),
  KEY `payroll_periods_approved_by_foreign` (`approved_by`),
  CONSTRAINT `payroll_periods_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_periods_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_payroll_periods`
--

LOCK TABLES `hr_payroll_periods` WRITE;
/*!40000 ALTER TABLE `hr_payroll_periods` DISABLE KEYS */;
INSERT INTO `hr_payroll_periods` VALUES (1,'September 2026 - 1st Half','2026-09-01','2026-09-15','2026-09-20','Semi-Monthly','Approved',6,1,NULL,'2026-09-23 10:41:27','2026-09-23 10:41:27'),(2,'Test Finalized Period','2026-08-01','2026-08-15','2026-08-20','Semi-Monthly','Finalized',NULL,NULL,NULL,'2026-09-23 10:55:19','2026-09-23 10:55:19');
/*!40000 ALTER TABLE `hr_payroll_periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_payroll_records`
--

DROP TABLE IF EXISTS `hr_payroll_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_payroll_records` (
  `payroll_record_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payroll_period_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `basic_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_work_days` decimal(4,1) NOT NULL DEFAULT 0.0,
  `total_hours` decimal(6,2) NOT NULL DEFAULT 0.00,
  `regular_hours_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `overtime_hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `overtime_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `night_diff_hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `night_diff_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `holiday_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `rest_day_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `premium_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `premium_pay_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`premium_pay_details`)),
  `allowances` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bonuses` decimal(10,2) NOT NULL DEFAULT 0.00,
  `other_earnings` decimal(10,2) NOT NULL DEFAULT 0.00,
  `gross_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `late_deduction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `undertime_deduction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `absence_deduction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sss_employee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sss_employer` decimal(10,2) NOT NULL DEFAULT 0.00,
  `philhealth_employee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `philhealth_employer` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pagibig_employee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pagibig_employer` decimal(10,2) NOT NULL DEFAULT 0.00,
  `withholding_tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `salary_advance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `employee_loan` decimal(10,2) NOT NULL DEFAULT 0.00,
  `other_deductions` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('Draft','Approved','Finalized') NOT NULL DEFAULT 'Draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`payroll_record_id`),
  UNIQUE KEY `payroll_records_payroll_period_id_employee_id_unique` (`payroll_period_id`,`employee_id`),
  KEY `payroll_records_employee_id_foreign` (`employee_id`),
  CONSTRAINT `payroll_records_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`employee_id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_records_payroll_period_id_foreign` FOREIGN KEY (`payroll_period_id`) REFERENCES `hr_payroll_periods` (`payroll_period_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_payroll_records`
--

LOCK TABLES `hr_payroll_records` WRITE;
/*!40000 ALTER TABLE `hr_payroll_records` DISABLE KEYS */;
INSERT INTO `hr_payroll_records` VALUES (1,1,1,22500.00,0.0,0.00,22500.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,NULL,2500.00,0.00,0.00,25000.00,0.00,0.00,0.00,675.00,1425.00,562.50,562.50,100.00,100.00,2336.60,0.00,0.00,0.00,3674.10,21325.90,'Draft','2026-09-23 10:41:27','2026-09-23 10:41:27'),(2,1,2,16000.00,0.0,0.00,16000.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,NULL,1500.00,0.00,0.00,17500.00,0.00,0.00,0.00,675.00,1425.00,400.00,400.00,100.00,100.00,886.20,0.00,0.00,0.00,2061.20,15438.80,'Draft','2026-09-23 10:41:27','2026-09-23 10:41:27'),(3,1,3,10000.00,0.0,0.00,10000.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,NULL,750.00,0.00,0.00,10750.00,0.00,0.00,0.00,450.00,950.00,250.00,250.00,100.00,100.00,0.00,0.00,0.00,0.00,800.00,9950.00,'Draft','2026-09-23 10:41:27','2026-09-23 10:41:27'),(4,1,5,22500.00,0.0,0.00,22500.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,NULL,2500.00,0.00,0.00,25000.00,0.00,0.00,0.00,675.00,1425.00,562.50,562.50,100.00,100.00,2336.60,0.00,0.00,0.00,3674.10,21325.90,'Draft','2026-09-23 10:41:27','2026-09-23 10:41:27'),(5,1,6,11000.00,0.0,0.00,11000.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,NULL,750.00,0.00,0.00,11750.00,0.00,0.00,0.00,495.00,1045.00,275.00,275.00,100.00,100.00,69.45,0.00,0.00,0.00,939.45,10810.55,'Draft','2026-09-23 10:41:27','2026-09-23 10:41:27'),(6,1,8,22500.00,0.0,0.00,22500.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,NULL,2500.00,0.00,0.00,25000.00,0.00,0.00,0.00,675.00,1425.00,562.50,562.50,100.00,100.00,2336.60,0.00,0.00,0.00,3674.10,21325.90,'Draft','2026-09-23 10:41:27','2026-09-24 09:37:11'),(7,1,9,8750.00,0.0,0.00,8750.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,NULL,500.00,0.00,0.00,9250.00,0.00,0.00,0.00,393.75,831.25,218.75,218.75,100.00,100.00,0.00,0.00,0.00,0.00,712.50,8537.50,'Draft','2026-09-23 10:41:27','2026-09-23 10:41:27'),(8,1,10,8000.00,0.0,0.00,8000.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,NULL,500.00,0.00,0.00,8500.00,0.00,0.00,0.00,360.00,760.00,200.00,200.00,100.00,100.00,0.00,0.00,0.00,0.00,660.00,7840.00,'Draft','2026-09-23 10:41:27','2026-09-23 10:41:27'),(9,1,64,37500.00,0.0,0.00,37500.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,NULL,5000.00,0.00,0.00,42500.00,0.00,0.00,0.00,675.00,1425.00,937.50,937.50,100.00,100.00,6134.33,0.00,0.00,0.00,7846.83,34653.17,'Draft','2026-09-24 09:37:11','2026-09-24 09:37:11'),(10,1,65,30000.00,0.0,0.00,30000.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,NULL,4000.00,0.00,0.00,34000.00,0.00,0.00,0.00,675.00,1425.00,750.00,750.00,100.00,100.00,4099.10,0.00,0.00,0.00,5624.10,28375.90,'Draft','2026-09-24 09:37:11','2026-09-24 09:37:11'),(11,1,66,25000.00,0.0,0.00,25000.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,NULL,3000.00,0.00,0.00,28000.00,0.00,0.00,0.00,675.00,1425.00,625.00,625.00,100.00,100.00,2924.10,0.00,0.00,0.00,4324.10,23675.90,'Draft','2026-09-24 09:37:11','2026-09-24 09:37:11'),(12,1,67,10500.00,0.0,0.00,10500.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,NULL,750.00,0.00,0.00,11250.00,0.00,0.00,0.00,472.50,997.50,262.50,262.50,100.00,100.00,0.00,0.00,0.00,0.00,835.00,10415.00,'Draft','2026-09-24 09:37:11','2026-09-24 09:37:11'),(13,1,68,8750.00,0.0,0.00,8750.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,NULL,500.00,0.00,0.00,9250.00,0.00,0.00,0.00,393.75,831.25,218.75,218.75,100.00,100.00,0.00,0.00,0.00,0.00,712.50,8537.50,'Draft','2026-09-24 09:37:11','2026-09-24 09:37:11'),(14,1,69,12000.00,0.0,0.00,12000.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,NULL,1000.00,0.00,0.00,13000.00,0.00,0.00,0.00,540.00,1140.00,300.00,300.00,100.00,100.00,246.45,0.00,0.00,0.00,1186.45,11813.55,'Draft','2026-09-24 09:37:11','2026-09-24 09:37:11');
/*!40000 ALTER TABLE `hr_payroll_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_performance_criteria`
--

DROP TABLE IF EXISTS `hr_performance_criteria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_performance_criteria` (
  `performance_criteria_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `weight_percentage` tinyint(3) unsigned NOT NULL DEFAULT 10,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`performance_criteria_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_performance_criteria`
--

LOCK TABLES `hr_performance_criteria` WRITE;
/*!40000 ALTER TABLE `hr_performance_criteria` DISABLE KEYS */;
INSERT INTO `hr_performance_criteria` VALUES (1,'Attendance',NULL,10,1,'2026-09-23 10:40:59','2026-09-23 10:40:59'),(2,'Punctuality',NULL,10,1,'2026-09-23 10:40:59','2026-09-23 10:40:59'),(3,'Work Quality',NULL,15,1,'2026-09-23 10:40:59','2026-09-23 10:40:59'),(4,'Customer Service',NULL,15,1,'2026-09-23 10:40:59','2026-09-23 10:40:59'),(5,'Teamwork',NULL,10,1,'2026-09-23 10:40:59','2026-09-23 10:40:59'),(6,'Productivity',NULL,10,1,'2026-09-23 10:40:59','2026-09-23 10:40:59'),(7,'Communication',NULL,5,1,'2026-09-23 10:40:59','2026-09-23 10:40:59'),(8,'Cleanliness',NULL,10,1,'2026-09-23 10:40:59','2026-09-23 10:40:59'),(9,'Food Safety Compliance',NULL,10,1,'2026-09-23 10:40:59','2026-09-23 10:40:59'),(10,'Following Procedures',NULL,5,1,'2026-09-23 10:40:59','2026-09-23 10:40:59'),(11,'Initiative',NULL,5,1,'2026-09-23 10:40:59','2026-09-23 10:40:59');
/*!40000 ALTER TABLE `hr_performance_criteria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_performance_evaluations`
--

DROP TABLE IF EXISTS `hr_performance_evaluations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_performance_evaluations` (
  `performance_evaluation_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `performance_period_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `evaluator_id` bigint(20) unsigned NOT NULL,
  `overall_score` decimal(4,2) NOT NULL DEFAULT 0.00,
  `status` enum('Draft','Submitted','HR_Reviewed','Final') NOT NULL DEFAULT 'Draft',
  `manager_comments` text DEFAULT NULL,
  `hr_comments` text DEFAULT NULL,
  `recommendation` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`performance_evaluation_id`),
  UNIQUE KEY `performance_evaluations_performance_period_id_employee_id_unique` (`performance_period_id`,`employee_id`),
  KEY `performance_evaluations_employee_id_foreign` (`employee_id`),
  KEY `performance_evaluations_evaluator_id_foreign` (`evaluator_id`),
  CONSTRAINT `performance_evaluations_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`employee_id`) ON DELETE CASCADE,
  CONSTRAINT `performance_evaluations_evaluator_id_foreign` FOREIGN KEY (`evaluator_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `performance_evaluations_performance_period_id_foreign` FOREIGN KEY (`performance_period_id`) REFERENCES `hr_performance_periods` (`performance_period_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_performance_evaluations`
--

LOCK TABLES `hr_performance_evaluations` WRITE;
/*!40000 ALTER TABLE `hr_performance_evaluations` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr_performance_evaluations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_performance_periods`
--

DROP TABLE IF EXISTS `hr_performance_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_performance_periods` (
  `performance_period_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Draft','Active','Closed') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`performance_period_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_performance_periods`
--

LOCK TABLES `hr_performance_periods` WRITE;
/*!40000 ALTER TABLE `hr_performance_periods` DISABLE KEYS */;
INSERT INTO `hr_performance_periods` VALUES (1,'Q3 2026 Restaurant Performance Review','2026-07-01','2026-09-30','Active','2026-09-23 10:40:59','2026-09-23 10:40:59');
/*!40000 ALTER TABLE `hr_performance_periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_performance_ratings`
--

DROP TABLE IF EXISTS `hr_performance_ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_performance_ratings` (
  `performance_rating_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `evaluation_id` bigint(20) unsigned NOT NULL,
  `criterion_id` bigint(20) unsigned NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`performance_rating_id`),
  KEY `performance_ratings_evaluation_id_foreign` (`evaluation_id`),
  KEY `performance_ratings_criterion_id_foreign` (`criterion_id`),
  CONSTRAINT `performance_ratings_criterion_id_foreign` FOREIGN KEY (`criterion_id`) REFERENCES `hr_performance_criteria` (`performance_criteria_id`) ON DELETE CASCADE,
  CONSTRAINT `performance_ratings_evaluation_id_foreign` FOREIGN KEY (`evaluation_id`) REFERENCES `hr_performance_evaluations` (`performance_evaluation_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_performance_ratings`
--

LOCK TABLES `hr_performance_ratings` WRITE;
/*!40000 ALTER TABLE `hr_performance_ratings` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr_performance_ratings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_positions`
--

DROP TABLE IF EXISTS `hr_positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_positions` (
  `position_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(30) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`position_id`),
  UNIQUE KEY `positions_code_unique` (`code`),
  KEY `positions_department_id_foreign` (`department_id`),
  CONSTRAINT `positions_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `hr_departments` (`department_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_positions`
--

LOCK TABLES `hr_positions` WRITE;
/*!40000 ALTER TABLE `hr_positions` DISABLE KEYS */;
INSERT INTO `hr_positions` VALUES (1,1,'Restaurant Manager','RM-01',NULL,'2026-09-23 10:40:49','2026-09-23 10:40:49'),(2,1,'Assistant Manager','AM-02',NULL,'2026-09-23 10:40:49','2026-09-23 10:40:49'),(3,3,'Head Cook','HC-03',NULL,'2026-09-23 10:40:49','2026-09-23 10:40:49'),(4,3,'Cook','CK-04',NULL,'2026-09-23 10:40:49','2026-09-23 10:40:49'),(5,3,'Kitchen Helper','KH-05',NULL,'2026-09-23 10:40:49','2026-09-23 10:40:49'),(6,3,'Dishwasher','DW-06',NULL,'2026-09-23 10:40:49','2026-09-23 10:40:49'),(7,2,'Cashier','CS-07',NULL,'2026-09-23 10:40:49','2026-09-23 10:40:49'),(8,2,'Server','SV-08',NULL,'2026-09-23 10:40:49','2026-09-23 10:40:49'),(9,2,'Host','HT-09',NULL,'2026-09-23 10:40:49','2026-09-23 10:40:49'),(10,1,'General Manager','GM-00',NULL,'2026-09-24 09:37:09','2026-09-24 09:37:09'),(11,1,'Systems Administrator','IT-01',NULL,'2026-09-24 09:37:09','2026-09-24 09:37:09'),(12,4,'HR Manager','HR-01',NULL,'2026-09-24 09:37:09','2026-09-24 09:37:09'),(13,1,'Restaurant Supervisor','SP-01',NULL,'2026-09-24 09:37:09','2026-09-24 09:37:09'),(14,46,'Test Crew','TEST_POS',NULL,'2026-09-30 17:36:31','2026-09-30 17:36:31'),(15,46,'Floor Supervisor','TEST_SUPV',NULL,'2026-09-30 17:38:09','2026-09-30 17:38:09'),(18,46,'Floor Supervisor','TEST_SUPV_LIFE',NULL,'2026-09-30 17:41:47','2026-09-30 17:41:47');
/*!40000 ALTER TABLE `hr_positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_premium_pay_items`
--

DROP TABLE IF EXISTS `hr_premium_pay_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_premium_pay_items` (
  `premium_pay_item_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payroll_period_id` bigint(20) unsigned DEFAULT NULL,
  `payroll_record_id` bigint(20) unsigned DEFAULT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `work_date` date NOT NULL,
  `attendance_record_id` bigint(20) unsigned DEFAULT NULL,
  `holiday_id` bigint(20) unsigned DEFAULT NULL,
  `work_type` varchar(60) NOT NULL,
  `holiday_type` varchar(60) DEFAULT NULL,
  `is_rest_day` tinyint(1) NOT NULL DEFAULT 0,
  `hours_worked` decimal(5,2) NOT NULL DEFAULT 8.00,
  `regular_hours` decimal(5,2) NOT NULL DEFAULT 8.00,
  `overtime_hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `daily_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `applied_rate_multiplier` decimal(6,2) NOT NULL DEFAULT 130.00,
  `regular_premium_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `overtime_premium_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `premium_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `calculation_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`calculation_breakdown`)),
  `rule_version` varchar(50) DEFAULT 'v1.0-DOLE',
  `status` varchar(30) NOT NULL DEFAULT 'Pending',
  `rejection_reason` varchar(255) DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_by` bigint(20) unsigned DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`premium_pay_item_id`),
  KEY `hr_premium_pay_items_employee_id_work_date_index` (`employee_id`,`work_date`),
  KEY `hr_premium_pay_items_payroll_period_id_status_index` (`payroll_period_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_premium_pay_items`
--

LOCK TABLES `hr_premium_pay_items` WRITE;
/*!40000 ALTER TABLE `hr_premium_pay_items` DISABLE KEYS */;
INSERT INTO `hr_premium_pay_items` VALUES (1,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,1461.54,182.69,130.00,1900.00,0.00,1900.00,'{\"classification\":\"Rest Day\",\"rule_key\":\"REST_DAY\",\"rule_version\":\"v1.2-DOLE-2026\\/2027\",\"is_rest_day\":true,\"holiday_name\":\"Rest Day\",\"holiday_type\":null,\"daily_rate\":1461.54,\"hourly_rate\":182.69,\"applied_rate\":\"130%\",\"base_multiplier\":130,\"regular_hours\":8,\"regular_rate_hourly\":237.5,\"regular_pay\":1900,\"regular_formula\":\"\\u20b11461.54 \\/ 8h = \\u20b1182.69\\/hr \\u00d7 130% = \\u20b1237.5\\/hr \\u00d7 8h = \\u20b11900\",\"overtime_hours\":0,\"overtime_multiplier\":1.3,\"overtime_rate_hourly\":308.75,\"overtime_pay\":0,\"overtime_formula\":\"No overtime worked\",\"total_day_pay\":1900}','v1.2-DOLE-2026/2027','Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(2,1,NULL,2,'2026-11-01',NULL,11,'Special Non-Working','Special Non-Working',0,8.00,8.00,0.00,1230.77,153.85,130.00,1600.08,0.00,1600.08,'{\"classification\":\"Special Non-Working\",\"rule_key\":\"SPECIAL_NON-WORKING\",\"rule_version\":\"v1.2-DOLE-2026\\/2027\",\"is_rest_day\":false,\"holiday_name\":\"Special Non-Working\",\"holiday_type\":\"Special Non-Working\",\"daily_rate\":1230.77,\"hourly_rate\":153.85,\"applied_rate\":\"130%\",\"base_multiplier\":130,\"regular_hours\":8,\"regular_rate_hourly\":200.01,\"regular_pay\":1600.08,\"regular_formula\":\"\\u20b11230.77 \\/ 8h = \\u20b1153.85\\/hr \\u00d7 130% = \\u20b1200.01\\/hr \\u00d7 8h = \\u20b11600.08\",\"overtime_hours\":0,\"overtime_multiplier\":1.3,\"overtime_rate_hourly\":260.01,\"overtime_pay\":0,\"overtime_formula\":\"No overtime worked\",\"total_day_pay\":1600.08}','v1.2-DOLE-2026/2027','Approved',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(3,1,NULL,3,'2026-11-01',NULL,11,'Special Non-Working + Rest Day','Special Non-Working',1,10.00,8.00,2.00,769.23,96.15,150.00,1153.84,375.00,1528.84,'{\"classification\":\"Special Non-Working + Rest Day\",\"rule_key\":\"SPECIAL_NON-WORKING__REST_DAY\",\"rule_version\":\"v1.2-DOLE-2026\\/2027\",\"is_rest_day\":true,\"holiday_name\":\"Special Non-Working + Rest Day\",\"holiday_type\":\"Special Non-Working\",\"daily_rate\":769.23,\"hourly_rate\":96.15,\"applied_rate\":\"150%\",\"base_multiplier\":150,\"regular_hours\":8,\"regular_rate_hourly\":144.23,\"regular_pay\":1153.84,\"regular_formula\":\"\\u20b1769.23 \\/ 8h = \\u20b196.15\\/hr \\u00d7 150% = \\u20b1144.23\\/hr \\u00d7 8h = \\u20b11153.84\",\"overtime_hours\":2,\"overtime_multiplier\":1.3,\"overtime_rate_hourly\":187.5,\"overtime_pay\":375,\"overtime_formula\":\"\\u20b1144.23\\/hr \\u00d7 130% = \\u20b1187.5\\/hr \\u00d7 2h = \\u20b1375\",\"total_day_pay\":1528.84}','v1.2-DOLE-2026/2027','Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(4,1,NULL,4,'2026-11-30',NULL,NULL,'Regular Holiday','Regular Holiday',0,8.00,8.00,0.00,653.85,81.73,200.00,1307.68,0.00,1307.68,'{\"classification\":\"Regular Holiday\",\"rule_key\":\"REGULAR_HOLIDAY\",\"rule_version\":\"v1.2-DOLE-2026\\/2027\",\"is_rest_day\":false,\"holiday_name\":\"Regular Holiday\",\"holiday_type\":\"Regular Holiday\",\"daily_rate\":653.85,\"hourly_rate\":81.73,\"applied_rate\":\"200%\",\"base_multiplier\":200,\"regular_hours\":8,\"regular_rate_hourly\":163.46,\"regular_pay\":1307.68,\"regular_formula\":\"\\u20b1653.85 \\/ 8h = \\u20b181.73\\/hr \\u00d7 200% = \\u20b1163.46\\/hr \\u00d7 8h = \\u20b11307.68\",\"overtime_hours\":0,\"overtime_multiplier\":1.3,\"overtime_rate_hourly\":212.5,\"overtime_pay\":0,\"overtime_formula\":\"No overtime worked\",\"total_day_pay\":1307.68}','v1.2-DOLE-2026/2027','Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(5,1,NULL,5,'2026-11-30',NULL,NULL,'Regular Holiday + Rest Day','Regular Holiday',1,9.00,8.00,1.00,1730.77,216.35,260.00,4500.08,731.26,5231.34,'{\"classification\":\"Regular Holiday + Rest Day\",\"rule_key\":\"REGULAR_HOLIDAY__REST_DAY\",\"rule_version\":\"v1.2-DOLE-2026\\/2027\",\"is_rest_day\":true,\"holiday_name\":\"Regular Holiday + Rest Day\",\"holiday_type\":\"Regular Holiday\",\"daily_rate\":1730.77,\"hourly_rate\":216.35,\"applied_rate\":\"260%\",\"base_multiplier\":260,\"regular_hours\":8,\"regular_rate_hourly\":562.51,\"regular_pay\":4500.08,\"regular_formula\":\"\\u20b11730.77 \\/ 8h = \\u20b1216.35\\/hr \\u00d7 260% = \\u20b1562.51\\/hr \\u00d7 8h = \\u20b14500.08\",\"overtime_hours\":1,\"overtime_multiplier\":1.3,\"overtime_rate_hourly\":731.26,\"overtime_pay\":731.26,\"overtime_formula\":\"\\u20b1562.51\\/hr \\u00d7 130% = \\u20b1731.26\\/hr \\u00d7 1h = \\u20b1731.26\",\"total_day_pay\":5231.34}','v1.2-DOLE-2026/2027','Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-02 03:56:24','2026-10-02 03:56:24'),(6,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,1461.54,182.69,130.00,1900.00,0.00,1900.00,'{\"classification\":\"Rest Day\",\"rule_key\":\"REST_DAY\",\"rule_version\":\"v1.2-DOLE-2026\\/2027\",\"is_rest_day\":true,\"holiday_name\":\"Rest Day\",\"holiday_type\":null,\"daily_rate\":1461.54,\"hourly_rate\":182.69,\"applied_rate\":\"130%\",\"base_multiplier\":130,\"regular_hours\":8,\"regular_rate_hourly\":237.5,\"regular_pay\":1900,\"regular_formula\":\"\\u20b11461.54 \\/ 8h = \\u20b1182.69\\/hr \\u00d7 130% = \\u20b1237.5\\/hr \\u00d7 8h = \\u20b11900\",\"overtime_hours\":0,\"overtime_multiplier\":1.3,\"overtime_rate_hourly\":308.75,\"overtime_pay\":0,\"overtime_formula\":\"No overtime worked\",\"total_day_pay\":1900}','v1.2-DOLE-2026/2027','Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-02 04:03:41','2026-10-02 04:03:41'),(7,1,NULL,2,'2026-11-01',NULL,11,'Special Non-Working','Special Non-Working',0,8.00,8.00,0.00,1230.77,153.85,130.00,1600.08,0.00,1600.08,'{\"classification\":\"Special Non-Working\",\"rule_key\":\"SPECIAL_NON-WORKING\",\"rule_version\":\"v1.2-DOLE-2026\\/2027\",\"is_rest_day\":false,\"holiday_name\":\"Special Non-Working\",\"holiday_type\":\"Special Non-Working\",\"daily_rate\":1230.77,\"hourly_rate\":153.85,\"applied_rate\":\"130%\",\"base_multiplier\":130,\"regular_hours\":8,\"regular_rate_hourly\":200.01,\"regular_pay\":1600.08,\"regular_formula\":\"\\u20b11230.77 \\/ 8h = \\u20b1153.85\\/hr \\u00d7 130% = \\u20b1200.01\\/hr \\u00d7 8h = \\u20b11600.08\",\"overtime_hours\":0,\"overtime_multiplier\":1.3,\"overtime_rate_hourly\":260.01,\"overtime_pay\":0,\"overtime_formula\":\"No overtime worked\",\"total_day_pay\":1600.08}','v1.2-DOLE-2026/2027','Approved',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-02 04:03:41','2026-10-02 04:03:41'),(8,1,NULL,3,'2026-11-01',NULL,11,'Special Non-Working + Rest Day','Special Non-Working',1,10.00,8.00,2.00,769.23,96.15,150.00,1153.84,375.00,1528.84,'{\"classification\":\"Special Non-Working + Rest Day\",\"rule_key\":\"SPECIAL_NON-WORKING__REST_DAY\",\"rule_version\":\"v1.2-DOLE-2026\\/2027\",\"is_rest_day\":true,\"holiday_name\":\"Special Non-Working + Rest Day\",\"holiday_type\":\"Special Non-Working\",\"daily_rate\":769.23,\"hourly_rate\":96.15,\"applied_rate\":\"150%\",\"base_multiplier\":150,\"regular_hours\":8,\"regular_rate_hourly\":144.23,\"regular_pay\":1153.84,\"regular_formula\":\"\\u20b1769.23 \\/ 8h = \\u20b196.15\\/hr \\u00d7 150% = \\u20b1144.23\\/hr \\u00d7 8h = \\u20b11153.84\",\"overtime_hours\":2,\"overtime_multiplier\":1.3,\"overtime_rate_hourly\":187.5,\"overtime_pay\":375,\"overtime_formula\":\"\\u20b1144.23\\/hr \\u00d7 130% = \\u20b1187.5\\/hr \\u00d7 2h = \\u20b1375\",\"total_day_pay\":1528.84}','v1.2-DOLE-2026/2027','Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-02 04:03:41','2026-10-02 04:03:41'),(9,1,NULL,4,'2026-11-30',NULL,NULL,'Regular Holiday','Regular Holiday',0,8.00,8.00,0.00,653.85,81.73,200.00,1307.68,0.00,1307.68,'{\"classification\":\"Regular Holiday\",\"rule_key\":\"REGULAR_HOLIDAY\",\"rule_version\":\"v1.2-DOLE-2026\\/2027\",\"is_rest_day\":false,\"holiday_name\":\"Regular Holiday\",\"holiday_type\":\"Regular Holiday\",\"daily_rate\":653.85,\"hourly_rate\":81.73,\"applied_rate\":\"200%\",\"base_multiplier\":200,\"regular_hours\":8,\"regular_rate_hourly\":163.46,\"regular_pay\":1307.68,\"regular_formula\":\"\\u20b1653.85 \\/ 8h = \\u20b181.73\\/hr \\u00d7 200% = \\u20b1163.46\\/hr \\u00d7 8h = \\u20b11307.68\",\"overtime_hours\":0,\"overtime_multiplier\":1.3,\"overtime_rate_hourly\":212.5,\"overtime_pay\":0,\"overtime_formula\":\"No overtime worked\",\"total_day_pay\":1307.68}','v1.2-DOLE-2026/2027','Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-02 04:03:41','2026-10-02 04:03:41'),(10,1,NULL,5,'2026-11-30',NULL,NULL,'Regular Holiday + Rest Day','Regular Holiday',1,9.00,8.00,1.00,1730.77,216.35,260.00,4500.08,731.26,5231.34,'{\"classification\":\"Regular Holiday + Rest Day\",\"rule_key\":\"REGULAR_HOLIDAY__REST_DAY\",\"rule_version\":\"v1.2-DOLE-2026\\/2027\",\"is_rest_day\":true,\"holiday_name\":\"Regular Holiday + Rest Day\",\"holiday_type\":\"Regular Holiday\",\"daily_rate\":1730.77,\"hourly_rate\":216.35,\"applied_rate\":\"260%\",\"base_multiplier\":260,\"regular_hours\":8,\"regular_rate_hourly\":562.51,\"regular_pay\":4500.08,\"regular_formula\":\"\\u20b11730.77 \\/ 8h = \\u20b1216.35\\/hr \\u00d7 260% = \\u20b1562.51\\/hr \\u00d7 8h = \\u20b14500.08\",\"overtime_hours\":1,\"overtime_multiplier\":1.3,\"overtime_rate_hourly\":731.26,\"overtime_pay\":731.26,\"overtime_formula\":\"\\u20b1562.51\\/hr \\u00d7 130% = \\u20b1731.26\\/hr \\u00d7 1h = \\u20b1731.26\",\"total_day_pay\":5231.34}','v1.2-DOLE-2026/2027','Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-02 04:03:41','2026-10-02 04:03:41'),(11,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Schedule shift was unapproved by branch supervisor',NULL,NULL,1,'2026-10-02 04:03:42',NULL,NULL,'2026-10-02 04:03:42','2026-10-02 04:03:42'),(12,NULL,NULL,1,'2026-11-01',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-02 04:03:42','2026-10-02 04:03:42'),(13,NULL,NULL,1,'2026-11-02',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-02 04:03:42','2026-10-02 04:03:42'),(14,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Schedule shift was unapproved by branch supervisor',NULL,NULL,1,'2026-10-02 04:05:51',NULL,NULL,'2026-10-02 04:05:51','2026-10-02 04:05:51'),(15,NULL,NULL,1,'2026-11-01',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-02 04:05:51',NULL,NULL,'2026-10-02 04:05:51','2026-10-02 04:05:51'),(16,NULL,NULL,1,'2026-11-02',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-02 04:05:51',NULL,NULL,'2026-10-02 04:05:51','2026-10-02 04:05:51'),(17,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Schedule shift was unapproved by branch supervisor',NULL,NULL,1,'2026-10-02 04:15:59',NULL,NULL,'2026-10-02 04:15:59','2026-10-02 04:15:59'),(18,NULL,NULL,1,'2026-11-01',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-02 04:15:59',NULL,NULL,'2026-10-02 04:15:59','2026-10-02 04:15:59'),(19,NULL,NULL,1,'2026-11-02',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-02 04:15:59',NULL,NULL,'2026-10-02 04:15:59','2026-10-02 04:15:59'),(20,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Schedule shift was unapproved by branch supervisor',NULL,NULL,1,'2026-10-02 04:19:40',NULL,NULL,'2026-10-02 04:19:40','2026-10-02 04:19:40'),(21,NULL,NULL,1,'2026-11-01',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-02 04:19:40',NULL,NULL,'2026-10-02 04:19:40','2026-10-02 04:19:40'),(22,NULL,NULL,1,'2026-11-02',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-02 04:19:40',NULL,NULL,'2026-10-02 04:19:40','2026-10-02 04:19:40'),(23,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Schedule shift was unapproved by branch supervisor',NULL,NULL,1,'2026-10-02 04:43:30',NULL,NULL,'2026-10-02 04:43:30','2026-10-02 04:43:30'),(24,NULL,NULL,1,'2026-11-01',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-02 04:43:30',NULL,NULL,'2026-10-02 04:43:30','2026-10-02 04:43:30'),(25,NULL,NULL,1,'2026-11-02',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-02 04:43:30',NULL,NULL,'2026-10-02 04:43:30','2026-10-02 04:43:30'),(26,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Schedule shift was unapproved by branch supervisor',NULL,NULL,1,'2026-10-02 04:48:49',NULL,NULL,'2026-10-02 04:48:49','2026-10-02 04:48:49'),(27,NULL,NULL,1,'2026-11-01',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-02 04:48:49',NULL,NULL,'2026-10-02 04:48:49','2026-10-02 04:48:49'),(28,NULL,NULL,1,'2026-11-02',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-02 04:48:49',NULL,NULL,'2026-10-02 04:48:49','2026-10-02 04:48:49'),(29,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Schedule shift was unapproved by branch supervisor',NULL,NULL,1,'2026-10-02 05:04:43',NULL,NULL,'2026-10-02 05:04:43','2026-10-02 05:04:43'),(30,NULL,NULL,1,'2026-11-01',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-02 05:04:43',NULL,NULL,'2026-10-02 05:04:43','2026-10-02 05:04:43'),(31,NULL,NULL,1,'2026-11-02',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-02 05:04:43',NULL,NULL,'2026-10-02 05:04:43','2026-10-02 05:04:43'),(32,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Schedule shift was unapproved by branch supervisor',NULL,NULL,1,'2026-10-02 05:16:33',NULL,NULL,'2026-10-02 05:16:33','2026-10-02 05:16:33'),(33,NULL,NULL,1,'2026-11-01',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-02 05:16:33',NULL,NULL,'2026-10-02 05:16:33','2026-10-02 05:16:33'),(34,NULL,NULL,1,'2026-11-02',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-02 05:16:33',NULL,NULL,'2026-10-02 05:16:33','2026-10-02 05:16:33'),(35,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Schedule shift was unapproved by branch supervisor',NULL,NULL,1,'2026-10-09 02:31:05',NULL,NULL,'2026-10-09 02:31:05','2026-10-09 02:31:05'),(36,NULL,NULL,1,'2026-11-01',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 02:31:05',NULL,NULL,'2026-10-09 02:31:05','2026-10-09 02:31:05'),(37,NULL,NULL,1,'2026-11-02',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 02:31:05',NULL,NULL,'2026-10-09 02:31:05','2026-10-09 02:31:05'),(38,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Schedule shift was unapproved by branch supervisor',NULL,NULL,1,'2026-10-09 02:33:01',NULL,NULL,'2026-10-09 02:33:01','2026-10-09 02:33:01'),(39,NULL,NULL,1,'2026-11-01',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 02:33:01',NULL,NULL,'2026-10-09 02:33:01','2026-10-09 02:33:01'),(40,NULL,NULL,1,'2026-11-02',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 02:33:01',NULL,NULL,'2026-10-09 02:33:01','2026-10-09 02:33:01'),(41,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Schedule shift was unapproved by branch supervisor',NULL,NULL,1,'2026-10-09 04:00:31',NULL,NULL,'2026-10-09 04:00:31','2026-10-09 04:00:31'),(42,NULL,NULL,1,'2026-11-01',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 04:00:31',NULL,NULL,'2026-10-09 04:00:31','2026-10-09 04:00:31'),(43,NULL,NULL,1,'2026-11-02',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 04:00:31',NULL,NULL,'2026-10-09 04:00:31','2026-10-09 04:00:31'),(44,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Schedule shift was unapproved by branch supervisor',NULL,NULL,1,'2026-10-09 04:01:39',NULL,NULL,'2026-10-09 04:01:39','2026-10-09 04:01:39'),(45,NULL,NULL,1,'2026-11-01',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 04:01:39',NULL,NULL,'2026-10-09 04:01:39','2026-10-09 04:01:39'),(46,NULL,NULL,1,'2026-11-02',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 04:01:39',NULL,NULL,'2026-10-09 04:01:39','2026-10-09 04:01:39'),(47,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Schedule shift was unapproved by branch supervisor',NULL,NULL,1,'2026-10-09 04:42:09',NULL,NULL,'2026-10-09 04:42:09','2026-10-09 04:42:09'),(48,NULL,NULL,1,'2026-11-01',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 04:42:09',NULL,NULL,'2026-10-09 04:42:09','2026-10-09 04:42:09'),(49,NULL,NULL,1,'2026-11-02',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 04:42:09',NULL,NULL,'2026-10-09 04:42:09','2026-10-09 04:42:09'),(50,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Schedule shift was unapproved by branch supervisor',NULL,NULL,1,'2026-10-09 04:55:33',NULL,NULL,'2026-10-09 04:55:33','2026-10-09 04:55:33'),(51,NULL,NULL,1,'2026-11-01',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 04:55:33',NULL,NULL,'2026-10-09 04:55:33','2026-10-09 04:55:33'),(52,NULL,NULL,1,'2026-11-02',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 04:55:33',NULL,NULL,'2026-10-09 04:55:33','2026-10-09 04:55:33'),(53,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Schedule shift was unapproved by branch supervisor',NULL,NULL,1,'2026-10-09 05:10:36',NULL,NULL,'2026-10-09 05:10:36','2026-10-09 05:10:36'),(54,NULL,NULL,1,'2026-11-01',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 05:10:36',NULL,NULL,'2026-10-09 05:10:36','2026-10-09 05:10:36'),(55,NULL,NULL,1,'2026-11-02',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 05:10:36',NULL,NULL,'2026-10-09 05:10:36','2026-10-09 05:10:36'),(56,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Schedule shift was unapproved by branch supervisor',NULL,NULL,1,'2026-10-09 05:39:37',NULL,NULL,'2026-10-09 05:39:37','2026-10-09 05:39:37'),(57,NULL,NULL,1,'2026-11-01',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 05:39:37',NULL,NULL,'2026-10-09 05:39:37','2026-10-09 05:39:37'),(58,NULL,NULL,1,'2026-11-02',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 05:39:37',NULL,NULL,'2026-10-09 05:39:37','2026-10-09 05:39:37'),(59,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Schedule shift was unapproved by branch supervisor',NULL,NULL,1,'2026-10-09 05:47:01',NULL,NULL,'2026-10-09 05:47:01','2026-10-09 05:47:01'),(60,NULL,NULL,1,'2026-11-01',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 05:47:01',NULL,NULL,'2026-10-09 05:47:01','2026-10-09 05:47:01'),(61,NULL,NULL,1,'2026-11-02',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 05:47:01',NULL,NULL,'2026-10-09 05:47:01','2026-10-09 05:47:01'),(62,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Schedule shift was unapproved by branch supervisor',NULL,NULL,1,'2026-10-09 08:06:40',NULL,NULL,'2026-10-09 08:06:40','2026-10-09 08:06:40'),(63,NULL,NULL,1,'2026-11-01',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 08:06:40',NULL,NULL,'2026-10-09 08:06:40','2026-10-09 08:06:40'),(64,NULL,NULL,1,'2026-11-02',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 08:06:40',NULL,NULL,'2026-10-09 08:06:40','2026-10-09 08:06:40'),(65,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Schedule shift was unapproved by branch supervisor',NULL,NULL,1,'2026-10-09 08:07:40',NULL,NULL,'2026-10-09 08:07:40','2026-10-09 08:07:40'),(66,NULL,NULL,1,'2026-11-01',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 08:07:40',NULL,NULL,'2026-10-09 08:07:40','2026-10-09 08:07:40'),(67,NULL,NULL,1,'2026-11-02',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 08:07:40',NULL,NULL,'2026-10-09 08:07:40','2026-10-09 08:07:40'),(68,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Schedule shift was unapproved by branch supervisor',NULL,NULL,1,'2026-10-09 08:08:44',NULL,NULL,'2026-10-09 08:08:44','2026-10-09 08:08:44'),(69,NULL,NULL,1,'2026-11-01',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 08:08:44',NULL,NULL,'2026-10-09 08:08:44','2026-10-09 08:08:44'),(70,NULL,NULL,1,'2026-11-02',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 08:08:44',NULL,NULL,'2026-10-09 08:08:44','2026-10-09 08:08:44'),(71,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Schedule shift was unapproved by branch supervisor',NULL,NULL,1,'2026-10-09 08:14:45',NULL,NULL,'2026-10-09 08:14:45','2026-10-09 08:14:45'),(72,NULL,NULL,1,'2026-11-01',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 08:14:45',NULL,NULL,'2026-10-09 08:14:45','2026-10-09 08:14:45'),(73,NULL,NULL,1,'2026-11-02',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 08:14:45',NULL,NULL,'2026-10-09 08:14:45','2026-10-09 08:14:45'),(74,1,NULL,1,'2026-10-31',NULL,NULL,'Rest Day',NULL,1,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Schedule shift was unapproved by branch supervisor',NULL,NULL,1,'2026-10-09 08:29:23',NULL,NULL,'2026-10-09 08:29:23','2026-10-09 08:29:23'),(75,NULL,NULL,1,'2026-11-01',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 08:29:23',NULL,NULL,'2026-10-09 08:29:23','2026-10-09 08:29:23'),(76,NULL,NULL,1,'2026-11-02',NULL,NULL,'Special Non-Working Day',NULL,0,8.00,8.00,0.00,0.00,0.00,130.00,0.00,0.00,1040.00,NULL,'v1.0-DOLE','Rejected','Batch review: Store closed early',NULL,NULL,1,'2026-10-09 08:29:23',NULL,NULL,'2026-10-09 08:29:23','2026-10-09 08:29:23');
/*!40000 ALTER TABLE `hr_premium_pay_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_premium_pay_rule_versions`
--

DROP TABLE IF EXISTS `hr_premium_pay_rule_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_premium_pay_rule_versions` (
  `premium_pay_rule_version_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `premium_pay_rule_id` bigint(20) unsigned NOT NULL,
  `version_number` int(10) unsigned NOT NULL DEFAULT 1,
  `version_code` varchar(50) DEFAULT NULL,
  `effective_date` date NOT NULL,
  `configuration_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`configuration_snapshot`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `change_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`premium_pay_rule_version_id`),
  KEY `hr_premium_pay_rule_versions_premium_pay_rule_id_foreign` (`premium_pay_rule_id`),
  CONSTRAINT `hr_premium_pay_rule_versions_premium_pay_rule_id_foreign` FOREIGN KEY (`premium_pay_rule_id`) REFERENCES `hr_premium_pay_rules` (`premium_pay_rule_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_premium_pay_rule_versions`
--

LOCK TABLES `hr_premium_pay_rule_versions` WRITE;
/*!40000 ALTER TABLE `hr_premium_pay_rule_versions` DISABLE KEYS */;
INSERT INTO `hr_premium_pay_rule_versions` VALUES (1,1,1,'v1.0-DOLE-STATUTORY','2026-01-01','{\"rule_code\":\"REST_DAY\",\"name\":\"Rest Day Premium\",\"category\":\"Rest Day\",\"base_multiplier\":130,\"holiday_multiplier\":100,\"rest_day_multiplier\":130,\"overtime_multiplier\":1.3,\"night_diff_multiplier\":1.1,\"rule_type\":\"Statutory Default\",\"description\":\"Work performed on scheduled rest day (additional 30% of basic daily\\/hourly rate)\"}',NULL,'Statutory baseline under Philippine Labor Code Articles 87, 93, and 94','2026-10-02 03:56:24','2026-10-02 03:56:24'),(2,2,1,'v1.0-DOLE-STATUTORY','2026-01-01','{\"rule_code\":\"SPECIAL_NON_WORKING\",\"name\":\"Special Non-Working Day\",\"category\":\"Special Non-Working\",\"base_multiplier\":130,\"holiday_multiplier\":130,\"rest_day_multiplier\":100,\"overtime_multiplier\":1.3,\"night_diff_multiplier\":1.1,\"rule_type\":\"Statutory Default\",\"description\":\"Work on special non-working holiday (additional 30% of daily rate)\"}',NULL,'Statutory baseline under Philippine Labor Code Articles 87, 93, and 94','2026-10-02 03:56:24','2026-10-02 03:56:24'),(3,3,1,'v1.0-DOLE-STATUTORY','2026-01-01','{\"rule_code\":\"SPECIAL_NON_WORKING_REST_DAY\",\"name\":\"Special Non-Working + Rest Day\",\"category\":\"Special Non-Working\",\"base_multiplier\":150,\"holiday_multiplier\":130,\"rest_day_multiplier\":150,\"overtime_multiplier\":1.3,\"night_diff_multiplier\":1.1,\"rule_type\":\"Statutory Default\",\"description\":\"Work on special non-working holiday falling on employee rest day (additional 50%)\"}',NULL,'Statutory baseline under Philippine Labor Code Articles 87, 93, and 94','2026-10-02 03:56:24','2026-10-02 03:56:24'),(4,4,1,'v1.0-DOLE-STATUTORY','2026-01-01','{\"rule_code\":\"REGULAR_HOLIDAY\",\"name\":\"Regular Holiday\",\"category\":\"Regular Holiday\",\"base_multiplier\":200,\"holiday_multiplier\":200,\"rest_day_multiplier\":100,\"overtime_multiplier\":1.3,\"night_diff_multiplier\":1.1,\"rule_type\":\"Statutory Default\",\"description\":\"Work performed on a regular holiday (200% of basic daily rate for first 8 hours)\"}',NULL,'Statutory baseline under Philippine Labor Code Articles 87, 93, and 94','2026-10-02 03:56:24','2026-10-02 03:56:24'),(5,5,1,'v1.0-DOLE-STATUTORY','2026-01-01','{\"rule_code\":\"REGULAR_HOLIDAY_REST_DAY\",\"name\":\"Regular Holiday + Rest Day\",\"category\":\"Regular Holiday\",\"base_multiplier\":260,\"holiday_multiplier\":200,\"rest_day_multiplier\":130,\"overtime_multiplier\":1.3,\"night_diff_multiplier\":1.1,\"rule_type\":\"Statutory Default\",\"description\":\"Work performed on a regular holiday falling on employee scheduled rest day (260% of basic daily rate)\"}',NULL,'Statutory baseline under Philippine Labor Code Articles 87, 93, and 94','2026-10-02 03:56:24','2026-10-02 03:56:24'),(6,6,1,'v1.0-DOLE-STATUTORY','2026-01-01','{\"rule_code\":\"OVERTIME\",\"name\":\"Daily Overtime\",\"category\":\"Overtime\",\"base_multiplier\":125,\"overtime_multiplier\":1.25,\"rule_type\":\"Statutory Default\",\"description\":\"Work beyond 8 hours on regular workday (additional 25% of hourly rate)\"}',NULL,'Statutory baseline under Philippine Labor Code Articles 87, 93, and 94','2026-10-02 03:56:24','2026-10-02 03:56:24'),(7,7,1,'v1.0-DOLE-STATUTORY','2026-01-01','{\"rule_code\":\"NIGHT_SHIFT\",\"name\":\"Night Shift Differential (NSD)\",\"category\":\"Night Shift\",\"base_multiplier\":110,\"night_diff_multiplier\":1.1,\"rule_type\":\"Statutory Default\",\"description\":\"Work performed between 10:00 PM and 6:00 AM (additional 10% of regular hourly rate)\"}',NULL,'Statutory baseline under Philippine Labor Code Articles 87, 93, and 94','2026-10-02 03:56:24','2026-10-02 03:56:24');
/*!40000 ALTER TABLE `hr_premium_pay_rule_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_premium_pay_rules`
--

DROP TABLE IF EXISTS `hr_premium_pay_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_premium_pay_rules` (
  `premium_pay_rule_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rule_code` varchar(60) NOT NULL,
  `name` varchar(120) NOT NULL,
  `category` varchar(60) NOT NULL,
  `base_multiplier` decimal(6,2) NOT NULL DEFAULT 130.00,
  `holiday_multiplier` decimal(6,2) DEFAULT NULL,
  `rest_day_multiplier` decimal(6,2) DEFAULT NULL,
  `overtime_multiplier` decimal(6,2) DEFAULT NULL,
  `night_diff_multiplier` decimal(6,2) DEFAULT NULL,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `rule_type` varchar(50) NOT NULL DEFAULT 'Statutory Default',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`premium_pay_rule_id`),
  UNIQUE KEY `hr_premium_pay_rules_rule_code_unique` (`rule_code`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_premium_pay_rules`
--

LOCK TABLES `hr_premium_pay_rules` WRITE;
/*!40000 ALTER TABLE `hr_premium_pay_rules` DISABLE KEYS */;
INSERT INTO `hr_premium_pay_rules` VALUES (1,'REST_DAY','Rest Day Premium','Rest Day',130.00,100.00,130.00,1.30,1.10,'2026-01-01',NULL,'Statutory Default',1,'Work performed on scheduled rest day (additional 30% of basic daily/hourly rate)','2026-10-02 03:56:24','2026-10-02 03:56:24'),(2,'SPECIAL_NON_WORKING','Special Non-Working Day','Special Non-Working',130.00,130.00,100.00,1.30,1.10,'2026-01-01',NULL,'Statutory Default',1,'Work on special non-working holiday (additional 30% of daily rate)','2026-10-02 03:56:24','2026-10-02 03:56:24'),(3,'SPECIAL_NON_WORKING_REST_DAY','Special Non-Working + Rest Day','Special Non-Working',150.00,130.00,150.00,1.30,1.10,'2026-01-01',NULL,'Statutory Default',1,'Work on special non-working holiday falling on employee rest day (additional 50%)','2026-10-02 03:56:24','2026-10-02 03:56:24'),(4,'REGULAR_HOLIDAY','Regular Holiday','Regular Holiday',200.00,200.00,100.00,1.30,1.10,'2026-01-01',NULL,'Statutory Default',1,'Work performed on a regular holiday (200% of basic daily rate for first 8 hours)','2026-10-02 03:56:24','2026-10-02 03:56:24'),(5,'REGULAR_HOLIDAY_REST_DAY','Regular Holiday + Rest Day','Regular Holiday',260.00,200.00,130.00,1.30,1.10,'2026-01-01',NULL,'Statutory Default',1,'Work performed on a regular holiday falling on employee scheduled rest day (260% of basic daily rate)','2026-10-02 03:56:24','2026-10-02 03:56:24'),(6,'OVERTIME','Daily Overtime','Overtime',125.00,NULL,NULL,1.25,NULL,'2026-01-01',NULL,'Statutory Default',1,'Work beyond 8 hours on regular workday (additional 25% of hourly rate)','2026-10-02 03:56:24','2026-10-02 03:56:24'),(7,'NIGHT_SHIFT','Night Shift Differential (NSD)','Night Shift',110.00,NULL,NULL,NULL,1.10,'2026-01-01',NULL,'Statutory Default',1,'Work performed between 10:00 PM and 6:00 AM (additional 10% of regular hourly rate)','2026-10-02 03:56:24','2026-10-02 03:56:24');
/*!40000 ALTER TABLE `hr_premium_pay_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_salary_history`
--

DROP TABLE IF EXISTS `hr_salary_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_salary_history` (
  `salary_history_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `effective_date` date NOT NULL,
  `basic_salary` decimal(12,2) NOT NULL,
  `salary_frequency` varchar(30) NOT NULL DEFAULT 'Monthly',
  `employment_type` varchar(50) DEFAULT NULL,
  `reason` varchar(100) DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`salary_history_id`),
  KEY `hr_salary_history_employee_id_foreign` (`employee_id`),
  KEY `hr_salary_history_approved_by_foreign` (`approved_by`),
  CONSTRAINT `hr_salary_history_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `hr_salary_history_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`employee_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_salary_history`
--

LOCK TABLES `hr_salary_history` WRITE;
/*!40000 ALTER TABLE `hr_salary_history` DISABLE KEYS */;
INSERT INTO `hr_salary_history` VALUES (1,1,'2023-01-15',42000.00,'Semi-Monthly','Probationary','Hiring',1,'Initial hiring rate agreed per contract.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(2,1,'2023-07-15',45000.00,'Semi-Monthly','Regular','Regularization',1,'Salary adjustment upon passing probationary performance review.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(3,2,'2023-03-01',29000.00,'Semi-Monthly','Probationary','Hiring',1,'Initial hiring rate agreed per contract.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(4,2,'2023-09-01',32000.00,'Semi-Monthly','Regular','Regularization',1,'Salary adjustment upon passing probationary performance review.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(5,3,'2024-02-15',18000.00,'Semi-Monthly','Probationary','Hiring',1,'Initial hiring rate agreed per contract.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(6,3,'2024-08-15',20000.00,'Semi-Monthly','Regular','Regularization',1,'Salary adjustment upon passing probationary performance review.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(7,4,'2026-04-01',18000.00,'Semi-Monthly','Probationary','Hiring',1,'Initial hiring rate agreed per contract.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(8,5,'2023-06-01',42000.00,'Semi-Monthly','Probationary','Hiring',1,'Initial hiring rate agreed per contract.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(9,5,'2023-12-01',45000.00,'Semi-Monthly','Regular','Regularization',1,'Salary adjustment upon passing probationary performance review.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(10,6,'2024-01-10',19000.00,'Semi-Monthly','Probationary','Hiring',1,'Initial hiring rate agreed per contract.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(11,6,'2024-07-10',22000.00,'Semi-Monthly','Regular','Regularization',1,'Salary adjustment upon passing probationary performance review.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(12,7,'2026-05-01',18000.00,'Semi-Monthly','Probationary','Hiring',1,'Initial hiring rate agreed per contract.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(13,8,'2023-08-01',42000.00,'Semi-Monthly','Probationary','Hiring',1,'Initial hiring rate agreed per contract.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(14,8,'2024-02-01',45000.00,'Semi-Monthly','Regular','Regularization',1,'Salary adjustment upon passing probationary performance review.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(15,9,'2024-04-10',18000.00,'Semi-Monthly','Probationary','Hiring',1,'Initial hiring rate agreed per contract.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(16,10,'2025-01-15',18000.00,'Semi-Monthly','Probationary','Hiring',1,'Initial hiring rate agreed per contract.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(17,64,'2022-01-01',72000.00,'Semi-Monthly','Probationary','Hiring',1,'Initial hiring rate agreed per contract.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(18,64,'2022-07-01',75000.00,'Semi-Monthly','Regular','Regularization',1,'Salary adjustment upon passing probationary performance review.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(19,65,'2022-03-01',57000.00,'Semi-Monthly','Probationary','Hiring',1,'Initial hiring rate agreed per contract.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(20,65,'2022-09-01',60000.00,'Semi-Monthly','Regular','Regularization',1,'Salary adjustment upon passing probationary performance review.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(21,66,'2023-01-01',47000.00,'Semi-Monthly','Probationary','Hiring',1,'Initial hiring rate agreed per contract.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(22,66,'2023-07-01',50000.00,'Semi-Monthly','Regular','Regularization',1,'Salary adjustment upon passing probationary performance review.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(23,67,'2024-03-01',18000.00,'Semi-Monthly','Probationary','Hiring',1,'Initial hiring rate agreed per contract.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(24,67,'2024-09-01',21000.00,'Semi-Monthly','Regular','Regularization',1,'Salary adjustment upon passing probationary performance review.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(25,68,'2025-02-01',18000.00,'Semi-Monthly','Probationary','Hiring',1,'Initial hiring rate agreed per contract.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(26,69,'2023-09-15',21000.00,'Semi-Monthly','Probationary','Hiring',1,'Initial hiring rate agreed per contract.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(27,69,'2024-03-15',24000.00,'Semi-Monthly','Regular','Regularization',1,'Salary adjustment upon passing probationary performance review.','2026-09-30 08:47:10','2026-09-30 08:47:10'),(28,1,'2026-03-01',38000.00,'Semi-Monthly','Regular','Merit Increase',1,'Outstanding Q1 appraisal','2026-09-30 09:05:49','2026-09-30 09:05:49'),(29,1,'2026-03-01',38000.00,'Semi-Monthly','Regular','Merit Increase',1,'Outstanding Q1 appraisal','2026-09-30 09:06:11','2026-09-30 09:06:11');
/*!40000 ALTER TABLE `hr_salary_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_schedule_change_logs`
--

DROP TABLE IF EXISTS `hr_schedule_change_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_schedule_change_logs` (
  `schedule_change_log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `schedule_date` date NOT NULL,
  `previous_shift_template_id` bigint(20) unsigned DEFAULT NULL,
  `new_shift_template_id` bigint(20) unsigned DEFAULT NULL,
  `previous_time_range` varchar(100) DEFAULT NULL,
  `new_time_range` varchar(100) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`schedule_change_log_id`),
  KEY `hr_schedule_change_logs_employee_id_foreign` (`employee_id`),
  KEY `hr_schedule_change_logs_changed_by_foreign` (`changed_by`),
  CONSTRAINT `hr_schedule_change_logs_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `hr_schedule_change_logs_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`employee_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_schedule_change_logs`
--

LOCK TABLES `hr_schedule_change_logs` WRITE;
/*!40000 ALTER TABLE `hr_schedule_change_logs` DISABLE KEYS */;
INSERT INTO `hr_schedule_change_logs` VALUES (41,5,'2026-10-05',NULL,NULL,'Unassigned','08:00 - 17:00','Assigned Shift',1,'2026-10-09 02:18:29','2026-10-09 02:18:29'),(42,5,'2026-10-06',NULL,NULL,'Unassigned','RESTDAY','Assigned Rest Day',1,'2026-10-09 02:18:30','2026-10-09 02:18:30'),(43,5,'2026-10-07',NULL,NULL,'Unassigned','12:00 - 21:00','Assigned Shift',1,'2026-10-09 02:18:31','2026-10-09 02:18:31'),(44,5,'2026-10-08',NULL,NULL,'Unassigned','12:00 - 21:00','Assigned Shift',1,'2026-10-09 02:18:32','2026-10-09 02:18:32'),(45,5,'2026-10-09',NULL,NULL,'Unassigned','RESTDAY','Assigned Rest Day',1,'2026-10-09 02:18:34','2026-10-09 02:18:34'),(46,5,'2026-10-10',NULL,NULL,'Unassigned','13:00 - 22:00','Assigned Shift',1,'2026-10-09 02:18:35','2026-10-09 02:18:35'),(47,5,'2026-10-11',NULL,NULL,'Unassigned','13:00 - 22:00','Assigned Shift',1,'2026-10-09 02:18:36','2026-10-09 02:18:36'),(48,1,'2026-10-11',NULL,NULL,'Unassigned','13:00 - 22:00','Assigned Shift',1,'2026-10-09 02:18:37','2026-10-09 02:18:37'),(49,1,'2026-10-05',NULL,NULL,'Unassigned','RESTDAY','Assigned Rest Day',1,'2026-10-09 02:18:38','2026-10-09 02:18:38'),(50,1,'2026-10-06',NULL,NULL,'Unassigned','RESTDAY','Assigned Rest Day',1,'2026-10-09 02:18:39','2026-10-09 02:18:39'),(51,1,'2026-10-07',NULL,NULL,'Unassigned','RESTDAY','Assigned Rest Day',1,'2026-10-09 02:18:39','2026-10-09 02:18:39'),(52,1,'2026-10-08',NULL,NULL,'Unassigned','RESTDAY','Assigned Rest Day',1,'2026-10-09 02:18:40','2026-10-09 02:18:40'),(53,1,'2026-10-09',NULL,NULL,'Unassigned','10:00 - 19:00','Assigned Shift',1,'2026-10-09 02:18:41','2026-10-09 02:18:41'),(54,1,'2026-10-10',NULL,NULL,'Unassigned','RESTDAY','Assigned Rest Day',1,'2026-10-09 02:18:41','2026-10-09 02:18:41');
/*!40000 ALTER TABLE `hr_schedule_change_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_separation`
--

DROP TABLE IF EXISTS `hr_separation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_separation` (
  `separation_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `separation_type` varchar(50) NOT NULL,
  `separation_date` date NOT NULL,
  `last_working_day` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `eligible_for_rehire` tinyint(1) NOT NULL DEFAULT 1,
  `processed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`separation_id`),
  KEY `hr_separation_employee_id_foreign` (`employee_id`),
  KEY `hr_separation_processed_by_foreign` (`processed_by`),
  CONSTRAINT `hr_separation_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`employee_id`) ON DELETE CASCADE,
  CONSTRAINT `hr_separation_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_separation`
--

LOCK TABLES `hr_separation` WRITE;
/*!40000 ALTER TABLE `hr_separation` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr_separation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_shift_templates`
--

DROP TABLE IF EXISTS `hr_shift_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_shift_templates` (
  `shift_template_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `code` varchar(20) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_overnight` tinyint(1) NOT NULL DEFAULT 0,
  `break_minutes` int(10) unsigned NOT NULL DEFAULT 60,
  `color` varchar(20) NOT NULL DEFAULT '#a855f7',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`shift_template_id`),
  UNIQUE KEY `shift_templates_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=153 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_shift_templates`
--

LOCK TABLES `hr_shift_templates` WRITE;
/*!40000 ALTER TABLE `hr_shift_templates` DISABLE KEYS */;
INSERT INTO `hr_shift_templates` VALUES (1,'0600 = 6AM - 3PM','0600','06:00:00','15:00:00',0,60,'#10b981',NULL,'2026-09-23 10:40:49','2026-09-24 09:37:09'),(2,'1400 = 2PM - 11PM','1400','14:00:00','23:00:00',0,60,'#f97316',NULL,'2026-09-23 10:40:49','2026-09-24 09:37:09'),(3,'2200 = 10PM - 7AM','2200','22:00:00','07:00:00',1,60,'#8b5cf6',NULL,'2026-09-23 10:40:49','2026-09-24 09:37:09'),(4,'0700 = 7AM - 4PM','0700','07:00:00','16:00:00',0,60,'#06b6d4',NULL,'2026-09-24 00:19:42','2026-09-24 00:19:42'),(5,'0800 = 8AM - 5PM','0800','08:00:00','17:00:00',0,60,'#3b82f6',NULL,'2026-09-24 00:19:42','2026-09-24 00:19:42'),(6,'0900 = 9AM - 6PM','0900','09:00:00','18:00:00',0,60,'#6366f1',NULL,'2026-09-24 00:19:42','2026-09-24 00:19:42'),(7,'1000 = 10AM - 7PM','1000','10:00:00','19:00:00',0,60,'#8b5cf6',NULL,'2026-09-24 00:19:42','2026-09-24 00:19:42'),(8,'1100 = 11AM - 8PM','1100','11:00:00','20:00:00',0,60,'#a855f7',NULL,'2026-09-24 00:19:42','2026-09-24 00:19:42'),(9,'1200 = 12PM - 9PM','1200','12:00:00','21:00:00',0,60,'#ec4899',NULL,'2026-09-24 00:19:42','2026-09-24 00:19:42'),(10,'1300 = 1PM - 10PM','1300','13:00:00','22:00:00',0,60,'#f43f5e',NULL,'2026-09-24 00:19:42','2026-09-24 00:19:42'),(11,'1500 = 3PM - 12AM','1500','15:00:00','00:00:00',1,60,'#eab308',NULL,'2026-09-24 00:19:42','2026-09-24 00:19:42'),(12,'1600 = 4PM - 1AM','1600','16:00:00','01:00:00',1,60,'#84cc16',NULL,'2026-09-24 00:19:42','2026-09-24 00:19:42'),(13,'1700 = 5PM - 2AM','1700','17:00:00','02:00:00',1,60,'#14b8a6',NULL,'2026-09-24 00:19:42','2026-09-24 00:19:42'),(14,'1800 = 6PM - 3AM','1800','18:00:00','03:00:00',1,60,'#0ea5e9',NULL,'2026-09-24 00:19:42','2026-09-24 00:19:42'),(15,'1900 = 7PM - 4AM','1900','19:00:00','04:00:00',1,60,'#6366f1',NULL,'2026-09-24 00:19:42','2026-09-24 00:19:42'),(16,'2000 = 8PM - 5AM','2000','20:00:00','05:00:00',1,60,'#8b5cf6',NULL,'2026-09-24 00:19:42','2026-09-24 00:19:42'),(17,'2100 = 9PM - 6AM','2100','21:00:00','06:00:00',1,60,'#a855f7',NULL,'2026-09-24 00:19:42','2026-09-24 00:19:42'),(18,'2300 = 11PM - 8AM','2300','23:00:00','08:00:00',1,60,'#7e22ce',NULL,'2026-09-24 00:19:42','2026-09-24 00:19:42');
/*!40000 ALTER TABLE `hr_shift_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_statutory_contribution_rules`
--

DROP TABLE IF EXISTS `hr_statutory_contribution_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_statutory_contribution_rules` (
  `statutory_contribution_rule_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rule_name` varchar(100) NOT NULL,
  `rule_type` enum('SSS','PhilHealth','PagIBIG','BIR_Tax') NOT NULL,
  `rate` decimal(6,4) NOT NULL DEFAULT 0.0000,
  `min_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `employee_share` decimal(6,4) NOT NULL DEFAULT 0.0000,
  `employer_share` decimal(6,4) NOT NULL DEFAULT 0.0000,
  `fixed_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bracket_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bracket_json`)),
  `effective_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`statutory_contribution_rule_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_statutory_contribution_rules`
--

LOCK TABLES `hr_statutory_contribution_rules` WRITE;
/*!40000 ALTER TABLE `hr_statutory_contribution_rules` DISABLE KEYS */;
INSERT INTO `hr_statutory_contribution_rules` VALUES (1,'SSS Standard Table 2024-2026','SSS',0.1400,4000.00,30000.00,0.0450,0.0950,0.00,NULL,'2024-01-01',NULL,1,'2026-09-23 10:40:49','2026-09-23 10:40:49'),(2,'PhilHealth Universal Health Care (5%)','PhilHealth',0.0500,10000.00,100000.00,0.5000,0.5000,0.00,NULL,'2024-01-01',NULL,1,'2026-09-23 10:40:49','2026-09-23 10:40:49'),(3,'Pag-IBIG Fund Modern Maximum','PagIBIG',0.0000,0.00,0.00,0.0000,0.0000,200.00,NULL,'2024-02-01',NULL,1,'2026-09-23 10:40:59','2026-09-23 10:40:59'),(4,'BIR Revised Withholding Tax (TRAIN Law)','BIR_Tax',0.0000,0.00,0.00,0.0000,0.0000,0.00,NULL,'2023-01-01',NULL,1,'2026-09-23 10:40:59','2026-09-23 10:40:59');
/*!40000 ALTER TABLE `hr_statutory_contribution_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_training_enrollments`
--

DROP TABLE IF EXISTS `hr_training_enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_training_enrollments` (
  `training_enrollment_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `training_program_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `enrollment_date` date NOT NULL,
  `attendance_status` enum('Present','Absent','Excused') NOT NULL DEFAULT 'Present',
  `completion_status` enum('Assigned','Scheduled','In Progress','Completed','Failed','Cancelled') NOT NULL DEFAULT 'Scheduled',
  `score` decimal(5,2) DEFAULT NULL,
  `certificate_path` varchar(255) DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`training_enrollment_id`),
  UNIQUE KEY `training_enrollments_training_program_id_employee_id_unique` (`training_program_id`,`employee_id`),
  KEY `training_enrollments_employee_id_foreign` (`employee_id`),
  CONSTRAINT `training_enrollments_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`employee_id`) ON DELETE CASCADE,
  CONSTRAINT `training_enrollments_training_program_id_foreign` FOREIGN KEY (`training_program_id`) REFERENCES `hr_training_programs` (`training_program_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_training_enrollments`
--

LOCK TABLES `hr_training_enrollments` WRITE;
/*!40000 ALTER TABLE `hr_training_enrollments` DISABLE KEYS */;
INSERT INTO `hr_training_enrollments` VALUES (1,1,1,'2026-09-05','Present','Completed',95.00,NULL,'2026-09-11','Successfully passed restaurant food sanitation exam.','2026-09-23 10:41:27','2026-09-23 10:41:27'),(2,1,2,'2026-09-05','Present','Completed',95.00,NULL,'2026-09-11','Successfully passed restaurant food sanitation exam.','2026-09-23 10:41:27','2026-09-23 10:41:27'),(3,1,3,'2026-09-05','Present','Completed',95.00,NULL,'2026-09-11','Successfully passed restaurant food sanitation exam.','2026-09-23 10:41:27','2026-09-23 10:41:27'),(4,1,4,'2026-09-05','Present','Completed',95.00,NULL,'2026-09-11','Successfully passed restaurant food sanitation exam.','2026-09-23 10:41:27','2026-09-23 10:41:27'),(5,1,5,'2026-09-05','Present','Completed',95.00,NULL,'2026-09-11','Successfully passed restaurant food sanitation exam.','2026-09-23 10:41:27','2026-09-23 10:41:27'),(6,1,6,'2026-09-05','Present','Completed',95.00,NULL,'2026-09-11','Successfully passed restaurant food sanitation exam.','2026-09-23 10:41:27','2026-09-23 10:41:27'),(7,1,7,'2026-09-05','Present','Completed',95.00,NULL,'2026-09-11','Successfully passed restaurant food sanitation exam.','2026-09-23 10:41:27','2026-09-23 10:41:27'),(8,1,8,'2026-09-05','Present','Completed',95.00,NULL,'2026-09-11','Successfully passed restaurant food sanitation exam.','2026-09-23 10:41:27','2026-09-23 10:41:27'),(9,1,9,'2026-09-05','Present','Completed',95.00,NULL,'2026-09-11','Successfully passed restaurant food sanitation exam.','2026-09-23 10:41:27','2026-09-23 10:41:27'),(10,1,10,'2026-09-05','Present','Completed',95.00,NULL,'2026-09-11','Successfully passed restaurant food sanitation exam.','2026-09-23 10:41:27','2026-09-23 10:41:27'),(11,1,64,'2026-09-05','Present','Completed',95.00,NULL,'2026-09-11','Successfully passed restaurant food sanitation exam.','2026-09-24 09:37:10','2026-09-24 09:37:10'),(12,1,65,'2026-09-05','Present','Completed',95.00,NULL,'2026-09-11','Successfully passed restaurant food sanitation exam.','2026-09-24 09:37:11','2026-09-24 09:37:11'),(13,1,66,'2026-09-05','Present','Completed',95.00,NULL,'2026-09-11','Successfully passed restaurant food sanitation exam.','2026-09-24 09:37:11','2026-09-24 09:37:11'),(14,1,67,'2026-09-05','Present','Completed',95.00,NULL,'2026-09-11','Successfully passed restaurant food sanitation exam.','2026-09-24 09:37:11','2026-09-24 09:37:11'),(15,1,68,'2026-09-05','Present','Completed',95.00,NULL,'2026-09-11','Successfully passed restaurant food sanitation exam.','2026-09-24 09:37:11','2026-09-24 09:37:11'),(16,1,69,'2026-09-05','Present','Completed',95.00,NULL,'2026-09-11','Successfully passed restaurant food sanitation exam.','2026-09-24 09:37:11','2026-09-24 09:37:11');
/*!40000 ALTER TABLE `hr_training_enrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_training_programs`
--

DROP TABLE IF EXISTS `hr_training_programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_training_programs` (
  `training_program_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `trainer` varchar(100) DEFAULT NULL,
  `training_type` enum('Internal','External','Online') NOT NULL DEFAULT 'Internal',
  `location` varchar(150) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `duration_hours` smallint(5) unsigned NOT NULL DEFAULT 8,
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`training_program_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_training_programs`
--

LOCK TABLES `hr_training_programs` WRITE;
/*!40000 ALTER TABLE `hr_training_programs` DISABLE KEYS */;
INSERT INTO `hr_training_programs` VALUES (1,'HACCP & Restaurant Food Safety Standards','Mandatory sanitation, temperature logs, allergen management and cross-contamination prevention.','Chef Anthony Rivera (Certified Food Safety Specialist)','Internal','Makati Training Kitchen','2026-09-10','2026-09-11',8,5000.00,'Completed','2026-09-23 10:40:59','2026-09-23 10:40:59'),(2,'Hospitality & Guest Experience Mastery','Service standards, customer conflict resolution, wine and menu pairing.','Maria Santos (F&B Consultant)','Internal','BGC Dining Hall','2026-09-28','2026-09-29',6,4500.00,'Scheduled','2026-09-23 10:40:59','2026-09-23 10:40:59');
/*!40000 ALTER TABLE `hr_training_programs` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-11 21:30:42
