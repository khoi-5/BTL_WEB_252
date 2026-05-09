<?php

require_once __DIR__ . "/../models/User.php";
require_once __DIR__ . "/../models/Section.php";

class AdminController
{
    private $userModel;
    private $sectionModel;

    public function __construct($conn)
    {
        $this->userModel = new User($conn);
        $this->sectionModel = new Section($conn);
    }

    public function getInfo()
    {
        $admin_id = $_GET["admin_id"] ?? null;

        if (!$admin_id) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Thiếu admin_id."
            ]);
            return;
        }

        $admin = $this->userModel->findAdminById($admin_id);

        if (!$admin) {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "Không tìm thấy thông tin admin."
            ]);
            return;
        }

        echo json_encode([
            "success" => true,
            "message" => "Lấy thông tin admin thành công.",
            "admin" => $admin
        ]);
    }

    public function updateInfo()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $admin_id = $data["admin_id"] ?? null;
        $full_name = trim($data["full_name"] ?? "");
        $phone = trim($data["phone"] ?? "");

        if (!$admin_id) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Thiếu admin_id."
            ]);
            return;
        }

        if ($full_name === "" || $phone === "") {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Vui lòng nhập đầy đủ họ tên và số điện thoại."
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

        $admin = $this->userModel->findAdminById($admin_id);

        if (!$admin) {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "Admin không tồn tại."
            ]);
            return;
        }

        $cleanData = [
            "full_name" => htmlspecialchars($full_name, ENT_QUOTES, "UTF-8"),
            "phone" => htmlspecialchars($phone, ENT_QUOTES, "UTF-8"),
        ];

        $result = $this->userModel->updateAdminInfo($admin_id, $cleanData);

        if ($result) {
            $updatedAdmin = $this->userModel->findAdminById($admin_id);

            echo json_encode([
                "success" => true,
                "message" => "Cập nhật thông tin admin thành công.",
                "admin" => $updatedAdmin
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Không thể cập nhật thông tin admin."
            ]);
        }
    }

    public function changePassword()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $admin_id = $data["admin_id"] ?? null;
        $old_password = trim($data["old_password"] ?? "");
        $new_password = trim($data["new_password"] ?? "");
        $confirm_password = trim($data["confirm_password"] ?? "");

        if (!$admin_id || $old_password === "" || $new_password === "" || $confirm_password === "") {
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

        $admin = $this->userModel->findAdminById($admin_id);

        if (!$admin) {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "Admin không tồn tại."
            ]);
            return;
        }

        $user = $this->userModel->findById($admin_id);

        if (!$user || !password_verify($old_password, $user["password_hash"])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Mật khẩu hiện tại không đúng."
            ]);
            return;
        }

        $newHash = password_hash($new_password, PASSWORD_DEFAULT);
        $result = $this->userModel->updatePassword($admin_id, $newHash);

        if ($result) {
            echo json_encode([
                "success" => true,
                "message" => "Đổi mật khẩu admin thành công."
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Không thể đổi mật khẩu."
            ]);
        }
    }

    public function add_flash_sale() {
        $admin_id = $_GET["admin_id"] ?? null;
        $admin = $this->userModel->findAdminById($admin_id);
        if (!$admin) {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "Admin not found"
            ]);
            return;
        }

        $name = $_POST["name"] ?? null;
        $desc = $_POST["desc"] ?? null;
        $start_time = $_POST["start_time"] ?? null;
        $end_time = $_POST["end_time"] ?? null;
        
        $is_active = isset($_POST["is_active"]) ? (int)$_POST["is_active"] : null; 

        $image_file = $_FILES["image"] ?? null;
        $image_name = $image_file ? $image_file['name'] : null;

        if (!$name || !$desc || !$image_file || !$start_time || !$end_time || $is_active === null) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Please fill in all information."
            ]);
            return;
        }

        $result = $this->sectionModel->addFlashSale($name, $desc, $image_name, $start_time, $end_time, $is_active);

        if ($result) {
            echo json_encode([
                "success" => true,
                "message" => "Thêm flash sale thành công."
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Không thể thêm flash sale."
            ]);
        }
    }
}