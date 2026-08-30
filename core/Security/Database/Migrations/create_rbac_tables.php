<?php
// Path: core/Security/Database/Migrations/create_rbac_tables.php

namespace Core\Security\Database\Migrations;

use Core\Database\Migration;

class CreateRbacTables extends Migration
{
    public function up(): void
    {
        $sql = "
            -- 1. جدول الصلاحيات (التي ذكرتها بصيغة Module.Resource.Action)
            CREATE TABLE IF NOT EXISTS permissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE, -- e.g., 'sales.invoice.post'
                module VARCHAR(50) NOT NULL,       -- e.g., 'sales'
                description VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            -- 2. جدول الأدوار (الـ 28 دور التي ذكرتها)
            CREATE TABLE IF NOT EXISTS roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE, -- e.g., 'Branch Manager'
                description VARCHAR(255) NULL,
                is_system TINYINT(1) DEFAULT 0,    -- 1 means it cannot be deleted by users
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            -- 3. ربط الصلاحيات بالأدوار (Role has Permissions)
            CREATE TABLE IF NOT EXISTS role_permissions (
                role_id INT NOT NULL,
                permission_id INT NOT NULL,
                PRIMARY KEY (role_id, permission_id),
                FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
                FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            -- 4. ربط المستخدمين بالأدوار (User has Roles)
            CREATE TABLE IF NOT EXISTS user_roles (
                user_id INT NOT NULL,
                role_id INT NOT NULL,
                PRIMARY KEY (user_id, role_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            -- 5. جدول النطاقات (Scopes) لتحديد مكان عمل المستخدم
            -- هذا الجدول هو سر الـ Enterprise! يحدد هل المستخدم يرى فرع معين أو شركة معينة
            CREATE TABLE IF NOT EXISTS user_scopes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                scope_type ENUM('tenant', 'company', 'branch', 'warehouse', 'department') NOT NULL,
                scope_id INT NOT NULL, -- The ID of the specific branch or company
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            -- 6. استثناءات الصلاحيات المباشرة للمستخدم (Direct User Permissions)
            -- تستخدم إذا أردنا إعطاء مستخدم صلاحية محددة دون تغيير دوره بالكامل
            CREATE TABLE IF NOT EXISTS user_direct_permissions (
                user_id INT NOT NULL,
                permission_id INT NOT NULL,
                is_revoked TINYINT(1) DEFAULT 0, -- If 1, it explicitly DENIES this permission even if the role has it
                PRIMARY KEY (user_id, permission_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        $this->db->exec($sql);
    }

    public function down(): void
    {
        $this->db->exec("DROP TABLE IF EXISTS user_direct_permissions;");
        $this->db->exec("DROP TABLE IF EXISTS user_scopes;");
        $this->db->exec("DROP TABLE IF EXISTS user_roles;");
        $this->db->exec("DROP TABLE IF EXISTS role_permissions;");
        $this->db->exec("DROP TABLE IF EXISTS roles;");
        $this->db->exec("DROP TABLE IF EXISTS permissions;");
    }
}