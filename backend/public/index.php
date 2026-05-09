<?php

$allowedOrigins = [
    "http://localhost:5173",
    "http://localhost:5174",
    "http://localhost:5175",
    "http://localhost:5176",
    "http://localhost:5177",
    "http://localhost:5500",
];

$origin = $_SERVER["HTTP_ORIGIN"] ?? "";

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: " . $origin);
}

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Credentials: true");


if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

    // Serve Swagger UI at /docs
    if ($uri === "/docs" || $uri === "/swagger.html") {
        header("Content-Type: text/html; charset=UTF-8");
        require __DIR__ . "/swagger.html";
        exit;
    }

    if ($uri === "/swagger.yaml") {
        header("Content-Type: application/yaml; charset=UTF-8");
        echo file_get_contents(__DIR__ . "/swagger.yaml");
        exit;
    }

    // Allow serving static files (uploads)
    if (str_starts_with($uri, "/uploads/")) {
        $filePath = __DIR__ . $uri;

        if (is_file($filePath)) {
            return false;
        }
    }
}

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../app/controllers/AuthController.php";
require_once __DIR__ . "/../app/controllers/ContactController.php";
require_once __DIR__ . "/../app/controllers/CustomerController.php";
require_once __DIR__ . "/../app/controllers/AdminController.php";
require_once __DIR__ . "/../app/controllers/ProductController.php";
require_once __DIR__ . "/../app/controllers/OrderController.php";
require_once __DIR__ . "/../app/controllers/CartController.php";
require_once __DIR__ . "/../app/controllers/FaqController.php";
require_once __DIR__ . "/../app/controllers/SiteSettingController.php";
require_once __DIR__ . "/../app/controllers/CategoryController.php";
require_once __DIR__ . "/../app/controllers/SectionController.php";

$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$method = $_SERVER["REQUEST_METHOD"];

$authController = new AuthController($conn);
$contactController = new ContactController($conn);
$customerController = new CustomerController($conn);
$adminController = new AdminController($conn);
$productController = new ProductController($conn);
$orderController = new OrderController($conn);
$cartController = new CartController($conn);
$faqController = new FaqController($conn);
$siteSettingController = new SiteSettingController($conn);
$categoryController = new CategoryController($conn);
$sectionController = new SectionController($conn);

