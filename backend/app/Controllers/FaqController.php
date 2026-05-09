<?php

require_once __DIR__ . "/../models/Faq.php";

class FaqController
{
    private $model;

    public function __construct($conn)
    {
        $this->model = new Faq($conn);
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

    public function publicIndex()
    {
        try {
            $category = $_GET["category"] ?? "";
            $faqs = $this->model->getPublic($category);
            $this->json(true, "Lấy danh sách FAQ thành công", $faqs);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    public function publicCategories()
    {
        try {
            $categories = $this->model->getCategories();
            $this->json(true, "Lấy danh sách category FAQ thành công", $categories);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    // ========== ADMIN ==========

    public function adminIndex()
    {
        try {
            $q = $_GET["q"] ?? "";
            $page = $_GET["page"] ?? 1;
            $limit = $_GET["limit"] ?? 10;

            $data = $this->model->getAll($q, $page, $limit);
            $this->json(true, "Lấy danh sách FAQ thành công", $data);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    public function adminShow()
    {
        try {
            $id = $_GET["id"] ?? 0;
            $faq = $this->model->find((int)$id);

            if (!$faq) {
                return $this->json(false, "Không tìm thấy FAQ", null, 404);
            }

            $this->json(true, "Lấy chi tiết FAQ thành công", $faq);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    public function adminStore()
    {
        try {
            $data = $this->body();

            $question = trim($data["question"] ?? "");
            $answer = trim($data["answer"] ?? "");

            if ($question === "" || $answer === "") {
                return $this->json(false, "Vui lòng nhập câu hỏi và câu trả lời", null, 422);
            }

            if (strlen($question) > 255) {
                return $this->json(false, "Câu hỏi không được vượt quá 255 ký tự", null, 422);
            }

            $cleanData = [
                "question" => htmlspecialchars($question, ENT_QUOTES, "UTF-8"),
                "answer" => htmlspecialchars($answer, ENT_QUOTES, "UTF-8"),
                "category" => htmlspecialchars(trim($data["category"] ?? ""), ENT_QUOTES, "UTF-8"),
                "is_active" => $data["is_active"] ?? 1,
                "admin_id" => $data["admin_id"] ?? null
            ];

            $faq = $this->model->create($cleanData);
            $this->json(true, "Thêm FAQ thành công", $faq, 201);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    public function adminUpdate()
    {
        try {
            $id = $_GET["id"] ?? 0;
            $data = $this->body();

            $question = trim($data["question"] ?? "");
            $answer = trim($data["answer"] ?? "");

            if ($question === "" || $answer === "") {
                return $this->json(false, "Vui lòng nhập câu hỏi và câu trả lời", null, 422);
            }

            if (strlen($question) > 255) {
                return $this->json(false, "Câu hỏi không được vượt quá 255 ký tự", null, 422);
            }

            $cleanData = [
                "question" => htmlspecialchars($question, ENT_QUOTES, "UTF-8"),
                "answer" => htmlspecialchars($answer, ENT_QUOTES, "UTF-8"),
                "category" => htmlspecialchars(trim($data["category"] ?? ""), ENT_QUOTES, "UTF-8"),
                "is_active" => $data["is_active"] ?? 1
            ];

            $faq = $this->model->update((int)$id, $cleanData);

            if (!$faq) {
                return $this->json(false, "Không tìm thấy FAQ", null, 404);
            }

            $this->json(true, "Cập nhật FAQ thành công", $faq);
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
                $this->json(true, "Xóa FAQ thành công");
            } else {
                $this->json(false, "Không tìm thấy FAQ để xóa", null, 404);
            }
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }
}
