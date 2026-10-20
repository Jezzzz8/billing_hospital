-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 20, 2026 at 01:50 PM
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
-- Database: `billing_hospital`
--

-- --------------------------------------------------------

--
-- Table structure for table `admission`
--

CREATE TABLE `admission` (
  `admission_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL,
  `admission_datetime` datetime NOT NULL,
  `discharge_datetime` datetime DEFAULT NULL,
  `chief_complaint` varchar(255) DEFAULT NULL,
  `admission_type` varchar(50) DEFAULT NULL,
  `total_room_transfers` int(11) NOT NULL DEFAULT 0,
  `admitted_by_user_id` int(11) DEFAULT NULL,
  `discharged_by_user_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admission`
--

INSERT INTO `admission` (`admission_id`, `patient_id`, `status_id`, `admission_datetime`, `discharge_datetime`, `chief_complaint`, `admission_type`, `total_room_transfers`, `admitted_by_user_id`, `discharged_by_user_id`, `notes`) VALUES
(1, 1, 2, '2026-10-06 21:46:30', '2026-10-20 13:19:02', 'Gi kulata', 'Emergency', 0, 3, 3, 'Preferred room type: Deluxe Suite. [preferred_room_type_id=4]\n\n[2026-10-20 07:12] Doctor cleared patient for discharge. '),
(2, 2, 2, '2026-10-16 15:38:04', '2026-10-20 11:13:55', 'Napiang', 'Emergency', 0, 3, 3, 'Preferred room type: Ward. [preferred_room_type_id=1]\n\n[2026-10-20 04:56] Doctor cleared patient for discharge.'),
(3, 3, 2, '2026-10-20 11:45:59', '2026-10-20 12:13:19', 'Labad daw ang ulo', 'Emergency', 0, 3, 3, 'Preferred room type: Deluxe Suite. [preferred_room_type_id=4]\n\n[2026-10-20 06:11] Doctor cleared patient for discharge. '),
(4, 4, 2, '2026-10-20 11:52:20', '2026-10-20 12:21:09', 'Ambot naunsa ni sya', 'Emergency', 0, 3, 3, 'Preferred room type: Semi-Private. [preferred_room_type_id=2]\n\n[2026-10-20 06:20] Doctor cleared patient for discharge. '),
(5, 4, 2, '2026-10-20 12:23:31', '2026-10-20 12:35:52', 'Ambot naunsa ni sya', 'Emergency', 0, 3, 3, 'Preferred room type: Ward. [preferred_room_type_id=1]\n\n[2026-10-20 06:28] Doctor cleared patient for discharge.'),
(6, 3, 2, '2026-10-20 12:36:10', '2026-10-20 12:49:49', 'Labad daw ang ulo', 'Emergency', 0, 3, 3, 'Preferred room type: Ward. [preferred_room_type_id=1]\n\n[2026-10-20 06:38] Doctor cleared patient for discharge. '),
(7, 5, 2, '2026-10-20 16:36:16', '2026-10-20 19:09:43', 'Sakit daw ang itlog', 'Emergency', 0, 3, 3, 'Preferred room type: NICU. [preferred_room_type_id=6]\n\n[2026-10-20 11:51] Doctor cleared patient for discharge. ');

-- --------------------------------------------------------

--
-- Table structure for table `admission_diagnosis`
--

CREATE TABLE `admission_diagnosis` (
  `admission_diagnosis_id` int(11) NOT NULL,
  `admission_id` int(11) NOT NULL,
  `diagnosis_id` int(11) NOT NULL,
  `diagnosis_type` varchar(50) DEFAULT NULL,
  `diagnosed_datetime` datetime NOT NULL,
  `diagnosed_by_doctor_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admission_diagnosis`
--

INSERT INTO `admission_diagnosis` (`admission_diagnosis_id`, `admission_id`, `diagnosis_id`, `diagnosis_type`, `diagnosed_datetime`, `diagnosed_by_doctor_id`) VALUES
(5, 4, 38, 'Primary', '2026-10-20 12:01:51', 1),
(6, 3, 10, 'Primary', '2026-10-20 12:02:10', 1),
(8, 6, 30, 'Primary', '2026-10-20 12:36:45', 1),
(11, 1, 27, 'Primary', '2026-10-20 13:08:45', NULL),
(12, 2, 49, 'Primary', '2026-10-20 13:10:55', NULL),
(13, 5, 22, 'Primary', '2026-10-20 13:11:12', NULL),
(14, 7, 22, 'Primary', '2026-10-20 16:37:13', 1);

-- --------------------------------------------------------

--
-- Table structure for table `admission_doctor`
--

CREATE TABLE `admission_doctor` (
  `admission_doctor_id` int(11) NOT NULL,
  `admission_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `doctor_role` varchar(100) DEFAULT NULL,
  `assigned_datetime` datetime NOT NULL,
  `ended_datetime` datetime DEFAULT NULL,
  `consultation_fee_charged` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admission_doctor`
--

INSERT INTO `admission_doctor` (`admission_doctor_id`, `admission_id`, `doctor_id`, `doctor_role`, `assigned_datetime`, `ended_datetime`, `consultation_fee_charged`) VALUES
(6, 3, 2, 'Consulting', '2026-10-20 11:45:59', NULL, 1000.00),
(7, 3, 1, 'Attending', '2026-10-20 11:45:59', NULL, 800.00),
(8, 4, 1, 'Attending', '2026-10-20 11:52:20', NULL, 800.00),
(10, 6, 1, 'Attending', '2026-10-20 12:36:10', NULL, 800.00),
(15, 1, 2, 'Consulting', '2026-10-20 13:08:45', NULL, 1000.00),
(16, 1, 1, 'Attending', '2026-10-20 13:08:45', NULL, 800.00),
(17, 2, 1, 'Attending', '2026-10-20 13:10:55', NULL, 800.00),
(18, 5, 1, 'Attending', '2026-10-20 13:11:12', NULL, 800.00),
(19, 7, 1, 'Attending', '2026-10-20 16:36:16', NULL, 800.00);

-- --------------------------------------------------------

--
-- Table structure for table `admission_status`
--

CREATE TABLE `admission_status` (
  `status_id` int(11) NOT NULL,
  `status_name` varchar(50) NOT NULL,
  `color_code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admission_status`
--

INSERT INTO `admission_status` (`status_id`, `status_name`, `color_code`) VALUES
(1, 'Admitted', '#2a64c0'),
(2, 'Discharged', '#5cbe27'),
(3, 'Transferred', '#f73b3b'),
(4, 'Ready for Discharge', '#10b981');

-- --------------------------------------------------------

--
-- Table structure for table `billing_statement`
--

CREATE TABLE `billing_statement` (
  `statement_id` int(11) NOT NULL,
  `admission_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL,
  `statement_date` datetime NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `insurance_coverage_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `applied_insurance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `government_discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `applied_discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `philhealth_deduction` decimal(12,2) NOT NULL DEFAULT 0.00,
  `applied_philhealth` decimal(12,2) NOT NULL DEFAULT 0.00,
  `hmo_discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `applied_hmo` decimal(12,2) NOT NULL DEFAULT 0.00,
  `case_type` varchar(50) DEFAULT NULL,
  `patient_type` varchar(50) DEFAULT NULL,
  `phic_status` varchar(50) DEFAULT NULL,
  `hmo_name` varchar(150) DEFAULT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `patient_age` int(11) DEFAULT NULL,
  `patient_gender` varchar(50) DEFAULT NULL,
  `patient_address` varchar(255) DEFAULT NULL,
  `room_number` varchar(50) DEFAULT NULL,
  `room_type_name` varchar(100) DEFAULT NULL,
  `room_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `attending_physician` varchar(255) DEFAULT NULL,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by_user_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing_statement`
--

INSERT INTO `billing_statement` (`statement_id`, `admission_id`, `status_id`, `statement_date`, `due_date`, `subtotal_amount`, `insurance_coverage_amount`, `applied_insurance`, `government_discount`, `applied_discount`, `philhealth_deduction`, `applied_philhealth`, `hmo_discount`, `applied_hmo`, `case_type`, `patient_type`, `phic_status`, `hmo_name`, `company_name`, `patient_age`, `patient_gender`, `patient_address`, `room_number`, `room_type_name`, `room_rate`, `attending_physician`, `tax_amount`, `total_amount`, `amount_paid`, `balance_amount`, `created_at`, `created_by_user_id`, `notes`) VALUES
(1, 1, 2, '2026-10-06 21:46:30', NULL, 72052.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'In Patient', 'In Patient', 'Non-Member', NULL, NULL, 26, 'Male', 'Xavier Heights, Uptown, CdeO', '401', 'Deluxe Suite', 4500.00, 'Dr. Rafael Santos, Dr. Marco Reyes', 126.24, 72178.24, 25000.80, 47177.44, '2026-10-06 21:46:30', 3, NULL),
(2, 2, 1, '2026-10-16 15:38:04', NULL, 7998.00, 0.00, 0.00, 0.00, 0.00, 1000.00, 1000.00, 0.00, 0.00, 'In Patient', 'In Patient', 'Non-Member', NULL, NULL, 19, 'Male', 'Cdeo, Mis Or', '201', 'Ward', 800.00, 'Dr. Rafael Santos', 239.76, 7237.76, 0.00, 7237.76, '2026-10-16 15:38:04', 3, NULL),
(3, 3, 1, '2026-10-20 11:45:59', NULL, 6750.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'In Patient', 'In Patient', 'Non-Member', NULL, NULL, 21, 'Male', 'Bayabas, Cagayan de Oro City, Misamis Oriental', '401', 'Deluxe Suite', 4500.00, 'Dr. Marco Reyes, Dr. Rafael Santos', 54.00, 6804.00, 0.00, 6804.00, '2026-10-20 11:45:59', 3, NULL),
(4, 4, 1, '2026-10-20 11:52:20', NULL, 2420.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'In Patient', 'In Patient', 'Non-Member', NULL, NULL, 21, 'Male', 'Zayas, Cagayan de Oro City, Misamis Oriental', '205', 'Semi-Private', 1500.00, 'Dr. Rafael Santos', 14.40, 2434.40, 0.00, 2434.40, '2026-10-20 11:52:20', 3, NULL),
(5, 5, 1, '2026-10-20 12:23:31', NULL, 2440.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'In Patient', 'In Patient', 'Non-Member', NULL, NULL, 21, 'Male', 'Zayas, Cagayan de Oro City, Misamis Oriental', '201', 'Ward', 800.00, 'Dr. Rafael Santos', 4.80, 2444.80, 0.00, 2444.80, '2026-10-20 12:23:31', 3, NULL),
(6, 6, 3, '2026-10-20 12:36:10', NULL, 1640.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'In Patient', 'In Patient', 'Non-Member', NULL, NULL, 21, 'Male', 'Bayabas, Cagayan de Oro City, Misamis Oriental', '201', 'Ward', 800.00, 'Dr. Rafael Santos', 4.80, 1644.80, 1644.80, 0.00, '2026-10-20 12:36:10', 3, NULL),
(7, 7, 1, '2026-10-20 16:36:16', NULL, 7872.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'In Patient', 'In Patient', 'Non-Member', NULL, NULL, 21, 'Other', 'Sto. Niño Street', 'NICU-1', 'NICU', 7000.00, 'Dr. Rafael Santos', 8.64, 7880.64, 0.00, 7880.64, '2026-10-20 16:36:16', 3, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `billing_status`
--

CREATE TABLE `billing_status` (
  `status_id` int(11) NOT NULL,
  `status_name` varchar(50) NOT NULL,
  `color_code` varchar(20) DEFAULT NULL,
  `is_paid_status` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing_status`
--

INSERT INTO `billing_status` (`status_id`, `status_name`, `color_code`, `is_paid_status`) VALUES
(1, 'Pending', '#d3a51d', 0),
(2, 'Partially Paid', '#17a2b8', 0),
(3, 'Paid', '#28a745', 1),
(4, 'Overdue', '#dc3545', 0);

-- --------------------------------------------------------

--
-- Table structure for table `building`
--

CREATE TABLE `building` (
  `building_id` int(11) NOT NULL,
  `building_name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `building`
--

INSERT INTO `building` (`building_id`, `building_name`, `description`) VALUES
(1, 'Main Building', 'Primary hospital building housing wards, private rooms, and the emergency department'),
(2, 'Medical Arts Building', 'Outpatient consultations, specialty clinics, and diagnostic services');

-- --------------------------------------------------------

--
-- Table structure for table `charge`
--

CREATE TABLE `charge` (
  `charge_id` int(11) NOT NULL,
  `statement_id` int(11) NOT NULL,
  `charge_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `actual_price` decimal(10,2) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `charge_datetime` datetime NOT NULL,
  `processed_by_user_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `service_start_date` date DEFAULT NULL,
  `service_end_date` date DEFAULT NULL,
  `source_type` varchar(50) NOT NULL DEFAULT 'MANUAL',
  `room_assignment_id` int(11) DEFAULT NULL,
  `fulfillment_item_id` int(11) DEFAULT NULL,
  `admission_doctor_id` int(11) DEFAULT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `charge`
--

INSERT INTO `charge` (`charge_id`, `statement_id`, `charge_item_id`, `quantity`, `actual_price`, `amount`, `charge_datetime`, `processed_by_user_id`, `notes`, `service_start_date`, `service_end_date`, `source_type`, `room_assignment_id`, `fulfillment_item_id`, `admission_doctor_id`, `created_by_user_id`, `created_at`) VALUES
(1, 1, 4, 11, 4500.00, 0.00, '2026-10-06 21:46:30', 3, 'Room 401 (Deluxe Suite) — source=room:1', '2026-10-06', '2026-10-16', 'ROOM', 1, NULL, NULL, NULL, '2026-10-06 21:46:30'),
(2, 1, 45, 1, 1000.00, 0.00, '2026-10-06 21:46:30', 3, 'Consultation — Dr. Marco Reyes — source=doctor:1', NULL, NULL, 'PROFESSIONAL_FEE', NULL, NULL, 1, NULL, '2026-10-06 21:46:30'),
(3, 1, 45, 1, 800.00, 0.00, '2026-10-06 21:46:30', 3, 'Consultation — Dr. Rafael Santos — source=doctor:2', NULL, NULL, 'PROFESSIONAL_FEE', NULL, NULL, 2, NULL, '2026-10-06 21:46:30'),
(4, 1, 2, 5, 2500.00, 0.00, '2026-10-16 13:43:44', 3, 'Room 304 (Private) — source=room:2', '2026-10-16', '2026-10-20', 'ROOM', 2, NULL, NULL, NULL, '2026-10-16 13:43:44'),
(5, 1, 16, 1, 900.00, 900.00, '2026-10-16 14:50:59', 3, 'Fulfillment item #1 (Abdominal X-Ray) — source=fulfillment:1', NULL, NULL, 'FULFILLMENT', NULL, 1, NULL, NULL, '2026-10-16 14:50:59'),
(6, 1, 34, 10, 14.00, 140.00, '2026-10-16 15:15:06', 3, 'Fulfillment item #2 (Losartan 50mg Tablet) — source=fulfillment:2', NULL, NULL, 'FULFILLMENT', NULL, 2, NULL, NULL, '2026-10-16 15:15:06'),
(7, 2, 1, 5, 800.00, 0.00, '2026-10-16 15:38:04', 3, 'Room 201 (Ward) — source=room:3', '2026-10-16', '2026-10-20', 'ROOM', 3, NULL, NULL, NULL, '2026-10-16 15:38:04'),
(8, 2, 45, 1, 800.00, 0.00, '2026-10-16 15:38:04', 3, 'Consultation — Dr. Rafael Santos — source=doctor:3', NULL, NULL, 'PROFESSIONAL_FEE', NULL, NULL, 3, NULL, '2026-10-16 15:38:04'),
(9, 2, 29, 10, 15.00, 150.00, '2026-10-16 15:43:38', 3, 'Fulfillment item #3 (Amoxicillin 500mg Capsule) — source=fulfillment:3', NULL, NULL, 'FULFILLMENT', NULL, 3, NULL, NULL, '2026-10-16 15:43:38'),
(10, 2, 32, 10, 18.00, 180.00, '2026-10-16 15:43:52', 3, 'Fulfillment item #4 (Omeprazole 20mg Capsule) — source=fulfillment:4', NULL, NULL, 'FULFILLMENT', NULL, 4, NULL, NULL, '2026-10-16 15:43:52'),
(11, 2, 28, 10, 12.00, 120.00, '2026-10-16 15:43:59', 3, 'Fulfillment item #5 (Mefenamic Acid 500mg Capsule) — source=fulfillment:5', NULL, NULL, 'FULFILLMENT', NULL, 5, NULL, NULL, '2026-10-16 15:43:59'),
(12, 1, 45, 1, 800.00, 0.00, '2026-10-19 16:24:45', 1, 'Consultation — Dr. Rafael Santos — source=doctor:4', NULL, NULL, 'PROFESSIONAL_FEE', NULL, NULL, 4, NULL, '2026-10-19 16:24:45'),
(13, 1, 45, 1, 1000.00, 0.00, '2026-10-19 16:24:45', 1, 'Consultation — Dr. Marco Reyes — source=doctor:5', NULL, NULL, 'PROFESSIONAL_FEE', NULL, NULL, 5, NULL, '2026-10-19 16:24:45'),
(14, 2, 26, 10, 8.00, 80.00, '2026-10-19 18:01:27', 3, 'Fulfillment item #6 (Paracetamol 500mg Tablet) — source=fulfillment:6', NULL, NULL, 'FULFILLMENT', NULL, 6, NULL, NULL, '2026-10-19 18:01:27'),
(15, 2, 36, 10, 45.00, 450.00, '2026-10-19 20:01:12', 3, 'Fulfillment item #7 (Salbutamol Nebule 2.5mg/2.5mL) — source=fulfillment:7', NULL, NULL, 'FULFILLMENT', NULL, 7, NULL, NULL, '2026-10-19 20:01:12'),
(16, 2, 15, 1, 800.00, 800.00, '2026-10-19 20:02:45', 3, 'Fulfillment item #8 (Chest X-Ray (PA View)) — source=fulfillment:8', NULL, NULL, 'FULFILLMENT', NULL, 8, NULL, NULL, '2026-10-19 20:02:45'),
(17, 2, 23, 1, 400.00, 400.00, '2026-10-19 20:16:56', 3, 'Fulfillment item #9 (IV Insertion / Cannulation) — source=fulfillment:9', NULL, NULL, 'FULFILLMENT', NULL, 9, NULL, NULL, '2026-10-19 20:16:56'),
(18, 2, 32, 1, 18.00, 18.00, '2026-10-19 20:23:18', 10, 'Fulfillment item #10 (Omeprazole 20mg Capsule) — source=fulfillment:10', NULL, NULL, 'FULFILLMENT', NULL, 10, NULL, NULL, '2026-10-19 20:23:18'),
(19, 2, 26, 10, 8.00, 80.00, '2026-10-19 20:23:27', 10, 'Fulfillment item #11 (Paracetamol 500mg Tablet) — source=fulfillment:11', NULL, NULL, 'FULFILLMENT', NULL, 11, NULL, NULL, '2026-10-19 20:23:27'),
(20, 1, 28, 5, 12.00, 60.00, '2026-10-19 20:26:38', 10, 'Fulfillment item #12 (Mefenamic Acid 500mg Capsule) — source=fulfillment:12', NULL, NULL, 'FULFILLMENT', NULL, 12, NULL, NULL, '2026-10-19 20:26:38'),
(23, 2, 28, 10, 12.00, 120.00, '2026-10-20 09:03:54', 10, 'Fulfillment item #15 (Mefenamic Acid 500mg Capsule) — source=fulfillment:15', NULL, NULL, 'FULFILLMENT', NULL, 15, NULL, NULL, '2026-10-20 09:03:54'),
(24, 3, 4, 1, 4500.00, 0.00, '2026-10-20 11:45:59', 3, 'Room 401 (Deluxe Suite) — source=room:4', '2026-10-20', '2026-10-20', 'ROOM', 4, NULL, NULL, NULL, '2026-10-20 11:45:59'),
(25, 3, 45, 1, 800.00, 0.00, '2026-10-20 11:45:59', 3, 'Consultation — Dr. Rafael Santos — source=doctor:7', NULL, NULL, 'PROFESSIONAL_FEE', NULL, NULL, 7, NULL, '2026-10-20 11:45:59'),
(26, 3, 45, 1, 1000.00, 0.00, '2026-10-20 11:45:59', 3, 'Consultation — Dr. Marco Reyes — source=doctor:6', NULL, NULL, 'PROFESSIONAL_FEE', NULL, NULL, 6, NULL, '2026-10-20 11:45:59'),
(27, 4, 2, 1, 1500.00, 0.00, '2026-10-20 11:52:20', 3, 'Room 205 (Semi-Private) — source=room:5', '2026-10-20', '2026-10-20', 'ROOM', 5, NULL, NULL, NULL, '2026-10-20 11:52:20'),
(28, 4, 45, 1, 800.00, 0.00, '2026-10-20 11:52:20', 3, 'Consultation — Dr. Rafael Santos — source=doctor:8', NULL, NULL, 'PROFESSIONAL_FEE', NULL, NULL, 8, NULL, '2026-10-20 11:52:20'),
(29, 4, 26, 15, 8.00, 120.00, '2026-10-20 12:09:38', 10, 'Fulfillment item #16 (Paracetamol 500mg Tablet) — source=fulfillment:16', NULL, NULL, 'FULFILLMENT', NULL, 16, NULL, NULL, '2026-10-20 12:09:38'),
(30, 3, 36, 10, 45.00, 450.00, '2026-10-20 12:10:10', 10, 'Fulfillment item #17 (Salbutamol Nebule 2.5mg/2.5mL) — source=fulfillment:17', NULL, NULL, 'FULFILLMENT', NULL, 17, NULL, NULL, '2026-10-20 12:10:10'),
(31, 5, 1, 1, 800.00, 0.00, '2026-10-20 12:23:31', 3, 'Room 201 (Ward) — source=room:6', '2026-10-20', '2026-10-20', 'ROOM', 6, NULL, NULL, NULL, '2026-10-20 12:23:31'),
(32, 5, 45, 1, 800.00, 0.00, '2026-10-20 12:23:31', 3, 'Consultation — Dr. Rafael Santos — source=doctor:9', NULL, NULL, 'PROFESSIONAL_FEE', NULL, NULL, 9, NULL, '2026-10-20 12:23:31'),
(33, 5, 26, 20, 8.00, 160.00, '2026-10-20 12:25:08', 10, 'Fulfillment item #18 (Paracetamol 500mg Tablet) — source=fulfillment:18', NULL, NULL, 'FULFILLMENT', NULL, 18, NULL, NULL, '2026-10-20 12:25:08'),
(34, 5, 26, -15, 8.00, -120.00, '2026-10-20 12:32:02', 10, 'medicine-return-origin=18; Medicine return fulfillment item #19', NULL, NULL, 'MEDICINE_RETURN', NULL, 19, NULL, NULL, '2026-10-20 12:32:02'),
(35, 1, 28, 5, 12.00, 60.00, '2026-10-20 12:33:44', 10, 'Fulfillment item #20 (Mefenamic Acid 500mg Capsule) — source=fulfillment:20', NULL, NULL, 'FULFILLMENT', NULL, 20, NULL, NULL, '2026-10-20 12:33:44'),
(36, 6, 1, 1, 800.00, 0.00, '2026-10-20 12:36:10', 3, 'Room 201 (Ward) — source=room:7', '2026-10-20', '2026-10-20', 'ROOM', 7, NULL, NULL, NULL, '2026-10-20 12:36:10'),
(37, 6, 45, 1, 800.00, 0.00, '2026-10-20 12:36:10', 3, 'Consultation — Dr. Rafael Santos — source=doctor:10', NULL, NULL, 'PROFESSIONAL_FEE', NULL, NULL, 10, NULL, '2026-10-20 12:36:10'),
(38, 6, 26, 10, 8.00, 80.00, '2026-10-20 12:37:23', 10, 'Fulfillment item #21 (Paracetamol 500mg Tablet) — source=fulfillment:21', NULL, NULL, 'FULFILLMENT', NULL, 21, NULL, NULL, '2026-10-20 12:37:23'),
(39, 6, 26, -5, 8.00, -40.00, '2026-10-20 12:38:44', 10, 'medicine-return-origin=21; Medicine return fulfillment item #22', NULL, NULL, 'MEDICINE_RETURN', NULL, 22, NULL, NULL, '2026-10-20 12:38:44'),
(40, 1, 45, 1, 800.00, 0.00, '2026-10-20 12:49:25', 1, 'Consultation — Dr. Rafael Santos — source=doctor:12', NULL, NULL, 'PROFESSIONAL_FEE', NULL, NULL, 12, NULL, '2026-10-20 12:49:25'),
(41, 1, 45, 1, 1000.00, 0.00, '2026-10-20 12:49:25', 1, 'Consultation — Dr. Marco Reyes — source=doctor:11', NULL, NULL, 'PROFESSIONAL_FEE', NULL, NULL, 11, NULL, '2026-10-20 12:49:25'),
(42, 1, 45, 1, 800.00, 0.00, '2026-10-20 13:08:18', 1, 'Consultation — Dr. Rafael Santos — source=doctor:14', NULL, NULL, 'PROFESSIONAL_FEE', NULL, NULL, 14, NULL, '2026-10-20 13:08:18'),
(43, 1, 45, 1, 1000.00, 0.00, '2026-10-20 13:08:18', 1, 'Consultation — Dr. Marco Reyes — source=doctor:13', NULL, NULL, 'PROFESSIONAL_FEE', NULL, NULL, 13, NULL, '2026-10-20 13:08:18'),
(44, 1, 45, 1, 800.00, 0.00, '2026-10-20 13:08:45', 1, 'Consultation — Dr. Rafael Santos — source=doctor:16', NULL, NULL, 'PROFESSIONAL_FEE', NULL, NULL, 16, NULL, '2026-10-20 13:08:45'),
(45, 1, 45, 1, 1000.00, 0.00, '2026-10-20 13:08:45', 1, 'Consultation — Dr. Marco Reyes — source=doctor:15', NULL, NULL, 'PROFESSIONAL_FEE', NULL, NULL, 15, NULL, '2026-10-20 13:08:45'),
(46, 2, 45, 1, 800.00, 0.00, '2026-10-20 13:10:55', 1, 'Consultation — Dr. Rafael Santos — source=doctor:17', NULL, NULL, 'PROFESSIONAL_FEE', NULL, NULL, 17, NULL, '2026-10-20 13:10:55'),
(47, 5, 45, 1, 800.00, 0.00, '2026-10-20 13:11:12', 1, 'Consultation — Dr. Rafael Santos — source=doctor:18', NULL, NULL, 'PROFESSIONAL_FEE', NULL, NULL, 18, NULL, '2026-10-20 13:11:12'),
(48, 1, 28, -2, 12.00, -24.00, '2026-10-20 13:12:59', 10, 'medicine-return-origin=20; Medicine return fulfillment item #23', NULL, NULL, 'MEDICINE_RETURN', NULL, 23, NULL, NULL, '2026-10-20 13:12:59'),
(49, 1, 34, -6, 14.00, -84.00, '2026-10-20 13:13:11', 10, 'medicine-return-origin=2; Medicine return fulfillment item #24', NULL, NULL, 'MEDICINE_RETURN', NULL, 24, NULL, NULL, '2026-10-20 13:13:11'),
(50, 7, 6, 1, 7000.00, 0.00, '2026-10-20 16:36:16', 3, 'Room NICU-1 (NICU) — source=room:8', '2026-10-20', '2026-10-20', 'ROOM', 8, NULL, NULL, NULL, '2026-10-20 16:36:16'),
(51, 7, 45, 1, 800.00, 0.00, '2026-10-20 16:36:16', 3, 'Consultation — Dr. Rafael Santos — source=doctor:19', NULL, NULL, 'PROFESSIONAL_FEE', NULL, NULL, 19, NULL, '2026-10-20 16:36:16'),
(52, 7, 28, 10, 12.00, 120.00, '2026-10-20 16:38:06', 10, 'Fulfillment item #25 (Mefenamic Acid 500mg Capsule) — source=fulfillment:25', NULL, NULL, 'FULFILLMENT', NULL, 25, NULL, NULL, '2026-10-20 16:38:06'),
(53, 7, 28, -4, 12.00, -48.00, '2026-10-20 19:09:07', 10, 'medicine-return-origin=25; Medicine return fulfillment item #26', NULL, NULL, 'MEDICINE_RETURN', NULL, 26, NULL, NULL, '2026-10-20 19:09:07');

-- --------------------------------------------------------

--
-- Table structure for table `charge_category`
--

CREATE TABLE `charge_category` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `calculation_type` varchar(50) DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `charge_category`
--

INSERT INTO `charge_category` (`category_id`, `category_name`, `description`, `calculation_type`, `is_recurring`) VALUES
(1, 'Room & Board', 'Daily room accommodation fees including nursing care', 'Per Item', 1),
(2, 'Ancillary Services', 'Diagnostic, laboratory, imaging, and therapeutic service fees', 'Per Item', 0),
(3, 'Drugs & Medicines', 'Pharmacy items and medicine dispensing', 'Per Item', 0),
(4, 'Professional Fee', 'Physician consultation and service fees', 'Flat', 0);

-- --------------------------------------------------------

--
-- Table structure for table `charge_item`
--

CREATE TABLE `charge_item` (
  `charge_item_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `default_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_taxable` tinyint(1) NOT NULL DEFAULT 0,
  `requires_doctor_order` tinyint(1) NOT NULL DEFAULT 0,
  `unit_of_measure` varchar(50) DEFAULT NULL,
  `is_stockable` tinyint(1) NOT NULL DEFAULT 0,
  `stock_quantity` int(11) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `charge_item`
--

INSERT INTO `charge_item` (`charge_item_id`, `category_id`, `item_code`, `item_name`, `default_price`, `is_active`, `is_taxable`, `requires_doctor_order`, `unit_of_measure`, `is_stockable`, `stock_quantity`) VALUES
(1, 1, 'ROOM-WARD', 'Ward Room per Day', 800.00, 1, 0, 0, 'day', 0, 0),
(2, 1, 'ROOM-SEMI', 'Semi-Private Room per Day', 1500.00, 1, 0, 0, 'day', 0, 0),
(3, 1, 'ROOM-PVT', 'Private Room per Day', 2500.00, 1, 0, 0, 'day', 0, 0),
(4, 1, 'ROOM-DLX', 'Deluxe Suite per Day', 4500.00, 1, 0, 0, 'day', 0, 0),
(5, 1, 'ROOM-ICU', 'Intensive Care Unit (ICU) per Day', 6500.00, 1, 0, 0, 'day', 0, 0),
(6, 1, 'ROOM-NICU', 'Neonatal ICU (NICU) per Day', 7000.00, 1, 0, 0, 'day', 0, 0),
(7, 2, 'LAB-CBC', 'Complete Blood Count (CBC)', 450.00, 1, 1, 1, 'test', 0, 0),
(8, 2, 'LAB-URINALYSIS', 'Urinalysis', 250.00, 1, 1, 1, 'test', 0, 0),
(9, 2, 'LAB-FBS', 'Fasting Blood Sugar (FBS)', 300.00, 1, 1, 1, 'test', 0, 0),
(10, 2, 'LAB-LIPID', 'Lipid Profile', 900.00, 1, 1, 1, 'test', 0, 0),
(11, 2, 'LAB-CREA', 'Serum Creatinine', 350.00, 1, 1, 1, 'test', 0, 0),
(12, 2, 'LAB-SGPT', 'SGPT / ALT', 400.00, 1, 1, 1, 'test', 0, 0),
(13, 2, 'LAB-HBA1C', 'HbA1c (Glycated Hemoglobin)', 1200.00, 1, 1, 1, 'test', 0, 0),
(14, 2, 'LAB-DENGUE', 'Dengue NS1 Antigen', 1500.00, 1, 1, 1, 'test', 0, 0),
(15, 2, 'RAD-CHEST', 'Chest X-Ray (PA View)', 800.00, 1, 1, 1, 'procedure', 0, 0),
(16, 2, 'RAD-ABDOMEN', 'Abdominal X-Ray', 900.00, 1, 1, 1, 'procedure', 0, 0),
(17, 2, 'RAD-UTZ-ABD', 'Abdominal Ultrasound', 1500.00, 1, 1, 1, 'procedure', 0, 0),
(18, 2, 'RAD-ECG', '12-Lead Electrocardiogram (ECG)', 700.00, 1, 1, 1, 'procedure', 0, 0),
(19, 2, 'RAD-CT-HEAD', 'CT Scan - Head (Plain)', 6500.00, 1, 1, 1, 'procedure', 0, 0),
(20, 2, 'RAD-MRI-BRAIN', 'MRI - Brain (Plain)', 12000.00, 1, 1, 1, 'procedure', 0, 0),
(21, 2, 'PROC-NEB', 'Nebulization', 350.00, 1, 1, 1, 'session', 0, 0),
(22, 2, 'PROC-WOUND', 'Wound Dressing (Simple)', 300.00, 1, 1, 1, 'procedure', 0, 0),
(23, 2, 'PROC-IVINS', 'IV Insertion / Cannulation', 400.00, 1, 0, 1, 'procedure', 0, 0),
(24, 2, 'PROC-CATH', 'Urinary Catheter Insertion', 800.00, 1, 0, 1, 'procedure', 0, 0),
(25, 2, 'PROC-ENEMA', 'Enema Administration', 350.00, 1, 1, 1, 'procedure', 0, 0),
(26, 3, 'MED-PARA500', 'Paracetamol 500mg Tablet', 8.00, 1, 1, 0, 'tablet', 1, 945),
(27, 3, 'MED-PARA250SYP', 'Paracetamol 250mg/5mL Syrup', 120.00, 1, 1, 0, 'bottle', 1, 1000),
(28, 3, 'MED-MEFEN500', 'Mefenamic Acid 500mg Capsule', 12.00, 1, 1, 0, 'capsule', 1, 11),
(29, 3, 'MED-AMOX500', 'Amoxicillin 500mg Capsule', 15.00, 1, 1, 0, 'capsule', 1, 0),
(30, 3, 'MED-CEFU500', 'Cefuroxime 500mg Tablet', 45.00, 1, 1, 0, 'tablet', 1, 30),
(31, 3, 'MED-AZI500', 'Azithromycin 500mg Tablet', 85.00, 1, 1, 0, 'tablet', 1, 30),
(32, 3, 'MED-OMEP20', 'Omeprazole 20mg Capsule', 18.00, 1, 1, 0, 'capsule', 1, 0),
(33, 3, 'MED-AMLOD5', 'Amlodipine 5mg Tablet', 10.00, 1, 1, 0, 'tablet', 1, 31),
(34, 3, 'MED-LOSAR50', 'Losartan 50mg Tablet', 14.00, 1, 1, 0, 'tablet', 1, 6),
(35, 3, 'MED-METFOR500', 'Metformin 500mg Tablet', 9.00, 1, 1, 0, 'tablet', 1, 0),
(36, 3, 'MED-SALB-NEB', 'Salbutamol Nebule 2.5mg/2.5mL', 45.00, 1, 1, 0, 'ampoule', 1, 190),
(37, 3, 'MED-BUDES-NEB', 'Budesonide Nebule 0.5mg/2mL', 90.00, 1, 1, 0, 'ampoule', 1, 30),
(38, 3, 'MED-IV-PNSS', 'PNSS 1L (Plain Normal Saline Solution)', 120.00, 1, 1, 0, 'bottle', 1, 0),
(39, 3, 'MED-IV-D5LRS', 'D5LRS 1L (Dextrose 5% in Lactated Ringer\'s)', 150.00, 1, 1, 0, 'bottle', 1, 30),
(40, 3, 'MED-IV-D5W', 'D5W 1L (Dextrose 5% in Water)', 130.00, 1, 1, 0, 'bottle', 1, 0),
(41, 3, 'MED-TRAM50', 'Tramadol 50mg Capsule', 25.00, 1, 1, 0, 'capsule', 1, 0),
(42, 3, 'MED-KETO30', 'Ketorolac 30mg/mL Injection', 180.00, 1, 1, 0, 'ampoule', 1, 0),
(43, 3, 'MED-DEXA4', 'Dexamethasone 4mg/mL Injection', 60.00, 1, 1, 0, 'ampoule', 1, 0),
(44, 3, 'MED-CEFTRIAX', 'Ceftriaxone 1g Vial', 350.00, 1, 1, 0, 'vial', 1, 30),
(45, 4, 'PROF-CONSULT', 'Doctor Consultation Fee', 800.00, 1, 0, 0, 'visit', 0, 0),
(46, 4, 'PROF-VISIT', 'Ward Visit Fee', 600.00, 1, 0, 0, 'visit', 0, 0),
(47, 4, 'PROF-PROC', 'Procedure / Minor Surgery Fee', 3500.00, 1, 0, 0, 'procedure', 0, 0),
(48, 4, 'PROF-READ', 'Diagnostic Result Reading Fee', 300.00, 1, 0, 0, 'test', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `consultation`
--

CREATE TABLE `consultation` (
  `consultation_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `consultation_datetime` datetime NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `department_code` varchar(50) DEFAULT NULL,
  `charge_category_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`department_id`, `department_name`, `department_code`, `charge_category_id`, `description`, `is_active`) VALUES
(1, 'Administration', 'ADMIN', NULL, 'Hospital administration and executive offices', 1),
(2, 'Nursing Station', 'NURSING', NULL, 'Ward nursing and bed management', 1),
(3, 'Pharmacy', 'PHARM', 3, 'Dispensing of medications and medical supplies', 1),
(4, 'Radiology', 'RAD', 2, 'Diagnostic imaging services', 1),
(5, 'Laboratory', 'LAB', 2, 'Clinical laboratory testing services', 1),
(6, 'Emergency Department', 'ER', 2, 'Emergency room and trauma services', 1),
(7, 'Cashier', 'CASHIER', NULL, 'Billing, invoicing, and payment collection', 1),
(8, 'Medical Records', 'MRD', NULL, 'Patient records and health information management', 1),
(9, 'Central Sterile Supply', 'CSSD', NULL, 'Sterilization and sterile supply services', 1);

-- --------------------------------------------------------

--
-- Table structure for table `diagnosis`
--

CREATE TABLE `diagnosis` (
  `diagnosis_id` int(11) NOT NULL,
  `icd_code` varchar(50) DEFAULT NULL,
  `diagnosis_name` varchar(150) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `diagnosis`
--

INSERT INTO `diagnosis` (`diagnosis_id`, `icd_code`, `diagnosis_name`, `description`, `is_active`) VALUES
(1, 'I10', 'Essential Hypertension', 'Primary high blood pressure without identifiable cause', 1),
(2, 'I11.9', 'Hypertensive Heart Disease', 'Heart disease resulting from high blood pressure', 1),
(3, 'I20.9', 'Angina Pectoris', 'Chest pain due to reduced blood flow to the heart', 1),
(4, 'I21.9', 'Acute Myocardial Infarction', 'Heart attack; death of heart muscle from ischemia', 1),
(5, 'I50.9', 'Congestive Heart Failure', 'Inability of the heart to pump sufficient blood', 1),
(6, 'I63.9', 'Cerebral Infarction (Stroke)', 'Ischemic stroke due to blocked cerebral vessel', 1),
(7, 'J18.9', 'Pneumonia, Unspecified', 'Infection and inflammation of the lung tissue', 1),
(8, 'J45.909', 'Asthma, Unspecified', 'Chronic inflammatory disease of the airways', 1),
(9, 'J44.9', 'Chronic Obstructive Pulmonary Disease', 'Chronic obstruction of airflow from the lungs', 1),
(10, 'J06.9', 'Upper Respiratory Tract Infection', 'Acute infection of the upper airway', 1),
(11, 'A15.0', 'Pulmonary Tuberculosis', 'Tuberculosis infection of the lungs', 1),
(12, 'E11.9', 'Type 2 Diabetes Mellitus', 'Non-insulin-dependent diabetes', 1),
(13, 'E10.9', 'Type 1 Diabetes Mellitus', 'Insulin-dependent diabetes', 1),
(14, 'E78.5', 'Hyperlipidemia, Unspecified', 'Elevated lipids in the blood', 1),
(15, 'E03.9', 'Hypothyroidism, Unspecified', 'Underactive thyroid gland', 1),
(16, 'K29.7', 'Gastritis, Unspecified', 'Inflammation of the stomach lining', 1),
(17, 'K35.80', 'Acute Appendicitis', 'Inflammation of the appendix', 1),
(18, 'K80.20', 'Cholelithiasis', 'Gallstones in the gallbladder', 1),
(19, 'K59.00', 'Constipation', 'Difficulty in regular bowel movement', 1),
(20, 'A09', 'Infectious Gastroenteritis', 'Diarrhea and vomiting due to infection', 1),
(21, 'N39.0', 'Urinary Tract Infection', 'Infection of any part of the urinary system', 1),
(22, 'N18.9', 'Chronic Kidney Disease', 'Progressive loss of kidney function', 1),
(23, 'N20.0', 'Kidney Stone (Nephrolithiasis)', 'Calculus in the kidney', 1),
(24, 'N10', 'Acute Pyelonephritis', 'Infection of the kidney pelvis', 1),
(25, 'M54.5', 'Low Back Pain', 'Pain in the lower back region', 1),
(26, 'M17.9', 'Osteoarthritis of the Knee', 'Degenerative joint disease of the knee', 1),
(27, 'S72.0', 'Fracture of Femur', 'Closed fracture of the neck of femur', 1),
(28, 'S52.5', 'Fracture of Lower End of Radius', 'Distal radius fracture', 1),
(29, 'G43.909', 'Migraine, Unspecified', 'Severe recurring headache with autonomic symptoms', 1),
(30, 'G40.9', 'Epilepsy, Unspecified', 'Recurrent unprovoked seizures', 1),
(31, 'G20', 'Parkinson\'s Disease', 'Progressive neurodegenerative disorder', 1),
(32, 'L50.9', 'Urticaria, Unspecified', 'Hives; raised itchy skin rash', 1),
(33, 'L03.90', 'Cellulitis', 'Bacterial infection of the deep skin layers', 1),
(34, 'L20.9', 'Atopic Dermatitis', 'Chronic inflammatory skin condition (eczema)', 1),
(35, 'A90', 'Dengue Fever', 'Mosquito-borne viral infection', 1),
(36, 'A91', 'Dengue Hemorrhagic Fever', 'Severe form of dengue with bleeding', 1),
(37, 'B19.9', 'Viral Hepatitis, Unspecified', 'Inflammation of the liver due to virus', 1),
(38, 'B34.9', 'Viral Infection, Unspecified', 'Unspecified viral illness', 1),
(39, 'A01.0', 'Typhoid Fever', 'Systemic infection caused by Salmonella typhi', 1),
(40, 'O80', 'Normal Spontaneous Delivery', 'Uncomplicated vaginal delivery', 1),
(41, 'O14.9', 'Pre-eclampsia, Unspecified', 'Pregnancy-induced hypertension', 1),
(42, 'N80.9', 'Endometriosis, Unspecified', 'Endometrial tissue outside the uterus', 1),
(43, 'F32.9', 'Major Depressive Disorder', 'Persistent low mood and loss of interest', 1),
(44, 'F41.9', 'Anxiety Disorder, Unspecified', 'Excessive worry and anxiety', 1),
(45, 'J21.0', 'Acute Bronchiolitis', 'Lower respiratory infection common in infants', 1),
(46, 'A37.0', 'Whooping Cough (Pertussis)', 'Highly contagious respiratory infection', 1),
(47, 'P07.30', 'Preterm Birth', 'Infant born before 37 weeks of gestation', 1),
(48, 'R50.9', 'Fever, Unspecified', 'Elevated body temperature without identified cause', 1),
(49, 'R10.4', 'Abdominal Pain, Unspecified', 'Pain in the abdominal region', 1),
(50, 'D64.9', 'Anemia, Unspecified', 'Reduced hemoglobin or red blood cell count', 1);

-- --------------------------------------------------------

--
-- Table structure for table `doctor`
--

CREATE TABLE `doctor` (
  `doctor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `license_number` varchar(100) NOT NULL,
  `consultation_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor`
--

INSERT INTO `doctor` (`doctor_id`, `user_id`, `license_number`, `consultation_fee`, `is_active`) VALUES
(1, 2, 'PRC-100234', 800.00, 1),
(2, 9, 'PRC-100567', 1000.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `doctor_specialization`
--

CREATE TABLE `doctor_specialization` (
  `doctor_specialization_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `specialization_id` int(11) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_specialization`
--

INSERT INTO `doctor_specialization` (`doctor_specialization_id`, `doctor_id`, `specialization_id`, `is_primary`) VALUES
(1, 1, 1, 1),
(2, 1, 4, 0),
(3, 2, 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `floor_level`
--

CREATE TABLE `floor_level` (
  `floor_level_id` int(11) NOT NULL,
  `building_id` int(11) NOT NULL,
  `floor_level_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `floor_level`
--

INSERT INTO `floor_level` (`floor_level_id`, `building_id`, `floor_level_name`) VALUES
(1, 1, 'Ground Floor'),
(2, 1, '2nd Floor'),
(3, 1, '3rd Floor'),
(4, 1, '4th Floor'),
(5, 1, '5th Floor'),
(6, 2, 'Ground Floor'),
(7, 2, '2nd Floor');

-- --------------------------------------------------------

--
-- Table structure for table `gender`
--

CREATE TABLE `gender` (
  `gender_id` int(11) NOT NULL,
  `gender_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gender`
--

INSERT INTO `gender` (`gender_id`, `gender_name`) VALUES
(1, 'Male'),
(2, 'Female'),
(3, 'Other');

-- --------------------------------------------------------

--
-- Table structure for table `patient`
--

CREATE TABLE `patient` (
  `patient_id` int(11) NOT NULL,
  `gender_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `contact_number` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `emergency_contact` varchar(150) DEFAULT NULL,
  `emergency_contact_number` varchar(30) DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient`
--

INSERT INTO `patient` (`patient_id`, `gender_id`, `first_name`, `last_name`, `birth_date`, `contact_number`, `address`, `email`, `emergency_contact`, `emergency_contact_number`, `medical_history`, `created_at`, `is_active`) VALUES
(1, 1, 'Jerome', 'Pacaldo', '2000-06-13', '09127482312', 'Xavier Heights, Uptown, CdeO', 'pacaldo@gmail.com', '09336416221', NULL, NULL, '2026-10-06 21:46:05', 1),
(2, 1, 'Vladimer', 'Tuyor', '2006-10-30', '0973628472', 'Cdeo, Mis Or', 'tuyor@gmail.com', '0947371733', NULL, NULL, '2026-10-16 15:37:36', 1),
(3, 1, 'Steven', 'Tuyor', '2005-06-13', '097617264123', 'Bayabas, Cagayan de Oro City, Misamis Oriental', 'steven@gmail.com', '09174132134', NULL, NULL, '2026-10-20 11:45:26', 1),
(4, 1, 'Caryl', 'Pacaldo', '2005-02-08', '097617264123', 'Zayas, Cagayan de Oro City, Misamis Oriental', 'Caryl@gmail.com', '09174132134', NULL, NULL, '2026-10-20 11:52:10', 1),
(5, 3, 'Nino', 'Siarot', '2005-10-17', '09861274123', 'Sto. Niño Street', 'siarot@gmail.com', '09174132134', NULL, NULL, '2026-10-20 16:35:16', 1);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `statement_id` int(11) NOT NULL,
  `payment_type_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_datetime` datetime NOT NULL,
  `transaction_reference` varchar(100) DEFAULT NULL,
  `received_by_user_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `statement_id`, `payment_type_id`, `amount`, `payment_datetime`, `transaction_reference`, `received_by_user_id`, `notes`) VALUES
(1, 1, 1, 25000.80, '2026-10-16 15:49:21', 'PAY-20261016-0001', 4, NULL),
(2, 6, 1, 1644.80, '2026-10-20 13:14:41', 'PAY-20261020-0001', 4, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payment_type`
--

CREATE TABLE `payment_type` (
  `payment_type_id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_type`
--

INSERT INTO `payment_type` (`payment_type_id`, `type_name`, `description`) VALUES
(1, 'Cash', 'Paid in physical currency'),
(2, 'Credit Card', 'Paid via credit or debit card terminal'),
(3, 'Debit Card', 'Paid via debit card terminal'),
(4, 'Bank Transfer', 'Paid via direct bank transfer or online banking'),
(5, 'Insurance', 'Covered by an insurance provider'),
(6, 'HMO', 'Covered by a Health Maintenance Organization'),
(7, 'PhilHealth', 'Covered by PhilHealth benefits'),
(8, 'Check', 'Paid via bank check');

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`role_id`, `role_name`, `description`) VALUES
(1, 'admin', 'Full system access and configuration'),
(2, 'doctor', 'Attending, consulting, or referring physician'),
(3, 'nurse', 'Ward and bedside care staff'),
(4, 'cashier', 'Handles invoicing, statements, and payments'),
(5, 'department', 'Department staff working service requests and fulfillment');

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

CREATE TABLE `room` (
  `room_id` int(11) NOT NULL,
  `room_type_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL,
  `floor_level_id` int(11) NOT NULL,
  `room_number` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room`
--

INSERT INTO `room` (`room_id`, `room_type_id`, `status_id`, `floor_level_id`, `room_number`, `is_active`) VALUES
(1, 1, 1, 1, '101', 1),
(2, 1, 1, 1, '102', 1),
(3, 1, 1, 1, '103', 1),
(4, 1, 1, 1, '104', 1),
(5, 1, 1, 2, '201', 1),
(6, 1, 1, 2, '202', 1),
(7, 1, 1, 2, '203', 1),
(8, 1, 1, 2, '204', 1),
(9, 2, 1, 2, '205', 1),
(10, 2, 1, 2, '206', 1),
(11, 3, 1, 3, '301', 1),
(12, 3, 1, 3, '302', 1),
(13, 3, 1, 3, '303', 1),
(14, 3, 1, 3, '304', 1),
(15, 3, 1, 3, '305', 1),
(16, 3, 1, 3, '306', 1),
(17, 4, 1, 4, '401', 1),
(18, 4, 1, 4, '402', 1),
(19, 4, 1, 4, '403', 1),
(20, 5, 1, 5, 'ICU-1', 1),
(21, 5, 1, 5, 'ICU-2', 1),
(22, 5, 1, 5, 'ICU-3', 1),
(23, 6, 1, 5, 'NICU-1', 1),
(24, 6, 1, 5, 'NICU-2', 1),
(25, 3, 1, 6, 'MAB-101', 1),
(26, 3, 1, 6, 'MAB-102', 1),
(27, 3, 1, 7, 'MAB-201', 1);

-- --------------------------------------------------------

--
-- Table structure for table `room_assignment`
--

CREATE TABLE `room_assignment` (
  `room_assignment_id` int(11) NOT NULL,
  `admission_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime DEFAULT NULL,
  `daily_rate_at_assignment` decimal(10,2) NOT NULL,
  `transfer_reason` varchar(255) DEFAULT NULL,
  `transferred_by_user_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_assignment`
--

INSERT INTO `room_assignment` (`room_assignment_id`, `admission_id`, `room_id`, `start_datetime`, `end_datetime`, `daily_rate_at_assignment`, `transfer_reason`, `transferred_by_user_id`, `is_active`) VALUES
(1, 1, 17, '2026-10-06 21:46:30', '2026-10-16 13:43:44', 4500.00, 'Initial placement; preferred room type: Deluxe Suite.', 3, 0),
(2, 1, 14, '2026-10-16 13:43:44', '2026-10-20 13:19:02', 2500.00, NULL, 3, 0),
(3, 2, 5, '2026-10-16 15:38:04', '2026-10-20 11:13:55', 800.00, 'Initial placement; preferred room type: Ward.', 3, 0),
(4, 3, 17, '2026-10-20 11:45:59', '2026-10-20 12:13:19', 4500.00, 'Initial placement; preferred room type: Deluxe Suite.', 3, 0),
(5, 4, 9, '2026-10-20 11:52:20', '2026-10-20 12:21:09', 1500.00, 'Initial placement; preferred room type: Semi-Private.', 3, 0),
(6, 5, 5, '2026-10-20 12:23:31', '2026-10-20 12:35:52', 800.00, 'Initial placement; preferred room type: Ward.', 3, 0),
(7, 6, 5, '2026-10-20 12:36:10', '2026-10-20 12:49:49', 800.00, 'Initial placement; preferred room type: Ward.', 3, 0),
(8, 7, 23, '2026-10-20 16:36:16', '2026-10-20 19:09:43', 7000.00, 'Initial placement; preferred room type: NICU.', 3, 0);

-- --------------------------------------------------------

--
-- Table structure for table `room_status`
--

CREATE TABLE `room_status` (
  `status_id` int(11) NOT NULL,
  `status_name` varchar(50) NOT NULL,
  `color_code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_status`
--

INSERT INTO `room_status` (`status_id`, `status_name`, `color_code`) VALUES
(1, 'Available', '#36bc34'),
(2, 'Occupied', '#dc3545'),
(3, 'Maintenance', '#ffc107'),
(4, 'Reserved', '#17a2b8');

-- --------------------------------------------------------

--
-- Table structure for table `room_type`
--

CREATE TABLE `room_type` (
  `room_type_id` int(11) NOT NULL,
  `room_type_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `rate_per_day` decimal(10,2) NOT NULL DEFAULT 0.00,
  `capacity` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_type`
--

INSERT INTO `room_type` (`room_type_id`, `room_type_name`, `description`, `rate_per_day`, `capacity`, `created_at`) VALUES
(1, 'Ward', 'Shared ward accommodation with 4-6 beds per room', 800.00, 6, '2026-09-14 10:02:54'),
(2, 'Semi-Private', 'Two-bed room with shared bathroom', 1500.00, 2, '2026-09-14 10:02:54'),
(3, 'Private', 'Single-occupancy room with private bathroom', 2500.00, 1, '2026-09-14 10:02:54'),
(4, 'Deluxe Suite', 'Premium single-occupancy room with living area', 4500.00, 1, '2026-09-14 10:02:54'),
(5, 'ICU', 'Intensive Care Unit bed with monitoring equipment', 6500.00, 1, '2026-09-14 10:02:54'),
(6, 'NICU', 'Neonatal Intensive Care Unit bed with incubator', 7000.00, 1, '2026-09-14 10:02:54');

-- --------------------------------------------------------

--
-- Table structure for table `service_fulfillment`
--

CREATE TABLE `service_fulfillment` (
  `fulfillment_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `fulfilled_by_user_id` int(11) DEFAULT NULL,
  `fulfillment_datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) NOT NULL DEFAULT 'Prepared',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_fulfillment`
--

INSERT INTO `service_fulfillment` (`fulfillment_id`, `request_id`, `department_id`, `fulfilled_by_user_id`, `fulfillment_datetime`, `status`, `notes`) VALUES
(1, 1, 6, 13, '2026-10-06 21:50:14', 'Completed', NULL),
(4, 3, 3, 10, '2026-10-16 15:14:49', 'Completed', NULL),
(5, 4, 3, 10, '2026-10-16 15:43:05', 'Completed', NULL),
(6, 5, 3, 10, '2026-10-19 18:00:57', 'Completed', NULL),
(7, 7, 3, 10, '2026-10-19 20:00:53', 'Completed', NULL),
(8, 6, 5, 11, '2026-10-19 20:02:39', 'Completed', NULL),
(9, 8, 5, 11, '2026-10-19 20:16:37', 'Completed', NULL),
(10, 10, 3, 10, '2026-10-19 20:23:18', 'Completed', NULL),
(11, 9, 3, 10, '2026-10-19 20:23:27', 'Completed', NULL),
(12, 2, 3, 10, '2026-10-19 20:26:38', 'Completed', NULL),
(21, 13, 3, 10, '2026-10-20 09:03:54', 'Completed', NULL),
(61, 17, 3, 10, '2026-10-20 12:09:38', 'Completed', NULL),
(63, 18, 3, 10, '2026-10-20 12:10:10', 'Completed', NULL),
(64, 19, 3, 10, '2026-10-20 12:25:08', 'Completed', NULL),
(65, 20, 3, 10, '2026-10-20 12:32:02', 'Completed', NULL),
(66, 2, 3, 10, '2026-10-20 12:33:44', 'Completed', NULL),
(67, 21, 3, 10, '2026-10-20 12:37:23', 'Completed', NULL),
(68, 22, 3, 10, '2026-10-20 12:38:44', 'Completed', NULL),
(69, 23, 3, 10, '2026-10-20 13:12:59', 'Completed', NULL),
(70, 24, 3, 10, '2026-10-20 13:13:11', 'Completed', NULL),
(71, 25, 3, 10, '2026-10-20 16:38:06', 'Completed', NULL),
(72, 26, 3, 10, '2026-10-20 19:09:07', 'Completed', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `service_fulfillment_intake_log`
--

CREATE TABLE `service_fulfillment_intake_log` (
  `intake_log_id` int(11) NOT NULL,
  `fulfillment_item_id` int(11) NOT NULL,
  `event_type` enum('Starting Balance','Received','Administered','Reconciled') NOT NULL,
  `quantity` int(11) UNSIGNED NOT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by_user_id` int(11) DEFAULT NULL,
  `recorded_datetime` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_fulfillment_intake_log`
--

INSERT INTO `service_fulfillment_intake_log` (`intake_log_id`, `fulfillment_item_id`, `event_type`, `quantity`, `notes`, `recorded_by_user_id`, `recorded_datetime`) VALUES
(1, 3, 'Reconciled', 5, NULL, 3, '2026-10-20 09:28:00'),
(2, 3, 'Administered', 5, NULL, 3, '2026-10-20 09:28:15'),
(3, 6, 'Reconciled', 5, NULL, 3, '2026-10-20 09:29:02'),
(8, 11, 'Reconciled', 10, NULL, 3, '2026-10-20 09:29:40'),
(9, 5, 'Reconciled', 5, NULL, 3, '2026-10-20 09:29:54'),
(10, 6, 'Administered', 5, NULL, 3, '2026-10-20 09:30:16'),
(11, 15, 'Reconciled', 6, NULL, 3, '2026-10-20 09:41:38'),
(12, 10, 'Reconciled', 1, NULL, 3, '2026-10-20 10:27:38'),
(13, 4, 'Reconciled', 10, NULL, 3, '2026-10-20 10:32:03'),
(14, 7, 'Reconciled', 8, NULL, 3, '2026-10-20 10:37:59'),
(15, 5, 'Administered', 5, NULL, 3, '2026-10-20 10:38:33'),
(16, 11, 'Administered', 1, NULL, 3, '2026-10-20 10:40:59'),
(17, 10, 'Administered', 1, NULL, 3, '2026-10-20 10:41:21'),
(18, 15, 'Administered', 1, NULL, 3, '2026-10-20 10:41:27'),
(19, 4, 'Administered', 4, NULL, 3, '2026-10-20 10:44:30'),
(20, 15, 'Administered', 3, NULL, 3, '2026-10-20 10:45:11'),
(21, 11, 'Administered', 3, NULL, 3, '2026-10-20 10:56:13'),
(22, 16, 'Starting Balance', 15, NULL, 3, '2026-10-20 12:10:42'),
(23, 17, 'Starting Balance', 10, NULL, 3, '2026-10-20 12:10:43'),
(24, 17, 'Administered', 4, NULL, 3, '2026-10-20 12:10:58'),
(25, 16, 'Administered', 6, NULL, 3, '2026-10-20 12:11:10'),
(26, 18, 'Starting Balance', 20, NULL, 3, '2026-10-20 12:25:38'),
(27, 18, 'Administered', 5, NULL, 3, '2026-10-20 12:28:19'),
(28, 20, 'Starting Balance', 5, NULL, 3, '2026-10-20 12:34:20'),
(29, 21, 'Starting Balance', 10, NULL, 3, '2026-10-20 12:37:50'),
(30, 21, 'Administered', 5, NULL, 3, '2026-10-20 12:37:59'),
(31, 2, 'Reconciled', 10, NULL, 3, '2026-10-20 13:09:24'),
(32, 12, 'Reconciled', 5, NULL, 3, '2026-10-20 13:09:28'),
(33, 2, 'Administered', 4, NULL, 3, '2026-10-20 13:09:52'),
(34, 12, 'Administered', 5, NULL, 3, '2026-10-20 13:09:52'),
(35, 20, 'Administered', 3, NULL, 3, '2026-10-20 13:09:52'),
(36, 25, 'Starting Balance', 10, NULL, 3, '2026-10-20 16:38:48'),
(37, 25, 'Administered', 6, NULL, 3, '2026-10-20 16:39:03');

-- --------------------------------------------------------

--
-- Table structure for table `service_fulfillment_item`
--

CREATE TABLE `service_fulfillment_item` (
  `fulfillment_item_id` int(11) NOT NULL,
  `fulfillment_id` int(11) NOT NULL,
  `request_item_id` int(11) NOT NULL,
  `charge_item_id` int(11) NOT NULL,
  `quantity_fulfilled` int(11) NOT NULL DEFAULT 0,
  `quantity_restocked` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `intake_status` varchar(20) NOT NULL DEFAULT 'Not Started',
  `quantity_remaining` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `fulfilled_datetime` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_fulfillment_item`
--

INSERT INTO `service_fulfillment_item` (`fulfillment_item_id`, `fulfillment_id`, `request_item_id`, `charge_item_id`, `quantity_fulfilled`, `quantity_restocked`, `intake_status`, `quantity_remaining`, `unit_price`, `notes`, `fulfilled_datetime`) VALUES
(1, 1, 1, 16, 1, 0, 'Not Started', 0, 900.00, NULL, '2026-10-06 21:50:14'),
(2, 4, 3, 34, 10, 0, 'In Progress', 0, 14.00, NULL, '2026-10-16 15:14:49'),
(3, 5, 4, 29, 10, 0, 'Completed', 0, 15.00, NULL, '2026-10-16 15:43:05'),
(4, 5, 5, 32, 10, 0, 'In Progress', 6, 18.00, NULL, '2026-10-16 15:43:05'),
(5, 5, 6, 28, 10, 0, 'Completed', 0, 12.00, NULL, '2026-10-16 15:43:05'),
(6, 6, 7, 26, 10, 0, 'Completed', 0, 8.00, NULL, '2026-10-19 18:00:57'),
(7, 7, 9, 36, 10, 0, 'In Progress', 8, 45.00, NULL, '2026-10-19 20:00:53'),
(8, 8, 8, 15, 1, 0, 'Not Started', 0, 800.00, NULL, '2026-10-19 20:02:39'),
(9, 9, 10, 23, 1, 0, 'Not Started', 0, 400.00, NULL, '2026-10-19 20:16:37'),
(10, 10, 12, 32, 1, 0, 'Completed', 0, 18.00, NULL, '2026-10-19 20:23:18'),
(11, 11, 11, 26, 10, 0, 'In Progress', 6, 8.00, NULL, '2026-10-19 20:23:27'),
(12, 12, 2, 28, 5, 0, 'Completed', 0, 12.00, NULL, '2026-10-19 20:26:38'),
(15, 21, 15, 28, 10, 0, 'In Progress', 2, 12.00, NULL, '2026-10-20 09:03:54'),
(16, 61, 19, 26, 15, 0, 'In Progress', 9, 8.00, NULL, '2026-10-20 12:09:38'),
(17, 63, 20, 36, 10, 0, 'In Progress', 6, 45.00, NULL, '2026-10-20 12:10:10'),
(18, 64, 21, 26, 20, 0, 'In Progress', 0, 8.00, NULL, '2026-10-20 12:25:08'),
(19, 65, 22, 26, 15, 15, 'Not Started', 0, 8.00, NULL, '2026-10-20 12:32:02'),
(20, 66, 2, 28, 5, 0, 'In Progress', 0, 12.00, NULL, '2026-10-20 12:33:44'),
(21, 67, 23, 26, 10, 0, 'In Progress', 0, 8.00, NULL, '2026-10-20 12:37:23'),
(22, 68, 24, 26, 5, 5, 'Not Started', 0, 8.00, NULL, '2026-10-20 12:38:44'),
(23, 69, 25, 28, 2, 2, 'Not Started', 0, 12.00, NULL, '2026-10-20 13:12:59'),
(24, 70, 26, 34, 6, 6, 'Not Started', 0, 14.00, NULL, '2026-10-20 13:13:11'),
(25, 71, 27, 28, 10, 0, 'In Progress', 0, 12.00, NULL, '2026-10-20 16:38:06'),
(26, 72, 28, 28, 4, 4, 'Not Started', 0, 12.00, NULL, '2026-10-20 19:09:07');

-- --------------------------------------------------------

--
-- Table structure for table `service_fulfillment_receipt_item`
--

CREATE TABLE `service_fulfillment_receipt_item` (
  `receipt_item_id` int(11) NOT NULL,
  `fulfillment_item_id` int(11) NOT NULL,
  `quantity_received` int(11) NOT NULL,
  `received_by_user_id` int(11) DEFAULT NULL,
  `received_datetime` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_fulfillment_receipt_item`
--

INSERT INTO `service_fulfillment_receipt_item` (`receipt_item_id`, `fulfillment_item_id`, `quantity_received`, `received_by_user_id`, `received_datetime`) VALUES
(1, 1, 1, 3, '2026-10-16 14:50:59'),
(2, 2, 10, 3, '2026-10-16 15:15:06'),
(3, 3, 10, 3, '2026-10-16 15:43:38'),
(4, 4, 10, 3, '2026-10-16 15:43:52'),
(5, 5, 10, 3, '2026-10-16 15:43:59'),
(6, 6, 10, 3, '2026-10-19 18:01:27'),
(7, 7, 10, 3, '2026-10-19 20:01:12'),
(8, 8, 1, 3, '2026-10-19 20:02:45'),
(9, 9, 1, 3, '2026-10-19 20:16:56'),
(10, 10, 1, 3, '2026-10-19 20:27:41'),
(11, 11, 10, 3, '2026-10-19 21:38:52'),
(12, 12, 5, 3, '2026-10-20 08:47:23'),
(13, 15, 10, 3, '2026-10-20 09:05:50'),
(14, 16, 15, 3, '2026-10-20 12:10:42'),
(15, 17, 10, 3, '2026-10-20 12:10:43'),
(16, 18, 20, 3, '2026-10-20 12:25:38'),
(17, 20, 5, 3, '2026-10-20 12:34:20'),
(18, 21, 10, 3, '2026-10-20 12:37:50'),
(19, 25, 10, 3, '2026-10-20 16:38:48');

-- --------------------------------------------------------

--
-- Table structure for table `service_request`
--

CREATE TABLE `service_request` (
  `request_id` int(11) NOT NULL,
  `admission_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `priority` varchar(50) NOT NULL DEFAULT 'Routine',
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `instructions` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `request_datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_datetime` datetime DEFAULT NULL,
  `cancelled_datetime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_request`
--

INSERT INTO `service_request` (`request_id`, `admission_id`, `doctor_id`, `department_id`, `priority`, `status`, `instructions`, `notes`, `request_datetime`, `completed_datetime`, `cancelled_datetime`) VALUES
(1, 1, 1, 6, 'Routine', 'Completed', NULL, NULL, '2026-10-06 21:48:35', '2026-10-06 21:50:14', NULL),
(2, 1, 1, 3, 'Routine', 'Completed', NULL, NULL, '2026-10-06 21:48:58', '2026-10-20 12:33:44', NULL),
(3, 1, 1, 3, 'Routine', 'Completed', NULL, NULL, '2026-10-16 15:14:00', '2026-10-16 15:14:49', NULL),
(4, 2, 1, 3, 'Routine', 'Completed', NULL, NULL, '2026-10-16 15:42:12', '2026-10-16 15:43:05', NULL),
(5, 2, 1, 3, 'Routine', 'Completed', NULL, NULL, '2026-10-19 18:00:14', '2026-10-19 18:00:57', NULL),
(6, 2, 1, 5, 'Routine', 'Completed', NULL, NULL, '2026-10-19 19:01:38', '2026-10-19 20:02:39', NULL),
(7, 2, 1, 3, 'Routine', 'Completed', NULL, NULL, '2026-10-19 19:03:30', '2026-10-19 20:00:53', NULL),
(8, 2, 1, 5, 'Routine', 'Completed', NULL, NULL, '2026-10-19 20:12:53', '2026-10-19 20:16:37', NULL),
(9, 2, 1, 3, 'Routine', 'Completed', NULL, NULL, '2026-10-19 20:13:37', '2026-10-19 20:23:27', NULL),
(10, 2, 1, 3, 'Routine', 'Completed', NULL, NULL, '2026-10-19 20:13:58', '2026-10-19 20:23:18', NULL),
(13, 2, 1, 3, 'Routine', 'Completed', NULL, NULL, '2026-10-19 21:54:23', '2026-10-20 09:03:54', NULL),
(17, 4, 1, 3, 'Routine', 'Completed', NULL, NULL, '2026-10-20 12:02:29', '2026-10-20 12:09:38', NULL),
(18, 3, 1, 3, 'Routine', 'Completed', NULL, NULL, '2026-10-20 12:02:57', '2026-10-20 12:10:10', NULL),
(19, 5, 1, 3, 'Routine', 'Completed', NULL, NULL, '2026-10-20 12:24:13', '2026-10-20 12:25:08', NULL),
(20, 5, 1, 3, 'Routine', 'Completed', 'Medicine return for fulfillment item #18: Unused medicine returned at discharge readiness.', 'MED_RETURN:18|Unused medicine returned at discharge readiness.', '2026-10-20 12:28:32', '2026-10-20 12:32:02', NULL),
(21, 6, 1, 3, 'Routine', 'Completed', NULL, NULL, '2026-10-20 12:36:56', '2026-10-20 12:37:23', NULL),
(22, 6, 1, 3, 'Routine', 'Completed', 'Medicine return for fulfillment item #21: Unused medicine returned at discharge readiness.', 'MED_RETURN:21|Unused medicine returned at discharge readiness.', '2026-10-20 12:38:11', '2026-10-20 12:38:44', NULL),
(23, 1, 1, 3, 'Routine', 'Completed', 'Medicine return for fulfillment item #20: Unused medicine returned at discharge readiness.', 'MED_RETURN:20|Unused medicine returned at discharge readiness.', '2026-10-20 13:12:00', '2026-10-20 13:12:59', NULL),
(24, 1, 1, 3, 'Routine', 'Completed', 'Medicine return for fulfillment item #2: Unused medicine returned at discharge readiness.', 'MED_RETURN:2|Unused medicine returned at discharge readiness.', '2026-10-20 13:12:00', '2026-10-20 13:13:11', NULL),
(25, 7, 1, 3, 'Routine', 'Completed', NULL, NULL, '2026-10-20 16:37:39', '2026-10-20 16:38:06', NULL),
(26, 7, 1, 3, 'Routine', 'Completed', 'Medicine return for fulfillment item #25: Unused medicine returned at discharge readiness.', 'MED_RETURN:25|Unused medicine returned at discharge readiness.', '2026-10-20 17:51:14', '2026-10-20 19:09:07', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `service_request_item`
--

CREATE TABLE `service_request_item` (
  `request_item_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `charge_item_id` int(11) NOT NULL,
  `quantity_requested` int(11) NOT NULL DEFAULT 1,
  `quantity_fulfilled` int(11) NOT NULL DEFAULT 0,
  `instructions` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_request_item`
--

INSERT INTO `service_request_item` (`request_item_id`, `request_id`, `charge_item_id`, `quantity_requested`, `quantity_fulfilled`, `instructions`, `notes`, `created_at`) VALUES
(1, 1, 16, 1, 1, NULL, NULL, '2026-10-06 21:48:35'),
(2, 2, 28, 10, 10, NULL, NULL, '2026-10-06 21:48:58'),
(3, 3, 34, 10, 10, NULL, NULL, '2026-10-16 15:14:00'),
(4, 4, 29, 10, 10, NULL, NULL, '2026-10-16 15:42:12'),
(5, 4, 32, 10, 10, NULL, NULL, '2026-10-16 15:42:12'),
(6, 4, 28, 10, 10, NULL, NULL, '2026-10-16 15:42:12'),
(7, 5, 26, 10, 10, NULL, NULL, '2026-10-19 18:00:14'),
(8, 6, 15, 1, 1, NULL, NULL, '2026-10-19 19:01:38'),
(9, 7, 36, 10, 10, NULL, NULL, '2026-10-19 19:03:30'),
(10, 8, 23, 1, 1, NULL, NULL, '2026-10-19 20:12:53'),
(11, 9, 26, 10, 10, NULL, NULL, '2026-10-19 20:13:37'),
(12, 10, 32, 1, 1, NULL, NULL, '2026-10-19 20:13:58'),
(15, 13, 28, 10, 10, NULL, NULL, '2026-10-19 21:54:23'),
(19, 17, 26, 15, 15, NULL, NULL, '2026-10-20 12:02:29'),
(20, 18, 36, 10, 10, NULL, NULL, '2026-10-20 12:02:57'),
(21, 19, 26, 20, 20, NULL, NULL, '2026-10-20 12:24:13'),
(22, 20, 26, 15, 15, 'Return against original fulfillment item #18', NULL, '2026-10-20 12:28:32'),
(23, 21, 26, 10, 10, NULL, NULL, '2026-10-20 12:36:56'),
(24, 22, 26, 5, 5, 'Return against original fulfillment item #21', NULL, '2026-10-20 12:38:11'),
(25, 23, 28, 2, 2, 'Return against original fulfillment item #20', NULL, '2026-10-20 13:12:00'),
(26, 24, 34, 6, 6, 'Return against original fulfillment item #2', NULL, '2026-10-20 13:12:00'),
(27, 25, 28, 10, 10, NULL, NULL, '2026-10-20 16:37:39'),
(28, 26, 28, 4, 4, 'Return against original fulfillment item #25', NULL, '2026-10-20 17:51:14');

-- --------------------------------------------------------

--
-- Table structure for table `specialization`
--

CREATE TABLE `specialization` (
  `specialization_id` int(11) NOT NULL,
  `specialization_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `specialization`
--

INSERT INTO `specialization` (`specialization_id`, `specialization_name`) VALUES
(1, 'Cardiology'),
(2, 'Pediatrics'),
(3, 'Orthopedics'),
(4, 'General Medicine'),
(5, 'Pulmonology'),
(6, 'Gastroenterology'),
(7, 'Neurology'),
(8, 'Endocrinology'),
(9, 'Nephrology'),
(10, 'Obstetrics and Gynecology'),
(11, 'General Surgery'),
(12, 'Dermatology'),
(13, 'Psychiatry'),
(14, 'Ophthalmology'),
(15, 'Otorhinolaryngology (ENT)'),
(16, 'Urology'),
(17, 'Anesthesiology'),
(18, 'Emergency Medicine'),
(19, 'Family Medicine'),
(20, 'Infectious Disease');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `contact_number` varchar(30) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `role_id`, `department_id`, `username`, `password_hash`, `first_name`, `last_name`, `email`, `contact_number`, `created_at`, `is_active`) VALUES
(1, 1, 1, 'admin.garcia', '$2y$10$qe0m16woi2CfoDCDsisJneXBPUHFfNi4TRTDn.vNH40B2rFf7JqIe', 'Marissa', 'Garcia', 'admin@gmail.com', '0917-000-0001', '2026-09-14 10:02:54', 1),
(2, 2, 6, 'dr.santos', '$2y$10$qe0m16woi2CfoDCDsisJneXBPUHFfNi4TRTDn.vNH40B2rFf7JqIe', 'Rafael', 'Santos', 'doctor@gmail.com', '0917-000-0002', '2026-09-14 10:02:54', 1),
(3, 3, 2, 'nurse.aquino', '$2y$10$qe0m16woi2CfoDCDsisJneXBPUHFfNi4TRTDn.vNH40B2rFf7JqIe', 'Amy', 'Aquino', 'nurse@gmail.com', '0917-000-0005', '2026-09-14 10:02:54', 1),
(4, 4, 7, 'billing.torres', '$2y$10$qe0m16woi2CfoDCDsisJneXBPUHFfNi4TRTDn.vNH40B2rFf7JqIe', 'Bianca', 'Torres', 'cashier@gmail.com', '0917-000-0006', '2026-09-14 10:02:54', 1),
(5, 3, 2, 'nurse.reyes', '$2y$10$qe0m16woi2CfoDCDsisJneXBPUHFfNi4TRTDn.vNH40B2rFf7JqIe', 'Corazon', 'Reyes', 'nurse2@gmail.com', '0917-000-0007', '2026-09-14 10:02:54', 1),
(6, 4, 7, 'cashier.lim', '$2y$10$qe0m16woi2CfoDCDsisJneXBPUHFfNi4TRTDn.vNH40B2rFf7JqIe', 'Dennis', 'Lim', 'cashier2@gmail.com', '0917-000-0008', '2026-09-14 10:02:54', 1),
(9, 2, 6, 'dr.reyes', '$2y$10$3He2C.fBjmDkCw77l.OGpes9C2Gv/NwA2t..xl9d9ieR2aTd7O0WO', 'Marco', 'Reyes', 'reyes.doctor@gmail.com', '0917-000-0009', '2026-09-14 21:13:47', 1),
(10, 5, 3, 'dept.pharmacy', '$2y$10$qe0m16woi2CfoDCDsisJneXBPUHFfNi4TRTDn.vNH40B2rFf7JqIe', 'Maria', 'Dela Cruz', 'pharmacy@gmail.com', '0917-000-0010', '2026-09-14 10:02:54', 1),
(11, 5, 5, 'dept.lab', '$2y$10$qe0m16woi2CfoDCDsisJneXBPUHFfNi4TRTDn.vNH40B2rFf7JqIe', 'Angel', 'Reyes', 'laboratory@gmail.com', '0917-000-0011', '2026-09-14 10:02:54', 1),
(12, 5, 4, 'dept.radiology', '$2y$10$qe0m16woi2CfoDCDsisJneXBPUHFfNi4TRTDn.vNH40B2rFf7JqIe', 'Liza', 'Santos', 'radiology@gmail.com', '0917-000-0012', '2026-09-14 10:02:54', 1),
(13, 5, 6, 'dept.emergency', '$2y$10$qe0m16woi2CfoDCDsisJneXBPUHFfNi4TRTDn.vNH40B2rFf7JqIe', 'Ramon', 'Cruz', 'emergency@gmail.com', '0917-000-0013', '2026-09-14 10:02:54', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admission`
--
ALTER TABLE `admission`
  ADD PRIMARY KEY (`admission_id`),
  ADD KEY `fk_admission_patient` (`patient_id`),
  ADD KEY `fk_admission_status` (`status_id`),
  ADD KEY `fk_admission_admitted_by` (`admitted_by_user_id`),
  ADD KEY `fk_admission_discharged_by` (`discharged_by_user_id`);

--
-- Indexes for table `admission_diagnosis`
--
ALTER TABLE `admission_diagnosis`
  ADD PRIMARY KEY (`admission_diagnosis_id`),
  ADD KEY `fk_admission_diagnosis_admission` (`admission_id`),
  ADD KEY `fk_admission_diagnosis_diagnosis` (`diagnosis_id`),
  ADD KEY `fk_admission_diagnosis_doctor` (`diagnosed_by_doctor_id`);

--
-- Indexes for table `admission_doctor`
--
ALTER TABLE `admission_doctor`
  ADD PRIMARY KEY (`admission_doctor_id`),
  ADD KEY `fk_admission_doctor_admission` (`admission_id`),
  ADD KEY `fk_admission_doctor_doctor` (`doctor_id`);

--
-- Indexes for table `admission_status`
--
ALTER TABLE `admission_status`
  ADD PRIMARY KEY (`status_id`);

--
-- Indexes for table `billing_statement`
--
ALTER TABLE `billing_statement`
  ADD PRIMARY KEY (`statement_id`),
  ADD KEY `fk_billing_statement_admission` (`admission_id`),
  ADD KEY `fk_billing_statement_status` (`status_id`),
  ADD KEY `fk_billing_statement_user` (`created_by_user_id`);

--
-- Indexes for table `billing_status`
--
ALTER TABLE `billing_status`
  ADD PRIMARY KEY (`status_id`);

--
-- Indexes for table `building`
--
ALTER TABLE `building`
  ADD PRIMARY KEY (`building_id`);

--
-- Indexes for table `charge`
--
ALTER TABLE `charge`
  ADD PRIMARY KEY (`charge_id`),
  ADD KEY `fk_charge_statement` (`statement_id`),
  ADD KEY `fk_charge_item` (`charge_item_id`),
  ADD KEY `fk_charge_user` (`processed_by_user_id`);

--
-- Indexes for table `charge_category`
--
ALTER TABLE `charge_category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `charge_item`
--
ALTER TABLE `charge_item`
  ADD PRIMARY KEY (`charge_item_id`),
  ADD UNIQUE KEY `uq_charge_item_code` (`item_code`),
  ADD KEY `fk_charge_item_category` (`category_id`);

--
-- Indexes for table `consultation`
--
ALTER TABLE `consultation`
  ADD PRIMARY KEY (`consultation_id`),
  ADD KEY `fk_consultation_patient` (`patient_id`),
  ADD KEY `fk_consultation_doctor` (`doctor_id`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`department_id`),
  ADD KEY `idx_department_charge_category` (`charge_category_id`);

--
-- Indexes for table `diagnosis`
--
ALTER TABLE `diagnosis`
  ADD PRIMARY KEY (`diagnosis_id`),
  ADD UNIQUE KEY `uq_diagnosis_icd_code` (`icd_code`);

--
-- Indexes for table `doctor`
--
ALTER TABLE `doctor`
  ADD PRIMARY KEY (`doctor_id`),
  ADD UNIQUE KEY `uq_doctor_user` (`user_id`),
  ADD UNIQUE KEY `uq_doctor_license` (`license_number`);

--
-- Indexes for table `doctor_specialization`
--
ALTER TABLE `doctor_specialization`
  ADD PRIMARY KEY (`doctor_specialization_id`),
  ADD UNIQUE KEY `uq_doctor_specialization` (`doctor_id`,`specialization_id`),
  ADD KEY `fk_doctor_specialization_specialization` (`specialization_id`);

--
-- Indexes for table `floor_level`
--
ALTER TABLE `floor_level`
  ADD PRIMARY KEY (`floor_level_id`),
  ADD KEY `fk_floor_building` (`building_id`);

--
-- Indexes for table `gender`
--
ALTER TABLE `gender`
  ADD PRIMARY KEY (`gender_id`);

--
-- Indexes for table `patient`
--
ALTER TABLE `patient`
  ADD PRIMARY KEY (`patient_id`),
  ADD KEY `fk_patient_gender` (`gender_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `fk_payment_statement` (`statement_id`),
  ADD KEY `fk_payment_type` (`payment_type_id`),
  ADD KEY `fk_payment_user` (`received_by_user_id`);

--
-- Indexes for table `payment_type`
--
ALTER TABLE `payment_type`
  ADD PRIMARY KEY (`payment_type_id`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `room`
--
ALTER TABLE `room`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `fk_room_type` (`room_type_id`),
  ADD KEY `fk_room_status` (`status_id`),
  ADD KEY `fk_room_floor` (`floor_level_id`);

--
-- Indexes for table `room_assignment`
--
ALTER TABLE `room_assignment`
  ADD PRIMARY KEY (`room_assignment_id`),
  ADD KEY `fk_room_assignment_admission` (`admission_id`),
  ADD KEY `fk_room_assignment_room` (`room_id`),
  ADD KEY `fk_room_assignment_user` (`transferred_by_user_id`);

--
-- Indexes for table `room_status`
--
ALTER TABLE `room_status`
  ADD PRIMARY KEY (`status_id`);

--
-- Indexes for table `room_type`
--
ALTER TABLE `room_type`
  ADD PRIMARY KEY (`room_type_id`);

--
-- Indexes for table `service_fulfillment`
--
ALTER TABLE `service_fulfillment`
  ADD PRIMARY KEY (`fulfillment_id`),
  ADD KEY `fk_fulfillment_request` (`request_id`),
  ADD KEY `fk_fulfillment_department` (`department_id`),
  ADD KEY `fk_fulfillment_user` (`fulfilled_by_user_id`);

--
-- Indexes for table `service_fulfillment_intake_log`
--
ALTER TABLE `service_fulfillment_intake_log`
  ADD PRIMARY KEY (`intake_log_id`),
  ADD KEY `idx_intake_log_fulfillment_item` (`fulfillment_item_id`),
  ADD KEY `idx_intake_log_recorded_by` (`recorded_by_user_id`);

--
-- Indexes for table `service_fulfillment_item`
--
ALTER TABLE `service_fulfillment_item`
  ADD PRIMARY KEY (`fulfillment_item_id`),
  ADD KEY `fk_fulfillment_item_fulfillment` (`fulfillment_id`),
  ADD KEY `fk_fulfillment_item_request_item` (`request_item_id`),
  ADD KEY `fk_fulfillment_item_charge_item` (`charge_item_id`);

--
-- Indexes for table `service_fulfillment_receipt_item`
--
ALTER TABLE `service_fulfillment_receipt_item`
  ADD PRIMARY KEY (`receipt_item_id`),
  ADD KEY `idx_receipt_fulfillment_item` (`fulfillment_item_id`),
  ADD KEY `idx_receipt_received_by` (`received_by_user_id`);

--
-- Indexes for table `service_request`
--
ALTER TABLE `service_request`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `fk_service_request_admission` (`admission_id`),
  ADD KEY `fk_service_request_doctor` (`doctor_id`),
  ADD KEY `fk_service_request_department` (`department_id`);

--
-- Indexes for table `service_request_item`
--
ALTER TABLE `service_request_item`
  ADD PRIMARY KEY (`request_item_id`),
  ADD KEY `fk_request_item_request` (`request_id`),
  ADD KEY `fk_request_item_charge_item` (`charge_item_id`);

--
-- Indexes for table `specialization`
--
ALTER TABLE `specialization`
  ADD PRIMARY KEY (`specialization_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_user_username` (`username`),
  ADD KEY `fk_user_role` (`role_id`),
  ADD KEY `fk_user_department` (`department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admission`
--
ALTER TABLE `admission`
  MODIFY `admission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `admission_diagnosis`
--
ALTER TABLE `admission_diagnosis`
  MODIFY `admission_diagnosis_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `admission_doctor`
--
ALTER TABLE `admission_doctor`
  MODIFY `admission_doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `admission_status`
--
ALTER TABLE `admission_status`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `billing_statement`
--
ALTER TABLE `billing_statement`
  MODIFY `statement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `billing_status`
--
ALTER TABLE `billing_status`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `building`
--
ALTER TABLE `building`
  MODIFY `building_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `charge`
--
ALTER TABLE `charge`
  MODIFY `charge_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `charge_category`
--
ALTER TABLE `charge_category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `charge_item`
--
ALTER TABLE `charge_item`
  MODIFY `charge_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `consultation`
--
ALTER TABLE `consultation`
  MODIFY `consultation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `diagnosis`
--
ALTER TABLE `diagnosis`
  MODIFY `diagnosis_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `doctor`
--
ALTER TABLE `doctor`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `doctor_specialization`
--
ALTER TABLE `doctor_specialization`
  MODIFY `doctor_specialization_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `floor_level`
--
ALTER TABLE `floor_level`
  MODIFY `floor_level_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `gender`
--
ALTER TABLE `gender`
  MODIFY `gender_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `patient`
--
ALTER TABLE `patient`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payment_type`
--
ALTER TABLE `payment_type`
  MODIFY `payment_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `room`
--
ALTER TABLE `room`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `room_assignment`
--
ALTER TABLE `room_assignment`
  MODIFY `room_assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `room_status`
--
ALTER TABLE `room_status`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `room_type`
--
ALTER TABLE `room_type`
  MODIFY `room_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `service_fulfillment`
--
ALTER TABLE `service_fulfillment`
  MODIFY `fulfillment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `service_fulfillment_intake_log`
--
ALTER TABLE `service_fulfillment_intake_log`
  MODIFY `intake_log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `service_fulfillment_item`
--
ALTER TABLE `service_fulfillment_item`
  MODIFY `fulfillment_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `service_fulfillment_receipt_item`
--
ALTER TABLE `service_fulfillment_receipt_item`
  MODIFY `receipt_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `service_request`
--
ALTER TABLE `service_request`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `service_request_item`
--
ALTER TABLE `service_request_item`
  MODIFY `request_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `specialization`
--
ALTER TABLE `specialization`
  MODIFY `specialization_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admission`
--
ALTER TABLE `admission`
  ADD CONSTRAINT `fk_admission_admitted_by` FOREIGN KEY (`admitted_by_user_id`) REFERENCES `user` (`user_id`),
  ADD CONSTRAINT `fk_admission_discharged_by` FOREIGN KEY (`discharged_by_user_id`) REFERENCES `user` (`user_id`),
  ADD CONSTRAINT `fk_admission_patient` FOREIGN KEY (`patient_id`) REFERENCES `patient` (`patient_id`),
  ADD CONSTRAINT `fk_admission_status` FOREIGN KEY (`status_id`) REFERENCES `admission_status` (`status_id`);

--
-- Constraints for table `admission_diagnosis`
--
ALTER TABLE `admission_diagnosis`
  ADD CONSTRAINT `fk_admission_diagnosis_admission` FOREIGN KEY (`admission_id`) REFERENCES `admission` (`admission_id`),
  ADD CONSTRAINT `fk_admission_diagnosis_diagnosis` FOREIGN KEY (`diagnosis_id`) REFERENCES `diagnosis` (`diagnosis_id`),
  ADD CONSTRAINT `fk_admission_diagnosis_doctor` FOREIGN KEY (`diagnosed_by_doctor_id`) REFERENCES `doctor` (`doctor_id`);

--
-- Constraints for table `admission_doctor`
--
ALTER TABLE `admission_doctor`
  ADD CONSTRAINT `fk_admission_doctor_admission` FOREIGN KEY (`admission_id`) REFERENCES `admission` (`admission_id`),
  ADD CONSTRAINT `fk_admission_doctor_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`doctor_id`);

--
-- Constraints for table `billing_statement`
--
ALTER TABLE `billing_statement`
  ADD CONSTRAINT `fk_billing_statement_admission` FOREIGN KEY (`admission_id`) REFERENCES `admission` (`admission_id`),
  ADD CONSTRAINT `fk_billing_statement_status` FOREIGN KEY (`status_id`) REFERENCES `billing_status` (`status_id`),
  ADD CONSTRAINT `fk_billing_statement_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `charge`
--
ALTER TABLE `charge`
  ADD CONSTRAINT `fk_charge_item` FOREIGN KEY (`charge_item_id`) REFERENCES `charge_item` (`charge_item_id`),
  ADD CONSTRAINT `fk_charge_statement` FOREIGN KEY (`statement_id`) REFERENCES `billing_statement` (`statement_id`),
  ADD CONSTRAINT `fk_charge_user` FOREIGN KEY (`processed_by_user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `charge_item`
--
ALTER TABLE `charge_item`
  ADD CONSTRAINT `fk_charge_item_category` FOREIGN KEY (`category_id`) REFERENCES `charge_category` (`category_id`);

--
-- Constraints for table `consultation`
--
ALTER TABLE `consultation`
  ADD CONSTRAINT `fk_consultation_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`doctor_id`),
  ADD CONSTRAINT `fk_consultation_patient` FOREIGN KEY (`patient_id`) REFERENCES `patient` (`patient_id`);

--
-- Constraints for table `department`
--
ALTER TABLE `department`
  ADD CONSTRAINT `fk_department_charge_category` FOREIGN KEY (`charge_category_id`) REFERENCES `charge_category` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `doctor`
--
ALTER TABLE `doctor`
  ADD CONSTRAINT `fk_doctor_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `doctor_specialization`
--
ALTER TABLE `doctor_specialization`
  ADD CONSTRAINT `fk_doctor_specialization_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`doctor_id`),
  ADD CONSTRAINT `fk_doctor_specialization_specialization` FOREIGN KEY (`specialization_id`) REFERENCES `specialization` (`specialization_id`);

--
-- Constraints for table `floor_level`
--
ALTER TABLE `floor_level`
  ADD CONSTRAINT `fk_floor_building` FOREIGN KEY (`building_id`) REFERENCES `building` (`building_id`);

--
-- Constraints for table `patient`
--
ALTER TABLE `patient`
  ADD CONSTRAINT `fk_patient_gender` FOREIGN KEY (`gender_id`) REFERENCES `gender` (`gender_id`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `fk_payment_statement` FOREIGN KEY (`statement_id`) REFERENCES `billing_statement` (`statement_id`),
  ADD CONSTRAINT `fk_payment_type` FOREIGN KEY (`payment_type_id`) REFERENCES `payment_type` (`payment_type_id`),
  ADD CONSTRAINT `fk_payment_user` FOREIGN KEY (`received_by_user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `room`
--
ALTER TABLE `room`
  ADD CONSTRAINT `fk_room_floor` FOREIGN KEY (`floor_level_id`) REFERENCES `floor_level` (`floor_level_id`),
  ADD CONSTRAINT `fk_room_status` FOREIGN KEY (`status_id`) REFERENCES `room_status` (`status_id`),
  ADD CONSTRAINT `fk_room_type` FOREIGN KEY (`room_type_id`) REFERENCES `room_type` (`room_type_id`);

--
-- Constraints for table `room_assignment`
--
ALTER TABLE `room_assignment`
  ADD CONSTRAINT `fk_room_assignment_admission` FOREIGN KEY (`admission_id`) REFERENCES `admission` (`admission_id`),
  ADD CONSTRAINT `fk_room_assignment_room` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`),
  ADD CONSTRAINT `fk_room_assignment_user` FOREIGN KEY (`transferred_by_user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `service_fulfillment_intake_log`
--
ALTER TABLE `service_fulfillment_intake_log`
  ADD CONSTRAINT `fk_intake_log_fulfillment_item` FOREIGN KEY (`fulfillment_item_id`) REFERENCES `service_fulfillment_item` (`fulfillment_item_id`),
  ADD CONSTRAINT `fk_intake_log_recorded_by` FOREIGN KEY (`recorded_by_user_id`) REFERENCES `user` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `service_fulfillment_receipt_item`
--
ALTER TABLE `service_fulfillment_receipt_item`
  ADD CONSTRAINT `fk_receipt_fulfillment_item` FOREIGN KEY (`fulfillment_item_id`) REFERENCES `service_fulfillment_item` (`fulfillment_item_id`),
  ADD CONSTRAINT `fk_receipt_received_by` FOREIGN KEY (`received_by_user_id`) REFERENCES `user` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
