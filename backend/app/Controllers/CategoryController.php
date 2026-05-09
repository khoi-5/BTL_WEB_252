<?php

require_once __DIR__ . "/../models/Category.php";

class CategoryController
{
    private $model;

    public function __construct($conn)
    {
        $this->model = new Category($conn);
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

    private function body()
    {
        return json_decode(file_get_contents("php://input"), true) ?? [];
    }

    // ========== PUBLIC ==========

    // GET /api/categories — flat list
    public function publicIndex()
    {
        try {
            $data = $this->model->getAll();
            $this->json(true, "Lấy danh sách danh mục thành công", $data);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    // GET /api/categories/tree — nested tree
    public function publicTree()
    {
        try {
            $data = $this->model->getTree();
            $this->json(true, "Lấy cây danh mục thành công", $data);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    // GET /api/categories/products?id=X — products by category
    public function publicProducts()
    {
        try {
            $id = $_GET["id"] ?? 0;
            $page = $_GET["page"] ?? 1;
            $limit = $_GET["limit"] ?? 12;

            if (!$id) {
                return $this->json(false, "Thiếu category_id", null, 400);
            }

            $data = $this->model->getProductsByCategory((int)$id, $page, $limit);
            $this->json(true, "Lấy sản phẩm theo danh mục thành công", $data);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    // ========== ADMIN ==========

    public function adminStore()
    {
        try {
            $data = $this->body();
            $name = trim($data["category_name"] ?? "");
            $parentId = $data["parent_category_id"] ?? null;

            if ($name === "") {
                return $this->json(false, "Vui lòng nhập tên danh mục", null, 422);
            }

            if (strlen($name) > 100) {
                return $this->json(false, "Tên danh mục không được vượt quá 100 ký tự", null, 422);
            }

            $cleanName = htmlspecialchars($name, ENT_QUOTES, "UTF-8");
            $category = $this->model->create($cleanName, $parentId);
            $this->json(true, "Thêm danh mục thành công", $category, 201);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    public function adminUpdate()
    {
        try {
            $id = $_GET["id"] ?? 0;
            $data = $this->body();
            $name = trim($data["category_name"] ?? "");
            $parentId = $data["parent_category_id"] ?? null;

            if ($name === "") {
                return $this->json(false, "Vui lòng nhập tên danh mục", null, 422);
            }

            if (strlen($name) > 100) {
                return $this->json(false, "Tên danh mục không được vượt quá 100 ký tự", null, 422);
            }

            $cleanName = htmlspecialchars($name, ENT_QUOTES, "UTF-8");
            $category = $this->model->update((int)$id, $cleanName, $parentId);

            if (!$category) {
                return $this->json(false, "Không tìm thấy danh mục", null, 404);
            }

            $this->json(true, "Cập nhật danh mục thành công", $category);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    public function adminDelete()
    {
        try {
            $id = $_GET["id"] ?? 0;
            $result = $this->model->delete((int)$id);

            if ($result) {
                $this->json(true, "Xóa danh mục thành công");
            } else {
                $this->json(false, "Không tìm thấy danh mục để xóa", null, 404);
            }
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }
}
