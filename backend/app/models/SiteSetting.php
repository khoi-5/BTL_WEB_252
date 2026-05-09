<?php

class SiteSetting
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Public: get all settings (or filter by group)
    public function getAll($group = '')
    {
        if ($group !== '') {
            $stmt = $this->conn->prepare("SELECT setting_key, setting_value, setting_group FROM site_settings WHERE setting_group = ?");
            $stmt->execute([$group]);
        } else {
            $stmt = $this->conn->prepare("SELECT setting_key, setting_value, setting_group FROM site_settings");
            $stmt->execute();
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Return as key-value map grouped by setting_group
        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = $row['setting_value'];
        }

        return $result;
    }

    // Public: get all settings grouped
    public function getAllGrouped()
    {
        $stmt = $this->conn->prepare("SELECT setting_key, setting_value, setting_group FROM site_settings ORDER BY setting_group");
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($rows as $row) {
            $group = $row['setting_group'] ?? 'general';
            $grouped[$group][$row['setting_key']] = $row['setting_value'];
        }

        return $grouped;
    }

    // Public: get single setting value
    public function get($key)
    {
        $stmt = $this->conn->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['setting_value'] : null;
    }

    // Admin: update a single setting
    public function set($key, $value, $adminId = null)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO site_settings (setting_key, setting_value, updated_by_admin_id)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by_admin_id = VALUES(updated_by_admin_id)
        ");

        return $stmt->execute([$key, $value, $adminId ? (int)$adminId : null]);
    }

    // Admin: bulk update multiple settings
    public function bulkUpdate($settings, $adminId = null)
    {
        $this->conn->beginTransaction();

        try {
            foreach ($settings as $key => $value) {
                $this->set($key, $value, $adminId);
            }

            $this->conn->commit();
            return true;
        } catch (\Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    // Admin: get all settings with full details for admin panel
    public function getAllForAdmin()
    {
        $stmt = $this->conn->prepare("
            SELECT 
                ss.setting_key,
                ss.setting_value,
                ss.setting_group,
                ss.updated_by_admin_id,
                ss.updated_at,
                u.full_name AS updated_by_name
            FROM site_settings ss
            LEFT JOIN admins a ON ss.updated_by_admin_id = a.admin_id
            LEFT JOIN users u ON a.admin_id = u.user_id
            ORDER BY ss.setting_group, ss.setting_key
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
