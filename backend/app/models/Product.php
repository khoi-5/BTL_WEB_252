<?php

class Product
{
    private $conn;
    private $defaultImage = "uploads/products/no_img.jpg";

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    private function publicPath($relativePath)
    {
        return realpath(__DIR__ . "/../../public") . "/" . ltrim($relativePath, "/");
    }

    private function normalizeImageUrl($imageUrl)
    {
        $imageUrl = trim($imageUrl ?? "");

        if ($imageUrl === "") {
            return $this->defaultImage;
        }

        $path = $this->publicPath($imageUrl);

        error_log($path);

        if (file_exists($path)) {
            return $imageUrl;
        }

        return $this->defaultImage;
    }

    private function deleteImageFile($imageUrl)
    {
        $imageUrl = trim($imageUrl ?? "");

        if ($imageUrl === "" || $imageUrl === $this->defaultImage) {
            return;
        }

        $path = $this->publicPath($imageUrl);

        if (is_file($path)) {
            unlink($path);
        }
    }

    private function normalizeItem($item)
    {
        if (!$item) {
            return false;
        }

        $item["image_url"] = $this->normalizeImageUrl($item["image_url"] ?? "");

        return $item;
    }

    private function findRaw($versionId)
    {
        $stmt = $this->conn->prepare("
            SELECT
                pv.version_id,
                pv.product_id,
                pv.sku,
                pv.version_name,
                pv.format_type,
                pv.language,
                pv.cover_type,
                pv.edition,
                pv.price,
                pv.stock_quantity,
                pv.image_url,
                pv.version_status,
                p.product_name,
                p.brand,
                p.description
            FROM product_versions pv
            JOIN products p ON pv.product_id = p.product_id
            WHERE pv.version_id = ?
        ");

        $stmt->execute([(int)$versionId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll($keyword = '', $page = 1, $limit = 10)
    {
        $page = max(1, (int)$page);
        $limit = max(1, min(50, (int)$limit));
        $offset = ($page - 1) * $limit;

        $kw = "%" . trim($keyword) . "%";

        $countSql = "
            SELECT COUNT(*)
            FROM product_versions pv
            JOIN products p ON pv.product_id = p.product_id
            WHERE p.product_name LIKE ?
               OR COALESCE(p.brand, '') LIKE ?
               OR COALESCE(p.description, '') LIKE ?
               OR pv.sku LIKE ?
               OR pv.version_name LIKE ?
        ";

        $stmt = $this->conn->prepare($countSql);
        $stmt->execute([$kw, $kw, $kw, $kw, $kw]);
        $total = (int)$stmt->fetchColumn();

        $sql = "
            SELECT
                pv.version_id,
                pv.product_id,
                p.product_name,
                p.brand,
                p.description,
                pv.sku,
                pv.version_name,
                pv.format_type,
                pv.language,
                pv.cover_type,
                pv.edition,
                pv.price,
                pv.stock_quantity,
                pv.image_url,
                pv.version_status
            FROM product_versions pv
            JOIN products p ON pv.product_id = p.product_id
            WHERE p.product_name LIKE ?
               OR COALESCE(p.brand, '') LIKE ?
               OR COALESCE(p.description, '') LIKE ?
               OR pv.sku LIKE ?
               OR pv.version_name LIKE ?
            ORDER BY pv.version_id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$kw, $kw, $kw, $kw, $kw]);

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as &$item) {
            $item = $this->normalizeItem($item);
        }

        return [
            "items" => $items,
            "pagination" => [
                "current_page" => $page,
                "total_pages" => max(1, (int)ceil($total / $limit)),
                "total_items" => $total,
                "limit" => $limit
            ]
        ];
    }

    public function find($versionId)
    {
        return $this->normalizeItem($this->findRaw($versionId));
    }

    public function create($data)
    {
        $this->conn->beginTransaction();

        try {
            $stmt = $this->conn->prepare("
                INSERT INTO products(product_name, brand, description)
                VALUES (?, ?, ?)
            ");

            $stmt->execute([
                $data["product_name"],
                $data["brand"],
                $data["description"]
            ]);

            $productId = $this->conn->lastInsertId();

            $imageUrl = trim($data["image_url"] ?? "");

            if ($imageUrl === "") {
                $imageUrl = null;
            }

            $stmt = $this->conn->prepare("
                INSERT INTO product_versions(
                    product_id, sku, version_name,
                    format_type, language, cover_type,
                    edition, price, stock_quantity,
                    image_url, version_status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $productId,
                $data["sku"],
                $data["version_name"],
                $data["format_type"],
                $data["language"],
                $data["cover_type"],
                $data["edition"],
                $data["price"],
                $data["stock_quantity"],
                $imageUrl,
                $data["version_status"]
            ]);

            $versionId = $this->conn->lastInsertId();

            $this->conn->commit();

            return $this->find($versionId);
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function update($versionId, $data)
    {
        $old = $this->findRaw($versionId);

        if (!$old) {
            return false;
        }

        $productId = (int)$old["product_id"];

        $this->conn->beginTransaction();

        try {
            $stmt = $this->conn->prepare("
                UPDATE products
                SET product_name = ?, brand = ?, description = ?
                WHERE product_id = ?
            ");

            $stmt->execute([
                $data["product_name"],
                $data["brand"],
                $data["description"],
                $productId
            ]);

            $oldImage = trim($old["image_url"] ?? "");
            $newImage = trim($data["image_url"] ?? "");

            // Nếu admin không upload ảnh mới thì giữ ảnh cũ
            if ($newImage === "") {
                $newImage = $oldImage;
            }

            // Nếu có ảnh mới khác ảnh cũ thì xóa ảnh cũ
            if ($newImage !== $oldImage) {
                $this->deleteImageFile($oldImage);
            }

            if ($newImage === "") {
                $newImage = null;
            }

            $stmt = $this->conn->prepare("
                UPDATE product_versions
                SET sku = ?,
                    version_name = ?,
                    format_type = ?,
                    language = ?,
                    cover_type = ?,
                    edition = ?,
                    price = ?,
                    stock_quantity = ?,
                    image_url = ?,
                    version_status = ?
                WHERE version_id = ?
            ");

            $stmt->execute([
                $data["sku"],
                $data["version_name"],
                $data["format_type"],
                $data["language"],
                $data["cover_type"],
                $data["edition"],
                $data["price"],
                $data["stock_quantity"],
                $newImage,
                $data["version_status"],
                (int)$versionId
            ]);

            $this->conn->commit();

            return $this->find($versionId);
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function delete($versionId)
    {
        $old = $this->findRaw($versionId);

        if ($old) {
            $this->deleteImageFile($old["image_url"] ?? "");
        }

        $stmt = $this->conn->prepare("
            DELETE FROM product_versions
            WHERE version_id = ?
        ");

        return $stmt->execute([(int)$versionId]);
    }
}