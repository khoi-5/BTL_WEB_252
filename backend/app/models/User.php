<?php

class User
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function findByEmailOrPhone($identifier)
    {
        $sql = "SELECT * FROM users WHERE email = :identifier OR phone = :identifier LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":identifier" => $identifier]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByEmailOrPhoneOnly($email, $phone)
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM users
            WHERE email = ? OR phone = ?
            LIMIT 1
        ");

        $stmt->execute([$email, $phone]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function emailExists($email)
    {
        $sql = "SELECT user_id FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":email" => $email]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    }

    public function phoneExists($phone)
    {
        $sql = "SELECT user_id FROM users WHERE phone = :phone LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":phone" => $phone]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    }

    public function createCustomer($data)
    {
        $this->conn->beginTransaction();

        try {
            $sqlUser = "INSERT INTO users 
                (full_name, email, password_hash, user_role, phone)
                VALUES 
                (:full_name, :email, :password_hash, 'customer', :phone)";

            $stmtUser = $this->conn->prepare($sqlUser);
            $stmtUser->execute([
                ":full_name" => $data["full_name"],
                ":email" => $data["email"],
                ":password_hash" => $data["password_hash"],
                ":phone" => $data["phone"]
            ]);

            $userId = $this->conn->lastInsertId();

            $sqlCustomer = "INSERT INTO customers
                (customer_id, shipping_address, receiver_name, receiver_phone, customer_status)
                VALUES
                (:customer_id, :shipping_address, :receiver_name, :receiver_phone, 1)";

            $stmtCustomer = $this->conn->prepare($sqlCustomer);
            $stmtCustomer->execute([
                ":customer_id" => $userId,
                ":shipping_address" => $data["shipping_address"],
                ":receiver_name" => $data["receiver_name"] ?? $data["full_name"],
                ":receiver_phone" => $data["receiver_phone"] ?? $data["phone"]
            ]);

            $this->conn->commit();

            return [
                "user_id" => (int)$userId,
                "full_name" => $data["full_name"],
                "email" => $data["email"],
                "phone" => $data["phone"],
                "role" => "customer"
            ];

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function findCustomerById($user_id)
    {
        $sql = "
            SELECT 
                u.user_id,
                u.full_name,
                u.email,
                u.phone,
                u.avatar,
                u.created_at,
                u.updated_at,
                c.customer_id,
                c.shipping_address,
                c.receiver_name,
                c.receiver_phone,
                c.customer_status
            FROM users u
            INNER JOIN customers c ON u.user_id = c.customer_id
            WHERE u.user_id = ?
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$user_id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findAdminById($user_id)
    {
        $sql = "
            SELECT 
                u.user_id,
                u.full_name,
                u.email,
                u.phone,
                u.avatar,
                u.created_at,
                u.updated_at,
                a.admin_id,
                a.salary,
                a.is_super_admin
            FROM users u
            INNER JOIN admins a ON u.user_id = a.admin_id
            WHERE u.user_id = ?
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$user_id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateCustomerInfo($user_id, $data)
    {
        $sqlUser = "
            UPDATE users
            SET full_name = ?, phone = ?
            WHERE user_id = ?
        ";

        $stmtUser = $this->conn->prepare($sqlUser);
        $stmtUser->execute([
            $data["full_name"],
            $data["phone"],
            $user_id
        ]);

        $sqlCustomer = "
            UPDATE customers
            SET shipping_address = ?, receiver_name = ?, receiver_phone = ?
            WHERE customer_id = ?
        ";

        $stmtCustomer = $this->conn->prepare($sqlCustomer);
        return $stmtCustomer->execute([
            $data["shipping_address"],
            $data["receiver_name"],
            $data["receiver_phone"],
            $user_id
        ]);
    }

    public function findById($user_id)
    {
        $sql = "SELECT * FROM users WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$user_id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePassword($user_id, $password_hash)
    {
        $sql = "UPDATE users SET password_hash = ? WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([$password_hash, $user_id]);
    }

    public function updateAdminInfo($admin_id, $data)
    {
        $sql = "
            UPDATE users
            SET full_name = ?, phone = ?
            WHERE user_id = ?
        ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            $data["full_name"],
            $data["phone"],
            $admin_id
        ]);
    }
    public function isSuperAdmin($adminId)
    {
        $stmt = $this->conn->prepare("
            SELECT is_super_admin
            FROM admins
            WHERE admin_id = ?
        ");

        $stmt->execute([(int)$adminId]);

        return (int)$stmt->fetchColumn() === 1;
    }

    public function getAllCustomers($keyword = "", $page = 1, $limit = 10)
    {
        $page = max(1, (int)$page);
        $limit = max(1, min(50, (int)$limit));
        $offset = ($page - 1) * $limit;

        $kw = "%" . trim($keyword) . "%";

        $countSql = "
            SELECT COUNT(*)
            FROM customers c
            JOIN users u ON c.customer_id = u.user_id
            WHERE u.full_name LIKE ?
            OR u.email LIKE ?
            OR COALESCE(u.phone,'') LIKE ?
            OR COALESCE(c.receiver_name,'') LIKE ?
            OR COALESCE(c.receiver_phone,'') LIKE ?
        ";

        $stmt = $this->conn->prepare($countSql);
        $stmt->execute([$kw,$kw,$kw,$kw,$kw]);
        $total = (int)$stmt->fetchColumn();

        $sql = "
            SELECT
                c.customer_id,
                u.full_name,
                u.email,
                u.phone,
                c.shipping_address,
                c.receiver_name,
                c.receiver_phone,
                c.customer_status,
                u.created_at
            FROM customers c
            JOIN users u ON c.customer_id = u.user_id
            WHERE u.full_name LIKE ?
            OR u.email LIKE ?
            OR COALESCE(u.phone,'') LIKE ?
            OR COALESCE(c.receiver_name,'') LIKE ?
            OR COALESCE(c.receiver_phone,'') LIKE ?
            ORDER BY c.customer_id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$kw,$kw,$kw,$kw,$kw]);

        return [
            "items" => $stmt->fetchAll(PDO::FETCH_ASSOC),
            "pagination" => [
                "current_page" => $page,
                "total_pages" => max(1, (int)ceil($total / $limit)),
                "total_items" => $total,
                "limit" => $limit
            ]
        ];
    }


    public function updateCustomerStatus($customerId, $status, $adminId)
    {
        if (!$this->isSuperAdmin($adminId)) {
            return [
                "success" => false,
                "message" => "Không đủ thẩm quyền"
            ];
        }

        $status = (int)$status;

        if (!in_array($status, [0, 1], true)) {
            return [
                "success" => false,
                "message" => "Status không hợp lệ"
            ];
        }

        $stmt = $this->conn->prepare("
            UPDATE customers
            SET customer_status = ?
            WHERE customer_id = ?
        ");

        $stmt->execute([
            $status,
            (int)$customerId
        ]);

        return [
            "success" => true,
            "message" => $status === 1
                ? "Mở khóa khách hàng thành công"
                : "Ban khách hàng thành công"
        ];
    }

    // ban nếu nhập sai mật khẩu quá nhiều lần 
    public function lockCustomerBySystem($customerId)
    {
        $stmt = $this->conn->prepare("
            UPDATE customers
            SET customer_status = 0
            WHERE customer_id = ?
        ");

        return $stmt->execute([
            (int)$customerId
        ]);
    }

    // Update user avatar
    public function updateAvatar($userId, $avatarPath)
    {
        $stmt = $this->conn->prepare("UPDATE users SET avatar = ? WHERE user_id = ?");
        return $stmt->execute([$avatarPath, (int)$userId]);
    }

    // Get user avatar
    public function getAvatar($userId)
    {
        $stmt = $this->conn->prepare("SELECT avatar FROM users WHERE user_id = ?");
        $stmt->execute([(int)$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['avatar'] : null;
    }

    // Admin: reset customer password
    public function resetCustomerPassword($customerId, $adminId)
    {
        if (!$this->isSuperAdmin($adminId)) {
            return [
                "success" => false,
                "message" => "Không đủ thẩm quyền"
            ];
        }

        $customer = $this->findCustomerById($customerId);
        if (!$customer) {
            return [
                "success" => false,
                "message" => "Không tìm thấy khách hàng"
            ];
        }

        // Reset to default password: "123456"
        $defaultHash = password_hash("123456", PASSWORD_DEFAULT);
        $this->updatePassword($customerId, $defaultHash);

        return [
            "success" => true,
            "message" => "Đã reset mật khẩu về 123456"
        ];
    }
}