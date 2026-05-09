<?php

class Contact {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Guest: submit contact
    public function create($full_name, $email, $subject, $message) {
        $sql = "
            INSERT INTO contacts (full_name, email, subject, message)
            VALUES (?, ?, ?, ?)
        ";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$full_name, $email, $subject, $message]);
    }

    // Admin: get all contacts with pagination and search
    public function getAll($keyword = '', $page = 1, $limit = 10, $status = '')
    {
        $page = max(1, (int)$page);
        $limit = max(1, min(50, (int)$limit));
        $offset = ($page - 1) * $limit;

        $kw = "%" . trim($keyword) . "%";

        $where = "WHERE (c.full_name LIKE ? OR c.email LIKE ? OR COALESCE(c.subject,'') LIKE ? OR c.message LIKE ?)";
        $params = [$kw, $kw, $kw, $kw];

        if ($status !== '' && $status !== null) {
            $where .= " AND c.contact_status = ?";
            $params[] = $status;
        }

        $countSql = "SELECT COUNT(*) FROM contacts c $where";
        $stmt = $this->conn->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $sql = "
            SELECT
                c.contact_id,
                c.full_name,
                c.email,
                c.subject,
                c.message,
                c.contact_status,
                c.handled_by_admin_id,
                c.created_at,
                c.updated_at,
                u.full_name AS handled_by_name
            FROM contacts c
            LEFT JOIN admins a ON c.handled_by_admin_id = a.admin_id
            LEFT JOIN users u ON a.admin_id = u.user_id
            $where
            ORDER BY c.created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

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

    // Admin: get single contact
    public function find($id)
    {
        $stmt = $this->conn->prepare("
            SELECT c.*, u.full_name AS handled_by_name
            FROM contacts c
            LEFT JOIN admins a ON c.handled_by_admin_id = a.admin_id
            LEFT JOIN users u ON a.admin_id = u.user_id
            WHERE c.contact_id = ?
        ");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Admin: update contact status
    public function updateStatus($id, $status, $adminId)
    {
        $validStatuses = ['new', 'in_progress', 'replied', 'closed'];
        if (!in_array($status, $validStatuses, true)) {
            return false;
        }

        $stmt = $this->conn->prepare("
            UPDATE contacts
            SET contact_status = ?, handled_by_admin_id = ?
            WHERE contact_id = ?
        ");

        return $stmt->execute([$status, (int)$adminId, (int)$id]);
    }

    // Admin: delete contact
    public function delete($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM contacts WHERE contact_id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->rowCount() > 0;
    }
}