-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 06, 2026 at 04:07 PM
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
(1, 1, 1, '2026-10-06 21:46:30', NULL, 'Gi kulata', 'Emergency', 0, 3, NULL, 'Preferred room type: Deluxe Suite. [preferred_room_type_id=4]');

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
(1, 1, 27, 'Primary', '2026-10-06 21:47:31', 1);

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
(1, 1, 2, 'Consulting', '2026-10-06 21:46:30', NULL, 1000.00),
(2, 1, 1, 'Attending', '2026-10-06 21:46:30', NULL, 800.00);

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
(1, 1, 1, '2026-10-06 21:46:30', NULL, 6300.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'In Patient', 'In Patient', 'Non-Member', NULL, NULL, 26, 'Male', 'Xavier Heights, Uptown, CdeO', '401', 'Deluxe Suite', 4500.00, 'Dr. Rafael Santos, Dr. Marco Reyes', 0.00, 6300.00, 0.00, 6300.00, '2026-10-06 21:46:30', 3, NULL);

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
(1, 1, 4, 1, 4500.00, 0.00, '2026-10-06 21:46:30', 3, 'Room 401 (Deluxe Suite) — source=room:1', '2026-10-06', NULL, 'ROOM', 1, NULL, NULL, NULL, '2026-10-06 21:46:30'),
(2, 1, 45, 1, 1000.00, 0.00, '2026-10-06 21:46:30', 3, 'Consultation — Dr. Marco Reyes — source=doctor:1', NULL, NULL, 'PROFESSIONAL_FEE', NULL, NULL, 1, NULL, '2026-10-06 21:46:30'),
(3, 1, 45, 1, 800.00, 0.00, '2026-10-06 21:46:30', 3, 'Consultation — Dr. Rafael Santos — source=doctor:2', NULL, NULL, 'PROFESSIONAL_FEE', NULL, NULL, 2, NULL, '2026-10-06 21:46:30');

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
  `unit_of_measure` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `charge_item`
--

