<?php

class Category
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Public: get all categories (tree structure)
    public function getAll()
    {
        $stmt = $this->conn->prepare("
            SELECT
                c.category_id,
                c.category_name,
                c.parent_category_id,
                pc.category_name AS parent_name
            FROM categories c
            LEFT JOIN categories pc ON c.parent_category_id = pc.category_id
            ORDER BY c.parent_category_id IS NULL DESC, c.parent_category_id, c.category_name
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Public: get category tree (nested)
    public function getTree()
    {
        $all = $this->getAll();
        $map = [];
        $tree = [];

        foreach ($all as &$cat) {
            $cat['children'] = [];
            $map[$cat['category_id']] = &$cat;
        }
        unset($cat);

        foreach ($all as &$cat) {
            if ($cat['parent_category_id'] && isset($map[$cat['parent_category_id']])) {
                $map[$cat['parent_category_id']]['children'][] = &$cat;
            } else {
                $tree[] = &$cat;
            }
        }
        unset($cat);

        return $tree;
    }

    // Public: get products by category with pagination
    public function getProductsByCategory($categoryId, $page = 1, $limit = 12)
    {
        $page = max(1, (int)$page);
        $limit = max(1, min(50, (int)$limit));
        $offset = ($page - 1) * $limit;

        $countSql = "
            SELECT COUNT(DISTINCT pv.version_id)
            FROM product_categories pc
            JOIN products p ON pc.product_id = p.product_id
            JOIN product_versions pv ON p.product_id = pv.product_id
            WHERE pc.category_id = ? AND pv.version_status = 'available'
        ";
        $stmt = $this->conn->prepare($countSql);
        $stmt->execute([(int)$categoryId]);
        $total = (int)$stmt->fetchColumn();

        $sql = "
            SELECT
                pv.version_id,
                pv.product_id,
                p.product_name,
                p.brand,
                pv.version_name,
                pv.format_type,
                pv.price,
                pv.stock_quantity,
                pv.image_url,
                pv.version_status
            FROM product_categories pc
            JOIN products p ON pc.product_id = p.product_id
            JOIN product_versions pv ON p.product_id = pv.product_id
            WHERE pc.category_id = ? AND pv.version_status = 'available'
            ORDER BY pv.version_id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([(int)$categoryId]);

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

    // Admin: find single category
    public function find($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM categories WHERE category_id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Admin: create category
    public function create($name, $parentId = null)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO categories (category_name, parent_category_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$name, $parentId ? (int)$parentId : null]);
        return $this->find((int)$this->conn->lastInsertId());
    }

    // Admin: update category
    public function update($id, $name, $parentId = null)
    {
        $stmt = $this->conn->prepare("
            UPDATE categories
            SET category_name = ?, parent_category_id = ?
            WHERE category_id = ?
        ");
        $stmt->execute([$name, $parentId ? (int)$parentId : null, (int)$id]);
        return $this->find($id);
    }

    // Admin: delete category
    public function delete($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->rowCount() > 0;
    }
}
