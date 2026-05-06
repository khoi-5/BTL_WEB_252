<?php
require_once __DIR__ . '/../models/Product.php';

class ProductController
{
    private $model;
    public function __construct($conn) { $this->model = new Product($conn); }

    private function json($success, $message, $data = null, $code = 200)
    {
        http_response_code($code);
        echo json_encode(['success' => $success, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
    }

    private function body()
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    private function normalize($data)
    {
        return [
            'product_name' => trim($data['product_name'] ?? ''),
            'brand' => trim($data['brand'] ?? ''),
            'description' => trim($data['description'] ?? ''),
            'sku' => trim($data['sku'] ?? ''),
            'version_name' => trim($data['version_name'] ?? ''),
            'format_type' => $data['format_type'] ?? 'paperback',
            'language' => trim($data['language'] ?? 'Vietnamese'),
            'cover_type' => trim($data['cover_type'] ?? ''),
            'edition' => trim($data['edition'] ?? ''),
            'price' => (float)($data['price'] ?? 0),
            'stock_quantity' => (int)($data['stock_quantity'] ?? 0),
            'image_url' => isset($data['image_url']) ? trim($data['image_url']) : null,
            'version_status' => $data['version_status'] ?? 'available'
        ];
    }

    private function validate($d)
    {
        if ($d['product_name'] === '' || $d['sku'] === '' || $d['version_name'] === '') return 'Vui lòng nhập tên sản phẩm, SKU và tên phiên bản';
        if (!in_array($d['format_type'], ['paperback', 'hardcover', 'ebook', 'special_edition'], true)) return 'Định dạng sách không hợp lệ';
        if (!in_array($d['version_status'], ['available', 'out_of_stock', 'hidden'], true)) return 'Trạng thái không hợp lệ';
        if ($d['price'] < 0 || $d['stock_quantity'] < 0) return 'Giá và tồn kho không được âm';
        return null;
    }

    public function index()
    {
        try {
            $q = $_GET['q'] ?? '';
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;
            $this->json(true, 'Lấy danh sách sản phẩm thành công', $this->model->getAll($q, $page, $limit));
        } catch (Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    public function show($id)
    {
        $item = $this->model->find((int)$id);
        if (!$item) return $this->json(false, 'Không tìm thấy sản phẩm', null, 404);
        $this->json(true, 'Lấy sản phẩm thành công', $item);
    }

    public function store()
    {
        try {
            $data = $this->normalize($this->body());
            if ($err = $this->validate($data)) return $this->json(false, $err, null, 422);
            $this->json(true, 'Thêm sản phẩm thành công', $this->model->create($data), 201);
        } catch (Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    public function update($id)
    {
        try {
            $data = $this->normalize($this->body());
            if ($err = $this->validate($data)) return $this->json(false, $err, null, 422);
            $item = $this->model->update((int)$id, $data);
            if (!$item) return $this->json(false, 'Không tìm thấy sản phẩm', null, 404);
            $this->json(true, 'Cập nhật sản phẩm thành công', $item);
        } catch (Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->model->delete((int)$id);
            $this->json(true, 'Xóa sản phẩm thành công');
        } catch (Throwable $e) {
            $this->json(false, $e->getMessage(), null, 500);
        }
    }

   public function uploadImage()
    {
        try {
            if (!isset($_FILES["image"])) {
                return $this->json(false, "Chưa chọn ảnh", null, 400);
            }

            $file = $_FILES["image"];

            if ($file["error"] !== UPLOAD_ERR_OK) {
                return $this->json(false, "Upload ảnh thất bại", null, 400);
            }

            $allowedExt = ["jpg", "jpeg", "png", "webp"];
            $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExt, true)) {
                return $this->json(false, "Chỉ cho phép JPG, PNG, WEBP", null, 422);
            }

            $uploadDir = __DIR__ . "/../../public/uploads/products/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = "product_" . time() . "_" . rand(1000,9999) . "." . $ext;
            $targetPath = $uploadDir . $fileName;

            if (!move_uploaded_file($file["tmp_name"], $targetPath)) {
                return $this->json(false, "Không thể lưu ảnh", null, 500);
            }

            return $this->json(true, "Upload ảnh thành công", [
                "image_url" => "uploads/products/" . $fileName
            ]);

        } catch (Throwable $e) {
            return $this->json(false, $e->getMessage(), null, 500);
        }
    }

    
}
