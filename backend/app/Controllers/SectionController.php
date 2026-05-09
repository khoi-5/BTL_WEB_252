<?php

require_once __DIR__ . "/../models/Section.php";

class SectionController
{
    private $model;
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->model = new Section($conn);
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

    // ==================== PRODUCT SECTIONS ====================

    // GET /api/sections — public: active sections with their products
    public function publicSections()
    {
        try {
            $sections = $this->model->getPublicSectionsWithProducts();
            $this->json(true, "Lấy danh sách section thành công", $sections);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    // GET /api/admin/sections — admin: all sections
    public function adminSections()
    {
        try {
            $sections = $this->model->getAllSections();
            $this->json(true, "Lấy danh sách section thành công", $sections);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    // GET /api/admin/sections/detail?id=X
    public function adminSectionDetail()
    {
        try {
            $id = $_GET["id"] ?? 0;
            $section = $this->model->getSectionById((int)$id);

            if (!$section) {
                return $this->json(false, "Không tìm thấy section", null, 404);
            }

            $this->json(true, "Lấy chi tiết section thành công", $section);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    // POST /api/admin/sections — create (multipart: name, description, status, image file)
    public function adminSectionStore()
    {
        try {
            $name = trim($_POST["name"] ?? "");
            $description = trim($_POST["description"] ?? "");
            $status = isset($_POST["status"]) ? (int)$_POST["status"] : 1;

            if ($name === "") {
                return $this->json(false, "Vui lòng nhập tên section", null, 422);
            }

            // Handle image upload
            $imageName = $this->handleImageUpload("image", "sections");
            if ($imageName === false) {
                return $this->json(false, "Lỗi upload hình ảnh", null, 400);
            }

            $sectionId = $this->model->addSection(
                htmlspecialchars($name, ENT_QUOTES, "UTF-8"),
                htmlspecialchars($description, ENT_QUOTES, "UTF-8"),
                $imageName ?: "default-section.jpg",
                $status
            );

            $section = $this->model->getSectionById($sectionId);
            $this->json(true, "Thêm section thành công", $section, 201);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    // POST /api/admin/sections/update?id=X (multipart for image)
    public function adminSectionUpdate()
    {
        try {
            $id = $_GET["id"] ?? 0;
            $name = trim($_POST["name"] ?? "");
            $description = trim($_POST["description"] ?? "");
            $status = isset($_POST["status"]) ? (int)$_POST["status"] : 1;

            if ($name === "") {
                return $this->json(false, "Vui lòng nhập tên section", null, 422);
            }

            $old = $this->model->getSectionById((int)$id);
            if (!$old) {
                return $this->json(false, "Không tìm thấy section", null, 404);
            }

            // Handle image upload (keep old if not uploaded)
            $imageName = $this->handleImageUpload("image", "sections");
            if ($imageName === false) {
                $imageName = $old["section_image"]; // keep old image
            }

            $result = $this->model->updateSection(
                (int)$id,
                htmlspecialchars($name, ENT_QUOTES, "UTF-8"),
                htmlspecialchars($description, ENT_QUOTES, "UTF-8"),
                $imageName,
                $status
            );

            if ($result) {
                $updated = $this->model->getSectionById((int)$id);
                $this->json(true, "Cập nhật section thành công", $updated);
            } else {
                $this->json(false, "Không thể cập nhật section", null, 500);
            }
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    // DELETE /api/admin/sections/delete?id=X
    public function adminSectionDelete()
    {
        try {
            $id = $_GET["id"] ?? 0;
            $result = $this->model->deleteSection((int)$id);

            if ($result) {
                $this->json(true, "Xóa section thành công");
            } else {
                $this->json(false, "Không tìm thấy section để xóa", null, 404);
            }
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    // ==================== FLASH SALES ====================

    // GET /api/flash-sales — public: active flash sales
    public function publicFlashSales()
    {
        try {
            $conn = $this->conn;

            $stmtQ = $conn->prepare("
                SELECT fs.*,
                    CASE 
                        WHEN NOW() BETWEEN fs.flash_sale_start_time AND fs.flash_sale_end_time THEN 'active'
                        WHEN NOW() < fs.flash_sale_start_time THEN 'upcoming'
                        ELSE 'ended'
                    END AS sale_status
                FROM flash_sales fs
                WHERE fs.flash_sale_is_active = 1
                ORDER BY fs.flash_sale_start_time ASC
            ");
            $stmtQ->execute();
            $sales = $stmtQ->fetchAll(\PDO::FETCH_ASSOC);

            // For each sale, fetch its products
            foreach ($sales as &$sale) {
                $stmtP = $conn->prepare("
                    SELECT 
                        fsp.version_id,
                        fsp.sale_price,
                        fsp.stock_allocated,
                        fsp.stock_sold,
                        pv.version_name,
                        pv.price AS original_price,
                        pv.image_url,
                        p.product_name,
                        p.brand
                    FROM flash_sale_products fsp
                    JOIN product_versions pv ON fsp.version_id = pv.version_id
                    JOIN products p ON pv.product_id = p.product_id
                    WHERE fsp.flash_sale_id = ?
                ");
                $stmtP->execute([(int)$sale["flash_sale_id"]]);
                $sale["products"] = $stmtP->fetchAll(\PDO::FETCH_ASSOC);
            }
            unset($sale);

            $this->json(true, "Lấy danh sách flash sale thành công", $sales);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    // GET /api/admin/flash-sales — admin: all flash sales
    public function adminFlashSales()
    {
        try {
            $conn = $this->conn;
            $stmt = $conn->prepare("
                SELECT fs.*,
                    CASE 
                        WHEN NOW() BETWEEN fs.flash_sale_start_time AND fs.flash_sale_end_time AND fs.flash_sale_is_active = 1 THEN 'active'
                        WHEN NOW() < fs.flash_sale_start_time THEN 'upcoming'
                        ELSE 'ended'
                    END AS sale_status
                FROM flash_sales fs
                ORDER BY fs.flash_sale_id DESC
            ");
            $stmt->execute();
            $this->json(true, "Lấy danh sách flash sale thành công", $stmt->fetchAll(\PDO::FETCH_ASSOC));
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    // GET /api/admin/flash-sales/detail?id=X
    public function adminFlashSaleDetail()
    {
        try {
            $id = $_GET["id"] ?? 0;
            $sale = $this->model->getFlashSaleById((int)$id);

            if (!$sale) {
                return $this->json(false, "Không tìm thấy flash sale", null, 404);
            }

            // Fetch products for this sale
            $conn = $this->conn;
            $stmt = $conn->prepare("
                SELECT 
                    fsp.*,
                    pv.version_name,
                    pv.price AS original_price,
                    pv.image_url,
                    p.product_name
                FROM flash_sale_products fsp
                JOIN product_versions pv ON fsp.version_id = pv.version_id
                JOIN products p ON pv.product_id = p.product_id
                WHERE fsp.flash_sale_id = ?
            ");
            $stmt->execute([(int)$id]);
            $sale["products"] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->json(true, "Lấy chi tiết flash sale thành công", $sale);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    // POST /api/admin/flash-sales (multipart: name, description, start_time, end_time, is_active, image)
    public function adminFlashSaleStore()
    {
        try {
            $name = trim($_POST["name"] ?? "");
            $desc = trim($_POST["description"] ?? "");
            $startTime = trim($_POST["start_time"] ?? "");
            $endTime = trim($_POST["end_time"] ?? "");
            $isActive = isset($_POST["is_active"]) ? (int)$_POST["is_active"] : 1;

            if ($name === "" || $startTime === "" || $endTime === "") {
                return $this->json(false, "Vui lòng nhập đầy đủ thông tin", null, 422);
            }

            $imageName = $this->handleImageUpload("image", "flash-sales");
            if ($imageName === false) {
                $imageName = "default-flash-sale.jpg";
            }

            $saleId = $this->model->addFlashSale(
                htmlspecialchars($name, ENT_QUOTES, "UTF-8"),
                htmlspecialchars($desc, ENT_QUOTES, "UTF-8"),
                $imageName,
                $startTime,
                $endTime,
                $isActive
            );

            $sale = $this->model->getFlashSaleById($saleId);
            $this->json(true, "Thêm flash sale thành công", $sale, 201);
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    // POST /api/admin/flash-sales/update?id=X (multipart)
    public function adminFlashSaleUpdate()
    {
        try {
            $id = $_GET["id"] ?? 0;
            $name = trim($_POST["name"] ?? "");
            $desc = trim($_POST["description"] ?? "");
            $startTime = trim($_POST["start_time"] ?? "");
            $endTime = trim($_POST["end_time"] ?? "");
            $isActive = isset($_POST["is_active"]) ? (int)$_POST["is_active"] : 1;

            if ($name === "" || $startTime === "" || $endTime === "") {
                return $this->json(false, "Vui lòng nhập đầy đủ thông tin", null, 422);
            }

            $old = $this->model->getFlashSaleById((int)$id);
            if (!$old) {
                return $this->json(false, "Không tìm thấy flash sale", null, 404);
            }

            $imageName = $this->handleImageUpload("image", "flash-sales");
            if ($imageName === false) {
                $imageName = $old["flash_sale_image"];
            }

            $result = $this->model->updateFlashSale(
                (int)$id,
                htmlspecialchars($name, ENT_QUOTES, "UTF-8"),
                htmlspecialchars($desc, ENT_QUOTES, "UTF-8"),
                $imageName,
                $startTime,
                $endTime,
                $isActive
            );

            if ($result) {
                $updated = $this->model->getFlashSaleById((int)$id);
                $this->json(true, "Cập nhật flash sale thành công", $updated);
            } else {
                $this->json(false, "Không thể cập nhật flash sale", null, 500);
            }
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    // DELETE /api/admin/flash-sales/delete?id=X
    public function adminFlashSaleDelete()
    {
        try {
            $id = $_GET["id"] ?? 0;
            $result = $this->model->deleteFlashSale((int)$id);

            if ($result) {
                $this->json(true, "Xóa flash sale thành công");
            } else {
                $this->json(false, "Không tìm thấy flash sale để xóa", null, 404);
            }
        } catch (\Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    // ==================== HELPERS ====================

    /**
     * Handle file upload and return filename, or false if no file uploaded.
     */
    private function handleImageUpload($fieldName, $subDir)
    {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]["error"] !== UPLOAD_ERR_OK) {
            return false;
        }

        $file = $_FILES[$fieldName];
        $allowedExt = ["jpg", "jpeg", "png", "webp"];
        $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt, true)) {
            return false;
        }

        $uploadDir = __DIR__ . "/../../public/uploads/{$subDir}/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = $subDir . "_" . time() . "_" . rand(1000, 9999) . "." . $ext;
        $targetPath = $uploadDir . $fileName;

        if (!move_uploaded_file($file["tmp_name"], $targetPath)) {
            return false;
        }

        return "uploads/{$subDir}/" . $fileName;
    }
}
