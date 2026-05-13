<?php

require_once __DIR__ . "/../models/User.php";

class AuthService
{
    private $conn;
    private $userModel;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->userModel = new User($conn);
    }

    public function login($identifier, $password)
    {
        if (empty($identifier) || empty($password)) {
            throw new Exception("Vui lòng nhập đầy đủ thông tin");
        }

        $user = $this->userModel->findByEmailOrPhone($identifier);

        if (!$user) {
            throw new Exception("Tài khoản không tồn tại");
        }

        if (!password_verify($password, $user["password_hash"])) {
            throw new Exception("Mật khẩu không đúng");
        }

        // Nếu user là customer thì kiểm tra bị ban chưa
        $sqlCustomer = "SELECT customer_status FROM customers WHERE customer_id = ?";
        $stmtCustomer = $this->conn->prepare($sqlCustomer);
        $stmtCustomer->execute([$user["user_id"]]);
        $customer = $stmtCustomer->fetch(PDO::FETCH_ASSOC);

        if ($customer && (int)$customer["customer_status"] === 0) {
            throw new Exception("Tài khoản của bạn đã bị vô hiệu hóa");
        }

        // Check admin nếu không phải customer
        $sqlAdmin = "SELECT is_super_admin FROM admins WHERE admin_id = ?";
        $stmtAdmin = $this->conn->prepare($sqlAdmin);
        $stmtAdmin->execute([$user["user_id"]]);
        $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

        unset($user["password_hash"]);

        if ($customer) {
            $user["role"] = "customer";
            $user["customer_status"] = (int)$customer["customer_status"];
        } elseif ($admin) {
            $user["role"] = "admin";
            $user["is_super_admin"] = (int)$admin["is_super_admin"];
        } else {
            $user["role"] = "unknown";
        }

        return $user;
    }

    public function register($data)
    {
        $fullName = trim($data["full_name"] ?? "");
        $email = trim($data["email"] ?? "");
        $password = trim($data["password"] ?? "");
        $phone = trim($data["phone"] ?? "");
        $shippingAddress = trim($data["shipping_address"] ?? "");
        $receiverName = trim($data["receiver_name"] ?? "");
        $receiverPhone = trim($data["receiver_phone"] ?? "");

        if (
            empty($fullName) ||
            empty($email) ||
            empty($password) ||
            empty($phone) ||
            empty($shippingAddress)
        ) {
            throw new Exception("Vui lòng nhập đầy đủ thông tin");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email không hợp lệ");
        }

        if (!preg_match('/^0[0-9]{9}$/', $phone)) {
            throw new Exception("Số điện thoại không hợp lệ");
        }

        if (!empty($receiverPhone) && !preg_match('/^0[0-9]{9}$/', $receiverPhone)) {
            throw new Exception("Số điện thoại người nhận không hợp lệ");
        }

        $exist = $this->userModel->findByEmailOrPhoneOnly($email, $phone);

        if ($exist) {
            if ($exist["email"] === $email) {
                throw new Exception("Email đã tồn tại");
            }

            if ($exist["phone"] === $phone) {
                throw new Exception("Số điện thoại đã tồn tại");
            }
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        return $this->userModel->createCustomer([
            "full_name" => $fullName,
            "email" => $email,
            "password_hash" => $hashedPassword,
            "phone" => $phone,
            "shipping_address" => $shippingAddress,
            "receiver_name" => $receiverName ?: $fullName,
            "receiver_phone" => $receiverPhone ?: $phone,
        ]);
}

    public function checkStatus($userId)
    {
        if (!$userId) {
            throw new Exception("Thiếu user_id");
        }

        $sql = "SELECT customer_status FROM customers WHERE customer_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer && (int)$customer["customer_status"] === 0) {
            return [
                "active" => false
            ];
        }

        return [
            "active" => true
        ];
    }
    public function loginCustomer($identifier, $password)
    {
        if (empty($identifier) || empty($password)) {
            throw new Exception("Vui lòng nhập đầy đủ thông tin");
        }

        $user = $this->userModel->findByEmailOrPhone($identifier);

        if (!$user) {
            throw new Exception("Sai tài khoản hoặc mật khẩu");
        }

        $customer = $this->userModel->findCustomerById($user["user_id"]);

        if (!$customer) {
            throw new Exception("Tài khoản này không phải customer");
        }

        if ((int)$customer["customer_status"] === 0) {
            throw new Exception("Tài khoản của bạn đã bị vô hiệu hóa");
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $attemptKey = "customer_login_attempt_" . $user["user_id"];

        if (!isset($_SESSION[$attemptKey])) {
            $_SESSION[$attemptKey] = 0;
        }

        if (!password_verify($password, $user["password_hash"])) {
            $_SESSION[$attemptKey]++;

            $remaining = 5 - $_SESSION[$attemptKey];

            if ($_SESSION[$attemptKey] >= 5) {
                $this->userModel->lockCustomerBySystem($customer["customer_id"]);

                unset($_SESSION[$attemptKey]);

                throw new Exception("Tài khoản của bạn đã bị khóa do nhập sai mật khẩu 5 lần");
            }

            throw new Exception("Sai mật khẩu. Bạn còn {$remaining} lần đăng nhập");
        }

        $_SESSION[$attemptKey] = 0;

        unset($user["password_hash"]);
        $user["role"] = "customer";

        return array_merge($user, $customer);
    }

    public function loginAdmin($identifier, $password)
    {
        if (empty($identifier) || empty($password)) {
            throw new Exception("Vui lòng nhập đầy đủ thông tin");
        }

        $user = $this->userModel->findByEmailOrPhone($identifier);

        if (!$user || !password_verify($password, $user["password_hash"])) {
            throw new Exception("Sai tài khoản hoặc mật khẩu");
        }

        $admin = $this->userModel->findAdminById($user["user_id"]);

        if (!$admin) {
            throw new Exception("Tài khoản này không phải admin");
        }

        unset($user["password_hash"]);
        $user["role"] = "admin";

        return array_merge($user, $admin);
    }

    public function getAdminProfile($adminId)
    {
        if (!$adminId) {
            throw new Exception("Thiếu admin_id");
        }

        $admin = $this->userModel->findAdminById($adminId);

        if (!$admin) {
            throw new Exception("Admin không tồn tại");
        }

        $user = $this->userModel->findById($adminId);

        unset($user["password_hash"]);

        return array_merge($user, $admin);
    }
}