<?php

require_once __DIR__ . "/../models/Contact.php";

class ContactController
{
    private $contactModel;

    public function __construct($conn)
    {
        $this->contactModel = new Contact($conn);
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

    // ========== GUEST ==========

    public function createContact()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $full_name = trim($data["full_name"] ?? "");
        $email = trim($data["email"] ?? "");
        $subject = trim($data["subject"] ?? "");
        $message = trim($data["message"] ?? "");

        // check required
        if ($full_name === "" || $email === "" || $message === "") {
            return $this->json(false, "Vui lòng nhập đầy đủ họ tên, email và nội dung.", null, 400);
        }

        // check full name
        if (strlen($full_name) < 2 || strlen($full_name) > 100) {
            return $this->json(false, "Họ tên phải từ 2 đến 100 ký tự.", null, 400);
        }

        // check email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(false, "Email không hợp lệ.", null, 400);
        }

        // subject optional -> chỉ check nếu có nhập
        if ($subject !== "" && strlen($subject) > 150) {
            return $this->json(false, "Chủ đề không được vượt quá 150 ký tự.", null, 400);
        }

        // check message
        if (strlen($message) < 10) {
            return $this->json(false, "Nội dung liên hệ phải có ít nhất 10 ký tự.", null, 400);
        }

        // sanitize
        $full_name = htmlspecialchars($full_name, ENT_QUOTES, "UTF-8");
        $email = htmlspecialchars($email, ENT_QUOTES, "UTF-8");
        $subject = htmlspecialchars($subject, ENT_QUOTES, "UTF-8");
        $message = htmlspecialchars($message, ENT_QUOTES, "UTF-8");

        // insert
        $result = $this->contactModel->create(
            $full_name,
            $email,
            $subject === "" ? null : $subject,
            $message
        );

        if ($result) {
            $this->json(true, "Gửi liên hệ thành công.", null, 201);
        } else {
            $this->json(false, "Không thể gửi liên hệ.", null, 500);
        }
    }

    // ========== ADMIN ==========

    public function adminIndex()
    {
        try {
            $q = $_GET["q"] ?? "";
            $page = $_GET["page"] ?? 1;
            $limit = $_GET["limit"] ?? 10;
            $status = $_GET["status"] ?? "";

            $data = $this->contactModel->getAll($q, $page, $limit, $status);
            $this->json(true, "Lấy danh sách liên hệ thành công", $data);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    public function adminShow()
    {
        try {
            $id = $_GET["id"] ?? 0;
            $contact = $this->contactModel->find((int)$id);

            if (!$contact) {
                return $this->json(false, "Không tìm thấy liên hệ", null, 404);
            }

            $this->json(true, "Lấy chi tiết liên hệ thành công", $contact);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    public function adminUpdateStatus()
    {
        try {
            $id = $_GET["id"] ?? 0;
            $data = json_decode(file_get_contents("php://input"), true);

            $status = $data["status"] ?? null;
            $adminId = $data["admin_id"] ?? null;

            if (!$status || !$adminId) {
                return $this->json(false, "Thiếu status hoặc admin_id", null, 400);
            }

            $validStatuses = ['new', 'in_progress', 'replied', 'closed'];
            if (!in_array($status, $validStatuses, true)) {
                return $this->json(false, "Status không hợp lệ. Cho phép: " . implode(', ', $validStatuses), null, 422);
            }

            $result = $this->contactModel->updateStatus((int)$id, $status, (int)$adminId);

            if ($result) {
                $updated = $this->contactModel->find((int)$id);
                $this->json(true, "Cập nhật trạng thái liên hệ thành công", $updated);
            } else {
                $this->json(false, "Không thể cập nhật trạng thái", null, 500);
            }
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    public function adminDelete()
    {
        try {
            $id = $_GET["id"] ?? 0;

            $result = $this->contactModel->delete((int)$id);

            if ($result) {
                $this->json(true, "Xóa liên hệ thành công");
            } else {
                $this->json(false, "Không tìm thấy liên hệ để xóa", null, 404);
            }
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }
}