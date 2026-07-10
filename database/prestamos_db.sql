-- Sistema de Préstamos - Script SQL
-- Ejecutar este script directamente en MySQL para crear la base de datos, tablas y datos iniciales

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS `prestamos_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `prestamos_db`;

-- Tabla de usuarios
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NULL,
  `is_super_admin` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de empresas
CREATE TABLE `companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruc` varchar(20) COLLATE utf8mb4_unicode_ci NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci NULL,
  `description` text COLLATE utf8mb4_unicode_ci NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_ruc_unique` (`ruc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de roles
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de permisos
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla pivot: usuario - empresa (con rol)
CREATE TABLE `company_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_user_company_id_user_id_unique` (`company_id`, `user_id`),
  KEY `company_user_user_id_foreign` (`user_id`),
  KEY `company_user_role_id_foreign` (`role_id`),
  CONSTRAINT `company_user_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: rol - permiso
CREATE TABLE `role_permission` (
  `role_id` bigint unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  KEY `role_permission_permission_id_foreign` (`permission_id`),
  CONSTRAINT `role_permission_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permission_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: usuario - permiso (permisos adicionales por usuario/empresa)
CREATE TABLE `user_permission` (
  `user_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`user_id`, `company_id`, `permission_id`),
  KEY `user_permission_company_id_foreign` (`company_id`),
  KEY `user_permission_permission_id_foreign` (`permission_id`),
  CONSTRAINT `user_permission_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_permission_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_permission_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: tipología de préstamos
CREATE TABLE `loan_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `max_amount` decimal(15,2) NULL,
  `max_term_months` int NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loan_types_company_id_foreign` (`company_id`),
  CONSTRAINT `loan_types_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: préstamos
CREATE TABLE `loans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `loan_type_id` bigint unsigned NULL,
  `created_by` bigint unsigned NOT NULL,
  `approved_by` bigint unsigned NULL,
  `client_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_id_number` varchar(30) COLLATE utf8mb4_unicode_ci NULL,
  `client_phone` varchar(20) COLLATE utf8mb4_unicode_ci NULL,
  `client_email` varchar(255) COLLATE utf8mb4_unicode_ci NULL,
  `client_address` varchar(255) COLLATE utf8mb4_unicode_ci NULL,
  `amount` decimal(15,2) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `term_months` int NOT NULL,
  `monthly_payment` decimal(15,2) NULL,
  `total_to_pay` decimal(15,2) NULL,
  `total_paid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `start_date` date NULL,
  `end_date` date NULL,
  `purpose` varchar(255) COLLATE utf8mb4_unicode_ci NULL,
  `notes` text COLLATE utf8mb4_unicode_ci NULL,
  `status` enum('pending','approved','active','finished','cancelled','overdue') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loans_company_id_status_index` (`company_id`, `status`),
  KEY `loans_loan_type_id_foreign` (`loan_type_id`),
  KEY `loans_created_by_foreign` (`created_by`),
  KEY `loans_approved_by_foreign` (`approved_by`),
  CONSTRAINT `loans_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `loans_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loans_loan_type_id_foreign` FOREIGN KEY (`loan_type_id`) REFERENCES `loan_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: pagos de préstamos
CREATE TABLE `loan_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` bigint unsigned NOT NULL,
  `registered_by` bigint unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `capital` decimal(15,2) NULL,
  `interest` decimal(15,2) NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'efectivo',
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci NULL,
  `notes` text COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loan_payments_loan_id_foreign` (`loan_id`),
  KEY `loan_payments_registered_by_foreign` (`registered_by`),
  CONSTRAINT `loan_payments_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loan_payments_registered_by_foreign` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tablas de soporte de Laravel
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `reserved_at` int unsigned NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci NULL,
  `cancelled_at` int NULL,
  `created_at` int NOT NULL,
  `finished_at` int NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- INSERTAR DATOS INICIALES

-- Roles
INSERT INTO `roles` (`name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
('Super Administrador', 'super_admin', 'Acceso completo al sistema', NOW(), NOW()),
('Administrador de Empresa', 'admin', 'Administrador de una empresa específica', NOW(), NOW()),
('Gerente', 'manager', 'Gerente de préstamos', NOW(), NOW()),
('Cajero', 'cashier', 'Operador de caja y pagos', NOW(), NOW()),
('Empleado', 'employee', 'Empleado básico', NOW(), NOW());

-- Permisos
INSERT INTO `permissions` (`name`, `slug`, `module`, `description`, `created_at`, `updated_at`) VALUES
('Ver Empresas', 'companies.view', 'companies', NULL, NOW(), NOW()),
('Crear Empresas', 'companies.create', 'companies', NULL, NOW(), NOW()),
('Editar Empresas', 'companies.edit', 'companies', NULL, NOW(), NOW()),
('Eliminar Empresas', 'companies.delete', 'companies', NULL, NOW(), NOW()),
('Ver Usuarios', 'users.view', 'users', NULL, NOW(), NOW()),
('Crear Usuarios', 'users.create', 'users', NULL, NOW(), NOW()),
('Editar Usuarios', 'users.edit', 'users', NULL, NOW(), NOW()),
('Eliminar Usuarios', 'users.delete', 'users', NULL, NOW(), NOW()),
('Ver Roles', 'roles.view', 'roles', NULL, NOW(), NOW()),
('Crear Roles', 'roles.create', 'roles', NULL, NOW(), NOW()),
('Editar Roles', 'roles.edit', 'roles', NULL, NOW(), NOW()),
('Eliminar Roles', 'roles.delete', 'roles', NULL, NOW(), NOW()),
('Ver Préstamos', 'loans.view', 'loans', NULL, NOW(), NOW()),
('Crear Préstamos', 'loans.create', 'loans', NULL, NOW(), NOW()),
('Editar Préstamos', 'loans.edit', 'loans', NULL, NOW(), NOW()),
('Aprobar Préstamos', 'loans.approve', 'loans', NULL, NOW(), NOW()),
('Eliminar Préstamos', 'loans.delete', 'loans', NULL, NOW(), NOW()),
('Ver Pagos', 'payments.view', 'payments', NULL, NOW(), NOW()),
('Registrar Pagos', 'payments.create', 'payments', NULL, NOW(), NOW()),
('Ver Reportes', 'reports.view', 'reports', NULL, NOW(), NOW());

-- Empresa Demo
INSERT INTO `companies` (`name`, `ruc`, `address`, `phone`, `email`, `active`, `created_at`, `updated_at`) VALUES
('Empresa Demo', '12345678901', 'Calle Principal 123', '+503 1234-5678', 'info@empresademo.com', 1, NOW(), NOW());

-- Usuarios
INSERT INTO `users` (`name`, `email`, `password`, `is_super_admin`, `active`, `created_at`, `updated_at`) VALUES
('Super Administrador', 'superadmin@sistema.com', '$2y$12$xyz123...', 1, 1, NOW(), NOW()),
('Admin Empresa', 'admin@empresademo.com', '$2y$12$xyz123...', 0, 1, NOW(), NOW());

-- Asignar Admin a Empresa Demo (requiere que el usuario y la empresa existan)
INSERT INTO `company_user` (`company_id`, `user_id`, `role_id`, `active`, `created_at`, `updated_at`) VALUES
(1, 2, 2, 1, NOW(), NOW());

-- Permisos para Super Admin
INSERT INTO `role_permission` (`role_id`, `permission_id`) 
SELECT 1, id FROM `permissions`;

-- Permisos para Admin
INSERT INTO `role_permission` (`role_id`, `permission_id`) 
SELECT 2, id FROM `permissions` WHERE slug IN 
('companies.view', 'users.view', 'users.create', 'users.edit',
 'loans.view', 'loans.create', 'loans.edit', 'loans.approve',
 'payments.view', 'payments.create', 'reports.view');

-- Permisos para Manager
INSERT INTO `role_permission` (`role_id`, `permission_id`) 
SELECT 3, id FROM `permissions` WHERE slug IN 
('loans.view', 'loans.create', 'loans.approve', 'reports.view');

-- Permisos para Cashier
INSERT INTO `role_permission` (`role_id`, `permission_id`) 
SELECT 4, id FROM `permissions` WHERE slug IN 
('loans.view', 'payments.view', 'payments.create');

-- Permisos para Employee
INSERT INTO `role_permission` (`role_id`, `permission_id`) 
SELECT 5, id FROM `permissions` WHERE slug IN 
('loans.view');

-- NOTA IMPORTANTE: Las contraseñas están codificadas como hash de bcrypt
-- Para el Super Admin: superadmin@sistema.com / Admin@1234
-- Para el Admin: admin@empresademo.com / Admin@1234
-- Debes generar los hashes con: php artisan tinker
-- password_hash('Admin@1234', PASSWORD_BCRYPT);
-- O usar Laravel: Hash::make('Admin@1234')
