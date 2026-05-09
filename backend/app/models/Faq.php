<?php

class Faq
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Public: get active FAQs (optionally filter by category)
    public function getPublic($category = '')
    {
        $sql = "SELECT faq_id, question, answer, category FROM faqs WHERE is_active = 1";
        $params = [];

        if ($category !== '') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        $sql .= " ORDER BY faq_id ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Public: get distinct FAQ categories
    public function getCategories()
    {
        $stmt = $this->conn->prepare("
            SELECT DISTINCT category FROM faqs
            WHERE is_active = 1 AND category IS NOT NULL
            ORDER BY category ASC
        ");
        $stmt->execute();

        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'category');
    }

    // Admin: get all FAQs with pagination
    public function getAll($keyword = '', $page = 1, $limit = 10)
    {
        $page = max(1, (int)$page);
        $limit = max(1, min(50, (int)$limit));
        $offset = ($page - 1) * $limit;

        $kw = "%" . trim($keyword) . "%";

        $countSql = "
            SELECT COUNT(*) FROM faqs
            WHERE question LIKE ? OR answer LIKE ? OR COALESCE(category, '') LIKE ?
        ";
        $stmt = $this->conn->prepare($countSql);
        $stmt->execute([$kw, $kw, $kw]);
        $total = (int)$stmt->fetchColumn();

        $sql = "
            SELECT
                f.faq_id,
                f.question,
                f.answer,
                f.category,
                f.is_active,
                f.created_by_admin_id,
                f.created_at,
                f.updated_at,
                u.full_name AS created_by_name
            FROM faqs f
            LEFT JOIN admins a ON f.created_by_admin_id = a.admin_id
            LEFT JOIN users u ON a.admin_id = u.user_id
            WHERE f.question LIKE ? OR f.answer LIKE ? OR COALESCE(f.category, '') LIKE ?
            ORDER BY f.faq_id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$kw, $kw, $kw]);

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

    // Admin: find single FAQ
    public function find($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM faqs WHERE faq_id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Admin: create FAQ
    public function create($data)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO faqs (question, answer, category, is_active, created_by_admin_id)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['question'],
            $data['answer'],
            $data['category'] ?: null,
            (int)($data['is_active'] ?? 1),
            (int)($data['admin_id'] ?? 0) ?: null
        ]);

        return $this->find((int)$this->conn->lastInsertId());
    }

    // Admin: update FAQ
    public function update($id, $data)
    {
        $old = $this->find($id);
        if (!$old) {
            return false;
        }

        $stmt = $this->conn->prepare("
            UPDATE faqs
            SET question = ?, answer = ?, category = ?, is_active = ?
            WHERE faq_id = ?
        ");

        $stmt->execute([
            $data['question'],
            $data['answer'],
            $data['category'] ?: null,
            (int)($data['is_active'] ?? 1),
            (int)$id
        ]);

        return $this->find($id);
    }

    // Admin: delete FAQ
    public function delete($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM faqs WHERE faq_id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->rowCount() > 0;
    }
}
