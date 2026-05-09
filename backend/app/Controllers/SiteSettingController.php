<?php

require_once __DIR__ . "/../models/SiteSetting.php";

class SiteSettingController
{
    private $model;

    public function __construct($conn)
    {
        $this->model = new SiteSetting($conn);
    }

    private function json($success, $message, $data = null, $code = 200)
    {
        http_response_code($code);
        echo json_encode([
            "success" => $success,
            "message" => $message,
            "data" => $data
        ], JSON_UNESCAPED_UNICODE);
    }

    // ========== PUBLIC ==========

    // GET /api/settings — returns key-value map (optionally filtered by group)
    public function publicIndex()
    {
        try {
            $group = $_GET["group"] ?? "";
            $data = $this->model->getAll($group);
            $this->json(true, "Lấy cài đặt thành công", $data);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    // ========== ADMIN ==========

    // GET /api/admin/settings — returns full details for admin panel
    public function adminIndex()
    {
        try {
            $data = $this->model->getAllForAdmin();
            $this->json(true, "Lấy cài đặt thành công", $data);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    // GET /api/admin/settings/grouped — returns grouped settings
    public function adminGrouped()
    {
        try {
            $data = $this->model->getAllGrouped();
            $this->json(true, "Lấy cài đặt nhóm thành công", $data);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    // PUT /api/admin/settings/update — bulk update settings
    public function adminUpdate()
    {
        try {
            $body = json_decode(file_get_contents("php://input"), true) ?? [];

            $settings = $body["settings"] ?? [];
            $adminId = $body["admin_id"] ?? null;

            if (empty($settings) || !is_array($settings)) {
                return $this->json(false, "Vui lòng cung cấp danh sách cài đặt cần cập nhật", null, 422);
            }

            if (!$adminId) {
                return $this->json(false, "Thiếu admin_id", null, 400);
            }

            $this->model->bulkUpdate($settings, (int)$adminId);

            $updated = $this->model->getAllGrouped();
            $this->json(true, "Cập nhật cài đặt thành công", $updated);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }
}