INSERT INTO `charge_item` (`charge_item_id`, `category_id`, `item_code`, `item_name`, `default_price`, `is_active`, `is_taxable`, `requires_doctor_order`, `unit_of_measure`) VALUES
(1, 1, 'ROOM-WARD', 'Ward Room per Day', 800.00, 1, 0, 0, 'day'),
(2, 1, 'ROOM-SEMI', 'Semi-Private Room per Day', 1500.00, 1, 0, 0, 'day'),
(3, 1, 'ROOM-PVT', 'Private Room per Day', 2500.00, 1, 0, 0, 'day'),
(4, 1, 'ROOM-DLX', 'Deluxe Suite per Day', 4500.00, 1, 0, 0, 'day'),
(5, 1, 'ROOM-ICU', 'Intensive Care Unit (ICU) per Day', 6500.00, 1, 0, 0, 'day'),
(6, 1, 'ROOM-NICU', 'Neonatal ICU (NICU) per Day', 7000.00, 1, 0, 0, 'day'),
(7, 2, 'LAB-CBC', 'Complete Blood Count (CBC)', 450.00, 1, 1, 1, 'test'),
(8, 2, 'LAB-URINALYSIS', 'Urinalysis', 250.00, 1, 1, 1, 'test'),
(9, 2, 'LAB-FBS', 'Fasting Blood Sugar (FBS)', 300.00, 1, 1, 1, 'test'),
(10, 2, 'LAB-LIPID', 'Lipid Profile', 900.00, 1, 1, 1, 'test'),
(11, 2, 'LAB-CREA', 'Serum Creatinine', 350.00, 1, 1, 1, 'test'),
(12, 2, 'LAB-SGPT', 'SGPT / ALT', 400.00, 1, 1, 1, 'test'),
(13, 2, 'LAB-HBA1C', 'HbA1c (Glycated Hemoglobin)', 1200.00, 1, 1, 1, 'test'),
(14, 2, 'LAB-DENGUE', 'Dengue NS1 Antigen', 1500.00, 1, 1, 1, 'test'),
(15, 2, 'RAD-CHEST', 'Chest X-Ray (PA View)', 800.00, 1, 1, 1, 'procedure'),
(16, 2, 'RAD-ABDOMEN', 'Abdominal X-Ray', 900.00, 1, 1, 1, 'procedure'),
(17, 2, 'RAD-UTZ-ABD', 'Abdominal Ultrasound', 1500.00, 1, 1, 1, 'procedure'),
(18, 2, 'RAD-ECG', '12-Lead Electrocardiogram (ECG)', 700.00, 1, 1, 1, 'procedure'),
(19, 2, 'RAD-CT-HEAD', 'CT Scan - Head (Plain)', 6500.00, 1, 1, 1, 'procedure'),
(20, 2, 'RAD-MRI-BRAIN', 'MRI - Brain (Plain)', 12000.00, 1, 1, 1, 'procedure'),
(21, 2, 'PROC-NEB', 'Nebulization', 350.00, 1, 1, 1, 'session'),
(22, 2, 'PROC-WOUND', 'Wound Dressing (Simple)', 300.00, 1, 1, 1, 'procedure'),
(23, 2, 'PROC-IVINS', 'IV Insertion / Cannulation', 400.00, 1, 0, 1, 'procedure'),
(24, 2, 'PROC-CATH', 'Urinary Catheter Insertion', 800.00, 1, 0, 1, 'procedure'),
(25, 2, 'PROC-ENEMA', 'Enema Administration', 350.00, 1, 1, 1, 'procedure'),
(26, 3, 'MED-PARA500', 'Paracetamol 500mg Tablet', 8.00, 1, 1, 0, 'tablet'),
(27, 3, 'MED-PARA250SYP', 'Paracetamol 250mg/5mL Syrup', 120.00, 1, 1, 0, 'bottle'),
(28, 3, 'MED-MEFEN500', 'Mefenamic Acid 500mg Capsule', 12.00, 1, 1, 0, 'capsule'),
(29, 3, 'MED-AMOX500', 'Amoxicillin 500mg Capsule', 15.00, 1, 1, 0, 'capsule'),
(30, 3, 'MED-CEFU500', 'Cefuroxime 500mg Tablet', 45.00, 1, 1, 0, 'tablet'),
(31, 3, 'MED-AZI500', 'Azithromycin 500mg Tablet', 85.00, 1, 1, 0, 'tablet'),
(32, 3, 'MED-OMEP20', 'Omeprazole 20mg Capsule', 18.00, 1, 1, 0, 'capsule'),
(33, 3, 'MED-AMLOD5', 'Amlodipine 5mg Tablet', 10.00, 1, 1, 0, 'tablet'),
(34, 3, 'MED-LOSAR50', 'Losartan 50mg Tablet', 14.00, 1, 1, 0, 'tablet'),
(35, 3, 'MED-METFOR500', 'Metformin 500mg Tablet', 9.00, 1, 1, 0, 'tablet'),
(36, 3, 'MED-SALB-NEB', 'Salbutamol Nebule 2.5mg/2.5mL', 45.00, 1, 1, 0, 'ampoule'),
(37, 3, 'MED-BUDES-NEB', 'Budesonide Nebule 0.5mg/2mL', 90.00, 1, 1, 0, 'ampoule'),
(38, 3, 'MED-IV-PNSS', 'PNSS 1L (Plain Normal Saline Solution)', 120.00, 1, 1, 0, 'bottle'),
(39, 3, 'MED-IV-D5LRS', 'D5LRS 1L (Dextrose 5% in Lactated Ringer\'s)', 150.00, 1, 1, 0, 'bottle'),
(40, 3, 'MED-IV-D5W', 'D5W 1L (Dextrose 5% in Water)', 130.00, 1, 1, 0, 'bottle'),
(41, 3, 'MED-TRAM50', 'Tramadol 50mg Capsule', 25.00, 1, 1, 0, 'capsule'),
(42, 3, 'MED-KETO30', 'Ketorolac 30mg/mL Injection', 180.00, 1, 1, 0, 'ampoule'),
(43, 3, 'MED-DEXA4', 'Dexamethasone 4mg/mL Injection', 60.00, 1, 1, 0, 'ampoule'),
(44, 3, 'MED-CEFTRIAX', 'Ceftriaxone 1g Vial', 350.00, 1, 1, 0, 'vial'),
(45, 4, 'PROF-CONSULT', 'Doctor Consultation Fee', 800.00, 1, 0, 0, 'visit'),
(46, 4, 'PROF-VISIT', 'Ward Visit Fee', 600.00, 1, 0, 0, 'visit'),
(47, 4, 'PROF-PROC', 'Procedure / Minor Surgery Fee', 3500.00, 1, 0, 0, 'procedure'),
(48, 4, 'PROF-READ', 'Diagnostic Result Reading Fee', 300.00, 1, 0, 0, 'test');

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
(1, 1, 'Jerome', 'Pacaldo', '2000-06-13', '09127482312', 'Xavier Heights, Uptown, CdeO', 'pacaldo@gmail.com', '09336416221', NULL, NULL, '2026-10-06 21:46:05', 1);

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
(17, 4, 2, 4, '401', 1),
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
(1, 1, 17, '2026-10-06 21:46:30', NULL, 4500.00, 'Initial placement; preferred room type: Deluxe Suite.', 3, 1);

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
(1, 1, 6, 13, '2026-10-06 21:50:14', 'Completed', NULL);

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
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `fulfilled_datetime` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_fulfillment_item`
--

INSERT INTO `service_fulfillment_item` (`fulfillment_item_id`, `fulfillment_id`, `request_item_id`, `charge_item_id`, `quantity_fulfilled`, `unit_price`, `notes`, `fulfilled_datetime`) VALUES
(1, 1, 1, 16, 1, 900.00, NULL, '2026-10-06 21:50:14');

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
(2, 1, 1, 3, 'Routine', 'Pending', NULL, NULL, '2026-10-06 21:48:58', NULL, NULL);

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
(2, 2, 28, 10, 0, NULL, NULL, '2026-10-06 21:48:58');

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
  MODIFY `admission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admission_diagnosis`
--
ALTER TABLE `admission_diagnosis`
  MODIFY `admission_diagnosis_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admission_doctor`
--
ALTER TABLE `admission_doctor`
  MODIFY `admission_doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admission_status`
--
ALTER TABLE `admission_status`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `billing_statement`
--
ALTER TABLE `billing_statement`
  MODIFY `statement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `charge_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `gender_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `patient`
--
ALTER TABLE `patient`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `room_assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `room_status`
--
ALTER TABLE `room_status`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `room_type`
--
ALTER TABLE `room_type`
  MODIFY `room_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `service_fulfillment`
--
ALTER TABLE `service_fulfillment`
  MODIFY `fulfillment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `service_fulfillment_item`
--
ALTER TABLE `service_fulfillment_item`
  MODIFY `fulfillment_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `service_fulfillment_receipt_item`
--
ALTER TABLE `service_fulfillment_receipt_item`
  MODIFY `receipt_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_request`
--
ALTER TABLE `service_request`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `service_request_item`
--
ALTER TABLE `service_request_item`
  MODIFY `request_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
