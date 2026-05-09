<?php

require_once __DIR__ . "/../models/User.php";

class CustomerController
{
    private $userModel;

    public function __construct($conn)
    {
        $this->userModel = new User($conn);
    }

    public function getInfo()
    {
        $user_id = $_GET["user_id"] ?? null;

        if (!$user_id) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Thiếu user_id."
            ]);
            return;
        }

        $user = $this->userModel->findCustomerById($user_id);

        if (!$user) {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "Không tìm thấy thông tin khách hàng."
            ]);
            return;
        }

        unset($user["user_id"]);

        echo json_encode([
            "success" => true,
            "message" => "Lấy thông tin thành công.",
            "user" => $user
        ]);
    }

    public function updateInfo()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $user_id = $data["user_id"] ?? null;
        $full_name = trim($data["full_name"] ?? "");
        $phone = trim($data["phone"] ?? "");
        $shipping_address = trim($data["shipping_address"] ?? "");
        $receiver_name = trim($data["receiver_name"] ?? "");
        $receiver_phone = trim($data["receiver_phone"] ?? "");

        if (!$user_id) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Thiếu user_id."
            ]);
            return;
        }

        if ($full_name === "" || $phone === "" || $shipping_address === "") {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Vui lòng nhập đầy đủ họ tên, số điện thoại và địa chỉ giao hàng."
            ]);
            return;
        }

        if (strlen($full_name) < 2 || strlen($full_name) > 100) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Họ tên phải từ 2 đến 100 ký tự."
            ]);
            return;
        }

        if (!preg_match('/^0[0-9]{9}$/', $phone)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Số điện thoại phải có 10 số và bắt đầu bằng 0."
            ]);
            return;
        }

        if (strlen($shipping_address) < 5 || strlen($shipping_address) > 255) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Địa chỉ giao hàng phải từ 5 đến 255 ký tự."
            ]);
            return;
        }

        if ($receiver_name !== "" && strlen($receiver_name) > 100) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Tên người nhận không được vượt quá 100 ký tự."
            ]);
            return;
        }

        if ($receiver_phone !== "" && !preg_match('/^0[0-9]{9}$/', $receiver_phone)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "SĐT người nhận phải có 10 số và bắt đầu bằng 0."
            ]);
            return;
        }

        $cleanData = [
            "full_name" => htmlspecialchars($full_name, ENT_QUOTES, "UTF-8"),
            "phone" => htmlspecialchars($phone, ENT_QUOTES, "UTF-8"),
            "shipping_address" => htmlspecialchars($shipping_address, ENT_QUOTES, "UTF-8"),
            "receiver_name" => $receiver_name === "" ? null : htmlspecialchars($receiver_name, ENT_QUOTES, "UTF-8"),
            "receiver_phone" => $receiver_phone === "" ? null : htmlspecialchars($receiver_phone, ENT_QUOTES, "UTF-8"),
        ];

        $result = $this->userModel->updateCustomerInfo($user_id, $cleanData);

        if ($result) {
            $updatedUser = $this->userModel->findCustomerById($user_id);
            unset($updatedUser["user_id"]);

            echo json_encode([
                "success" => true,
                "message" => "Cập nhật thông tin thành công.",
                "user" => $updatedUser
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Không thể cập nhật thông tin."
            ]);
        }
    }

    public function changePassword()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $user_id = $data["user_id"] ?? null;
        $old_password = trim($data["old_password"] ?? "");
        $new_password = trim($data["new_password"] ?? "");
        $confirm_password = trim($data["confirm_password"] ?? "");

        if (!$user_id || $old_password === "" || $new_password === "" || $confirm_password === "") {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Vui lòng nhập đầy đủ thông tin đổi mật khẩu."
            ]);
            return;
        }

        if (strlen($new_password) < 6) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Mật khẩu mới phải có ít nhất 6 ký tự."
            ]);
            return;
        }

        if ($new_password !== $confirm_password) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Xác nhận mật khẩu không khớp."
            ]);
            return;
        }

        $user = $this->userModel->findById($user_id);

        if (!$user) {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "Tài khoản không tồn tại."
            ]);
            return;
        }

        if (!password_verify($old_password, $user["password_hash"])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Mật khẩu hiện tại không đúng."
            ]);
            return;
        }

        $newHash = password_hash($new_password, PASSWORD_DEFAULT);
        $result = $this->userModel->updatePassword($user_id, $newHash);

        if ($result) {
            echo json_encode([
                "success" => true,
                "message" => "Đổi mật khẩu thành công."
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Không thể đổi mật khẩu."
            ]);
        }
    }


    public function getAllCustomersForAdmin()
    {
        try {
            $q = $_GET["q"] ?? "";
            $page = $_GET["page"] ?? 1;
            $limit = $_GET["limit"] ?? 10;

            $data = $this->userModel->getAllCustomers($q, $page, $limit);

            echo json_encode([
                "success" => true,
                "message" => "Lấy danh sách khách hàng thành công",
                "data" => $data
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function updateCustomerStatus()
    {
        try {
            $customerId = $_GET["id"] ?? 0;

            $data = json_decode(file_get_contents("php://input"), true);

            $status = $data["status"] ?? null;
            $adminId = $data["admin_id"] ?? null;

            $result = $this->userModel->updateCustomerStatus(
                $customerId,
                $status,
                $adminId
            );

            if (!$result["success"]) {
                http_response_code(403);
            }

            echo json_encode($result, JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // Upload avatar for customer or admin
    public function uploadAvatar()
    {
        try {
            $userId = $_POST["user_id"] ?? null;

            if (!$userId) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Thiếu user_id"]);
                return;
            }

            if (!isset($_FILES["avatar"]) || $_FILES["avatar"]["error"] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Chưa chọn ảnh đại diện"]);
                return;
            }

            $file = $_FILES["avatar"];
            $allowedExt = ["jpg", "jpeg", "png", "webp"];
            $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExt, true)) {
                http_response_code(422);
                echo json_encode(["success" => false, "message" => "Chỉ cho phép JPG, PNG, WEBP"]);
                return;
            }

            // Max 2MB
            if ($file["size"] > 2 * 1024 * 1024) {
                http_response_code(422);
                echo json_encode(["success" => false, "message" => "Ảnh không được vượt quá 2MB"]);
                return;
            }

            $uploadDir = __DIR__ . "/../../public/uploads/avatars/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = "avatar_" . $userId . "_" . time() . "." . $ext;
            $targetPath = $uploadDir . $fileName;

            if (!move_uploaded_file($file["tmp_name"], $targetPath)) {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Không thể lưu ảnh"]);
                return;
            }

            $avatarPath = "uploads/avatars/" . $fileName;
            $this->userModel->updateAvatar((int)$userId, $avatarPath);

            echo json_encode([
                "success" => true,
                "message" => "Cập nhật avatar thành công",
                "data" => ["avatar" => $avatarPath]
            ]);

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
        }
    }

    // Admin: reset customer password
    public function resetPassword()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            $customerId = $data["customer_id"] ?? null;
            $adminId = $data["admin_id"] ?? null;

            if (!$customerId || !$adminId) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Thiếu customer_id hoặc admin_id"]);
                return;
            }

            $result = $this->userModel->resetCustomerPassword((int)$customerId, (int)$adminId);

            if (!$result["success"]) {
                http_response_code(403);
            }

            echo json_encode($result, JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
        }
    }
}