switch ($uri) {

    // ===================================================================
    // AUTH
    // ===================================================================

    case "/api/customer/login":
        if ($method === "POST") {
            $authController->loginCustomer();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/admin/login":
        if ($method === "POST") {
            $authController->loginAdmin();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/register":
        if ($method === "POST") {
            $authController->register();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/auth/check-status":
        if ($method === "GET") {
            $authController->checkStatus();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    // ===================================================================
    // ADMIN PROFILE
    // ===================================================================

    case "/api/admin/info":
        if ($method === "GET") {
            $adminController->getInfo();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/admin/update":
        if ($method === "PUT") {
            $adminController->updateInfo();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/admin/change-password":
        if ($method === "PUT") {
            $adminController->changePassword();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    // ===================================================================
    // CUSTOMER PROFILE
    // ===================================================================

    case "/api/customer/info":
        if ($method === "GET") {
            $customerController->getInfo();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/customer/update":
        if ($method === "PUT") {
            $customerController->updateInfo();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/customer/change-password":
        if ($method === "PUT") {
            $customerController->changePassword();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/customer/upload-avatar":
        if ($method === "POST") {
            $customerController->uploadAvatar();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    // ===================================================================
    // ADMIN: CUSTOMER MANAGEMENT
    // ===================================================================

    case "/api/admin/customers":
        if ($method === "GET") {
            $customerController->getAllCustomersForAdmin();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/admin/customers/status":
        if ($method === "PUT") {
            $customerController->updateCustomerStatus();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/admin/customers/reset-password":
        if ($method === "PUT") {
            $customerController->resetPassword();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    // ===================================================================
    // PRODUCTS
    // ===================================================================

    case "/api/products":
        if ($method === "GET") {
            $productController->index();
        } elseif ($method === "POST") {
            $productController->store();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/products/detail":
        if ($method === "GET") {
            $id = $_GET["id"] ?? 0;
            $productController->show($id);
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/products/update":
        if ($method === "PUT") {
            $id = $_GET["id"] ?? 0;
            $productController->update($id);
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/products/delete":
        if ($method === "DELETE") {
            $id = $_GET["id"] ?? 0;
            $productController->destroy($id);
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/products/upload-image":
        if ($method === "POST") {
            $productController->uploadImage();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    // ===================================================================
    // CATEGORIES (PUBLIC)
    // ===================================================================

    case "/api/categories":
        if ($method === "GET") {
            $categoryController->publicIndex();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/categories/tree":
        if ($method === "GET") {
            $categoryController->publicTree();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/categories/products":
        if ($method === "GET") {
            $categoryController->publicProducts();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    // CATEGORIES (ADMIN)
    case "/api/admin/categories":
        if ($method === "POST") {
            $categoryController->adminStore();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/admin/categories/update":
        if ($method === "PUT") {
            $categoryController->adminUpdate();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/admin/categories/delete":
        if ($method === "DELETE") {
            $categoryController->adminDelete();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    // ===================================================================
    // ORDERS
    // ===================================================================

    case "/api/orders":
        if ($method === "GET") {
            $orderController->index();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/orders/detail":
        if ($method === "GET") {
            $id = $_GET["id"] ?? 0;
            $orderController->show($id);
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/orders/move-to-shipping":
        if ($method === "PUT") {
            $id = $_GET["id"] ?? 0;
            $orderController->moveToShipping($id);
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/orders/customer-confirm-payment":
        if ($method === "PUT") {
            $id = $_GET["id"] ?? 0;
            $orderController->customerConfirmPayment($id);
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/orders/customer-cancel":
        if ($method === "PUT") {
            $id = $_GET["id"] ?? 0;
            $orderController->customerCancel($id);
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/orders/customer-confirm-delivered":
        if ($method === "PUT") {
            $id = $_GET["id"] ?? 0;
            $orderController->customerConfirmDelivered($id);
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/orders/create-from-cart":
        if ($method === "POST") {
            $orderController->createFromCart();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/orders/customer":
        if ($method === "GET") {
            $customerId = $_GET["customer_id"] ?? null;
            $orderController->getCustomerOrders($customerId);
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    // ===================================================================
    // CART
    // ===================================================================

    case "/api/cart":
        if ($method === "GET") {
            $cartController->list();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/cart/add":
        if ($method === "POST") {
            $cartController->add();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/cart/update":
        if ($method === "PUT") {
            $cartController->update();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/cart/remove":
        if ($method === "DELETE") {
            $cartController->remove();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/cart/clear":
        if ($method === "DELETE") {
            $cartController->clear();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    // ===================================================================
    // CONTACTS (PUBLIC)
    // ===================================================================

    case "/api/contact":
        if ($method === "POST") {
            $contactController->createContact();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    // CONTACTS (ADMIN)
    case "/api/admin/contacts":
        if ($method === "GET") {
            $contactController->adminIndex();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/admin/contacts/detail":
        if ($method === "GET") {
            $contactController->adminShow();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/admin/contacts/status":
        if ($method === "PUT") {
            $contactController->adminUpdateStatus();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/admin/contacts/delete":
        if ($method === "DELETE") {
            $contactController->adminDelete();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    // ===================================================================
    // FAQS (PUBLIC)
    // ===================================================================

    case "/api/faqs":
        if ($method === "GET") {
            $faqController->publicIndex();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/faqs/categories":
        if ($method === "GET") {
            $faqController->publicCategories();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    // FAQS (ADMIN)
    case "/api/admin/faqs":
        if ($method === "GET") {
            $faqController->adminIndex();
        } elseif ($method === "POST") {
            $faqController->adminStore();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/admin/faqs/detail":
        if ($method === "GET") {
            $faqController->adminShow();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/admin/faqs/update":
        if ($method === "PUT") {
            $faqController->adminUpdate();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/admin/faqs/delete":
        if ($method === "DELETE") {
            $faqController->adminDelete();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    // ===================================================================
    // SITE SETTINGS (PUBLIC)
    // ===================================================================

    case "/api/settings":
        if ($method === "GET") {
            $siteSettingController->publicIndex();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    // SITE SETTINGS (ADMIN)
    case "/api/admin/settings":
        if ($method === "GET") {
            $siteSettingController->adminIndex();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/admin/settings/grouped":
        if ($method === "GET") {
            $siteSettingController->adminGrouped();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/admin/settings/update":
        if ($method === "PUT") {
            $siteSettingController->adminUpdate();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    // ===================================================================
    // PRODUCT SECTIONS (PUBLIC)
    // ===================================================================

    case "/api/sections":
        if ($method === "GET") {
            $sectionController->publicSections();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    // PRODUCT SECTIONS (ADMIN)
    case "/api/admin/sections":
        if ($method === "GET") {
            $sectionController->adminSections();
        } elseif ($method === "POST") {
            $sectionController->adminSectionStore();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/admin/sections/detail":
        if ($method === "GET") {
            $sectionController->adminSectionDetail();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/admin/sections/update":
        if ($method === "POST") {
            $sectionController->adminSectionUpdate();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/admin/sections/delete":
        if ($method === "DELETE") {
            $sectionController->adminSectionDelete();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    // ===================================================================
    // FLASH SALES (PUBLIC)
    // ===================================================================

    case "/api/flash-sales":
        if ($method === "GET") {
            $sectionController->publicFlashSales();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    // FLASH SALES (ADMIN)
    case "/api/admin/flash-sales":
        if ($method === "GET") {
            $sectionController->adminFlashSales();
        } elseif ($method === "POST") {
            $sectionController->adminFlashSaleStore();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/admin/flash-sales/detail":
        if ($method === "GET") {
            $sectionController->adminFlashSaleDetail();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/admin/flash-sales/update":
        if ($method === "POST") {
            $sectionController->adminFlashSaleUpdate();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    case "/api/admin/flash-sales/delete":
        if ($method === "DELETE") {
            $sectionController->adminFlashSaleDelete();
        } else {
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method không hợp lệ"]);
        }
        break;

    // ===================================================================
    // DEFAULT 404
    // ===================================================================

    default:
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "API không tồn tại"
        ]);
        break;
}