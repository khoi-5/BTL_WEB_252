USE web;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS order_status_history;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS carts;
DROP TABLE IF EXISTS contacts;
DROP TABLE IF EXISTS faqs;
DROP TABLE IF EXISTS product_versions;
DROP TABLE IF EXISTS product_categories;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- 1. USERS
-- thông tin chung đăng nhập
-- =========================================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CHECK (phone REGEXP '^0[0-9]{9}$')
);

-- =========================================================
-- 2. CUSTOMERS
-- 1 = hoạt động, 0 = bị ban
-- =========================================================
CREATE TABLE customers (
    customer_id INT PRIMARY KEY,
    shipping_address VARCHAR(255) NOT NULL,
    receiver_name VARCHAR(100),
    receiver_phone VARCHAR(20),
    customer_status TINYINT NOT NULL DEFAULT 1,
    FOREIGN KEY (customer_id) REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- =========================================================
-- 3. ADMINS
-- is_super_admin = 1 thì có quyền tạo/ban admin khác và ban customer
-- =========================================================
CREATE TABLE admins (
    admin_id INT PRIMARY KEY,
    salary DECIMAL(12,2) NOT NULL DEFAULT 0,
    is_super_admin TINYINT NOT NULL DEFAULT 0,
    FOREIGN KEY (admin_id) REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- =========================================================
-- 4. CATEGORIES
-- category cha - con
-- =========================================================
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    parent_category_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_category_name UNIQUE (category_name),
    FOREIGN KEY (parent_category_id) REFERENCES categories(category_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

-- =========================================================
-- 5. PRODUCTS
-- vẫn dùng tên product để dễ mở rộng ngoài sách
-- =========================================================
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(200) NOT NULL,
    brand VARCHAR(150),
    description TEXT,
    created_by_admin_id INT NULL,
    updated_by_admin_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by_admin_id) REFERENCES admins(admin_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    FOREIGN KEY (updated_by_admin_id) REFERENCES admins(admin_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

-- =========================================================
-- 6. PRODUCT_CATEGORIES
-- M-N giữa product và category
-- =========================================================
CREATE TABLE product_categories (
    product_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (product_id, category_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- =========================================================
-- 7. PRODUCT_VERSIONS
-- tổng cộng sẽ insert 30 version mẫu
-- =========================================================
CREATE TABLE product_versions (
    version_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    sku VARCHAR(50) NOT NULL UNIQUE,
    version_name VARCHAR(150) NOT NULL,
    format_type ENUM('paperback', 'hardcover', 'ebook', 'special_edition') NOT NULL,
    language VARCHAR(50) DEFAULT 'Vietnamese',
    cover_type VARCHAR(50),
    edition VARCHAR(100),
    price DECIMAL(12,2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    image_url VARCHAR(255),
    version_status ENUM('available', 'out_of_stock', 'hidden') DEFAULT 'available',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- =========================================================
-- 8. CARTS
-- mỗi customer có đúng 1 cart
-- =========================================================
CREATE TABLE carts (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- =========================================================
-- 9. CART_ITEMS
-- =========================================================
CREATE TABLE cart_items (
    cart_item_id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    version_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES carts(cart_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (version_id) REFERENCES product_versions(version_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT uq_cart_version UNIQUE (cart_id, version_id),
    CONSTRAINT chk_cart_quantity CHECK (quantity > 0)
);

-- =========================================================
-- 10. ORDERS
-- order thuộc customer, admin chỉ xử lý
-- =========================================================
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    order_status ENUM('pending', 'confirmed', 'shipping', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address VARCHAR(255) NOT NULL,
    receiver_name VARCHAR(100) NOT NULL,
    receiver_phone VARCHAR(20) NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    processed_by_admin_id INT NULL,
    note VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (processed_by_admin_id) REFERENCES admins(admin_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

-- =========================================================
-- 11. ORDER_ITEMS
-- snapshot tại thời điểm mua
-- =========================================================
CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    version_id INT NOT NULL,
    product_name_snapshot VARCHAR(200) NOT NULL,
    version_name_snapshot VARCHAR(150),
    unit_price DECIMAL(12,2) NOT NULL,
    quantity INT NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (version_id) REFERENCES product_versions(version_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT chk_order_quantity CHECK (quantity > 0),
    CONSTRAINT chk_order_subtotal CHECK (subtotal >= 0)
);

-- =========================================================
-- 12. PAYMENTS
-- 1 order - 1 payment
-- =========================================================
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL UNIQUE,
    payment_method ENUM('cod', 'bank_transfer', 'momo', 'vnpay', 'credit_card') NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    amount DECIMAL(12,2) NOT NULL,
    transaction_code VARCHAR(100),
    paid_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT chk_payment_amount CHECK (amount >= 0)
);

-- =========================================================
-- 13. ORDER_STATUS_HISTORY
-- =========================================================
CREATE TABLE order_status_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    old_status ENUM('pending', 'confirmed', 'shipping', 'delivered', 'cancelled') NULL,
    new_status ENUM('pending', 'confirmed', 'shipping', 'delivered', 'cancelled') NOT NULL,
    changed_by_admin_id INT NULL,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    note VARCHAR(255),
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (changed_by_admin_id) REFERENCES admins(admin_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

-- =========================================================
-- 14. CONTACTS
-- người ngoài hệ thống liên hệ
-- =========================================================
CREATE TABLE contacts (
    contact_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(150) NULL,
    message TEXT NOT NULL,
    contact_status ENUM('new', 'in_progress', 'replied', 'closed') DEFAULT 'new',
    handled_by_admin_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (handled_by_admin_id) REFERENCES admins(admin_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

-- =========================================================
-- 15. FAQS
-- =========================================================
CREATE TABLE faqs (
    faq_id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(255) NOT NULL,
    answer TEXT NOT NULL,
    category VARCHAR(100),
    is_active TINYINT NOT NULL DEFAULT 1,
    created_by_admin_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by_admin_id) REFERENCES admins(admin_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

-- =========================================================
-- INSERT SAMPLE DATA
-- =========================================================

-- =========================
-- USERS
-- =========================
INSERT INTO users (full_name, email, password_hash, phone) VALUES
-- password: admin123
('Nguyen Van An', 'admin1@store.com',
'$2y$12$8T11Vyi31Jgxhw3fGWDzIuo3f4thxSpf0QHGyj2nNcdueJ6eBEVuy',
'0901000001'),

-- password: admin234
('Tran Thi Kieu Nga', 'admin2@store.com',
'$2y$12$bc4YwfMZrFGOkG1WRWkXDOTItP/t2Ls/mMjpWMm.9GhjpvTQX2iCO',
'0901000002'),

-- password: admin345
('Le Hoang Minh', 'admin3@store.com',
'$2y$12$5pwuVk4vGX2IWkiE/8PLa.UisW.L7B0tz1pLq6S5rdqYCW3H2KcyW',
'0901000003'),

-- password: customer123
('Le Minh Quan', 'customer1@gmail.com',
'$2y$12$yDdb7q4WXP8eZzJonk0yue9guPVu6DwdxYGQAmaTX.Fkx/ySacFii',
'0912000001'),

-- password: customer234
('Pham Ngoc Lan', 'customer2@gmail.com',
'$2y$12$g6Sj70SdWZdTPxu3VEEDAOe1hMUKcKce7OmQhlZl.ALYo7ERdgfG2',
'0912000002'),

-- password: customer345
('Hoang Gia Bao', 'customer3@gmail.com',
'$2y$12$CFZhPCSdlJ.ppWgP/cE/z.6pSfBj3anrbpgxf7QpnJr8Cf4Gl7KBy',
'0912000003'),

-- password: customer456
('Nguyen Thu Ha', 'customer4@gmail.com',
'$2y$12$Zbc28O9R81qmxep64NP37.oHaZjW4c88k5yMNmmxSHDxKZsmBBSCS',
'0912000004'),

-- password: customer567
('Tran Quoc Viet', 'customer5@gmail.com',
'$2y$12$77bQ7/zhfhwA.QQWejpIi.5cI65vrQOLwilbA9WEx83QqXOEbwmlu',
'0912000005'),

-- password: customer678
('Do Minh Anh', 'customer6@gmail.com',
'$2y$12$Z42d/kKF7r0wJM4pzwkxvuDE9o7xDv5sjrhuX6v3JBQ5RBTvSFBr2',
'0912000006');

-- =========================
-- ADMINS
-- =========================
INSERT INTO admins (admin_id, salary, is_super_admin) VALUES
(1, 25000000, 1),
(2, 18000000, 0),
(3, 22000000, 1);

-- =========================
-- CUSTOMERS
-- =========================
INSERT INTO customers (customer_id, shipping_address, receiver_name, receiver_phone, customer_status) VALUES
(4, '123 Le Loi, District 1, Ho Chi Minh City', 'Le Minh Customer', '0912000001', 1),
(5, '45 Nguyen Hue, District 1, Ho Chi Minh City', 'Pham Ngoc Lan', '0912000002', 1),
(6, '88 Hai Ba Trung, District 3, Ho Chi Minh City', 'Hoang Gia Bao', '0912000003', 1),
(7, '12 Vo Van Tan, District 3, Ho Chi Minh City', 'Nguyen Thu Ha', '0912000004', 0),
(8, '77 Cach Mang Thang 8, District 10, Ho Chi Minh City', 'Tran Quoc Viet', '0912000005', 1),
(9, '21 Phan Xich Long, Phu Nhuan, Ho Chi Minh City', 'Do Minh Anh', '0912000006', 1);

-- =========================
-- CATEGORIES
-- =========================
INSERT INTO categories (category_name, parent_category_id) VALUES
('Books', NULL),
('Programming', 1),
('Business', 1),
('Self-help', 1),
('Novel', 1),
('Manga', 1),
('Light Novel', 1),
('Best Seller', NULL);

-- =========================
-- PRODUCTS
-- =========================
INSERT INTO products (product_name, brand, description, created_by_admin_id, updated_by_admin_id) VALUES
('Clean Code', 'Prentice Hall', 'A handbook of agile software craftsmanship.', 1, 1),
('The Pragmatic Programmer', 'Addison-Wesley', 'Classic book for software developers.', 1, 1),
('Atomic Habits', 'Avery', 'An easy and proven way to build good habits.', 1, 2),
('Deep Work', 'Grand Central Publishing', 'Rules for focused success in a distracted world.', 2, 2),
('Think and Grow Rich', 'The Ralston Society', 'A classic personal success book.', 2, 3),
('One Piece Vol.1', 'Shueisha', 'Adventure manga by Eiichiro Oda.', 1, 2),
('Naruto Vol.1', 'Shueisha', 'Ninja action manga by Masashi Kishimoto.', 1, 3),
('Doraemon Vol.1', 'Shogakukan', 'Classic manga for all ages.', 2, 2),
('Your Name', 'Kadokawa', 'Japanese light novel based on the animated film.', 3, 3),
('Harry Potter and the Sorcerer''s Stone', 'Bloomsbury', 'Fantasy novel about a young wizard.', 1, 3);

-- =========================
-- PRODUCT_CATEGORIES
-- =========================
INSERT INTO product_categories (product_id, category_id) VALUES
(1, 1), (1, 2), (1, 8),
(2, 1), (2, 2),
(3, 1), (3, 4), (3, 8),
(4, 1), (4, 4),
(5, 1), (5, 3),
(6, 1), (6, 6), (6, 8),
(7, 1), (7, 6),
(8, 1), (8, 6),
(9, 1), (9, 7), (9, 8),
(10, 1), (10, 5), (10, 8);

-- =========================
-- PRODUCT_VERSIONS
-- =========================
INSERT INTO product_versions
(product_id, sku, version_name, format_type, language, cover_type, edition, price, stock_quantity, image_url, version_status)
VALUES
-- Product 1: Clean Code
(1, 'P001-V01', 'Paperback - Vietnamese', 'paperback', 'Vietnamese', 'Soft Cover', '1st Edition', 120000, 50, NULL, 'available'),
(1, 'P001-V02', 'Hardcover - English', 'hardcover', 'English', 'Hard Cover', '1st Edition', 250000, 20, NULL, 'available'),
(1, 'P001-V03', 'eBook - English', 'ebook', 'English', NULL, 'Digital Edition', 90000, 999, NULL, 'available'),

-- Product 2: The Pragmatic Programmer
(2, 'P002-V01', 'Paperback - English', 'paperback', 'English', 'Soft Cover', '20th Anniversary', 180000, 35, NULL, 'available'),
(2, 'P002-V02', 'Hardcover - English', 'hardcover', 'English', 'Hard Cover', '20th Anniversary', 290000, 12, NULL, 'available'),
(2, 'P002-V03', 'eBook - English', 'ebook', 'English', NULL, 'Digital Edition', 110000, 999, NULL, 'available'),

-- Product 3: Atomic Habits
(3, 'P003-V01', 'Paperback - Vietnamese', 'paperback', 'Vietnamese', 'Soft Cover', 'Vietnamese Edition', 99000, 100, NULL, 'available'),
(3, 'P003-V02', 'Hardcover - English', 'hardcover', 'English', 'Hard Cover', 'Deluxe Edition', 280000, 10, NULL, 'available'),
(3, 'P003-V03', 'eBook - English', 'ebook', 'English', NULL, 'Digital Edition', 85000, 999, NULL, 'available'),

-- Product 4: Deep Work
(4, 'P004-V01', 'Paperback - English', 'paperback', 'English', 'Soft Cover', '1st Edition', 160000, 28, NULL, 'available'),
(4, 'P004-V02', 'Hardcover - English', 'hardcover', 'English', 'Hard Cover', '1st Edition', 260000, 15, NULL, 'available'),
(4, 'P004-V03', 'eBook - English', 'ebook', 'English', NULL, 'Digital Edition', 95000, 999, NULL, 'available'),

-- Product 5: Think and Grow Rich
(5, 'P005-V01', 'Paperback - Vietnamese', 'paperback', 'Vietnamese', 'Soft Cover', 'Reprint 2024', 88000, 60, NULL, 'available'),
(5, 'P005-V02', 'Hardcover - English', 'hardcover', 'English', 'Hard Cover', 'Classic Edition', 210000, 18, NULL, 'available'),
(5, 'P005-V03', 'eBook - English', 'ebook', 'English', NULL, 'Digital Edition', 70000, 999, NULL, 'available'),

-- Product 6: One Piece Vol.1
(6, 'P006-V01', 'Paperback - Vietnamese', 'paperback', 'Vietnamese', 'Soft Cover', 'Vietnamese Edition', 35000, 120, NULL, 'available'),
(6, 'P006-V02', 'Paperback - English', 'paperback', 'English', 'Soft Cover', 'English Edition', 95000, 40, NULL, 'available'),
(6, 'P006-V03', 'Collector Edition - Japanese', 'special_edition', 'Japanese', 'Soft Cover', 'Collector Edition', 180000, 15, NULL, 'available'),

-- Product 7: Naruto Vol.1
(7, 'P007-V01', 'Paperback - Vietnamese', 'paperback', 'Vietnamese', 'Soft Cover', 'Vietnamese Edition', 35000, 100, NULL, 'available'),
(7, 'P007-V02', 'Paperback - English', 'paperback', 'English', 'Soft Cover', 'English Edition', 90000, 35, NULL, 'available'),
(7, 'P007-V03', 'Collector Edition - Japanese', 'special_edition', 'Japanese', 'Soft Cover', 'Collector Edition', 170000, 0, NULL, 'out_of_stock'),

-- Product 8: Doraemon Vol.1
(8, 'P008-V01', 'Paperback - Vietnamese', 'paperback', 'Vietnamese', 'Soft Cover', 'Vietnamese Edition', 30000, 150, NULL, 'available'),
(8, 'P008-V02', 'Paperback - English', 'paperback', 'English', 'Soft Cover', 'English Edition', 85000, 30, NULL, 'available'),
(8, 'P008-V03', 'Special Edition - Vietnamese', 'special_edition', 'Vietnamese', 'Soft Cover', 'Special Edition', 120000, 20, NULL, 'available'),

-- Product 9: Your Name
(9, 'P009-V01', 'Paperback - Vietnamese', 'paperback', 'Vietnamese', 'Soft Cover', 'Vietnamese Edition', 105000, 70, NULL, 'available'),
(9, 'P009-V02', 'Paperback - English', 'paperback', 'English', 'Soft Cover', 'English Edition', 160000, 25, NULL, 'available'),
(9, 'P009-V03', 'Hardcover - Japanese', 'hardcover', 'Japanese', 'Hard Cover', 'Japanese Edition', 260000, 10, NULL, 'available'),

-- Product 10: Harry Potter and the Sorcerer''s Stone
(10, 'P010-V01', 'Paperback - Vietnamese', 'paperback', 'Vietnamese', 'Soft Cover', 'Vietnamese Edition', 150000, 80, 'uploads/products/product_1777948991_4176.jpg', 'available'),
(10, 'P010-V02', 'Paperback - English', 'paperback', 'English', 'Soft Cover', 'English Edition', 220000, 45, NULL, 'available'),
(10, 'P010-V03', 'Hardcover - English', 'hardcover', 'English', 'Hard Cover', 'Illustrated Edition', 450000, 12, NULL, 'available');

-- =========================
-- CARTS
-- =========================
INSERT INTO carts (customer_id) VALUES
(4), (5), (6), (7), (8), (9);

-- =========================
-- CART_ITEMS
-- =========================
INSERT INTO cart_items (cart_id, version_id, quantity) VALUES
(1, 1, 2),
(1, 7, 1),
(2, 5, 1),
(2, 16, 2),
(3, 22, 3),
(3, 28, 1),
(4, 10, 1),
(5, 19, 1),
(5, 25, 2),
(6, 30, 1);

-- =========================
-- ORDERS
-- =========================
INSERT INTO orders
(customer_id, order_status, shipping_address, receiver_name, receiver_phone, total_amount, processed_by_admin_id, note)
VALUES
(4, 'confirmed', '123 Le Loi, District 1, Ho Chi Minh City', 'Le Minh Customer', '0912000001', 339000, 2, 'Customer requested fast delivery'),
(5, 'shipping', '45 Nguyen Hue, District 1, Ho Chi Minh City', 'Pham Ngoc Lan', '0912000002', 360000, 2, 'Books should be packed carefully'),
(6, 'pending', '88 Hai Ba Trung, District 3, Ho Chi Minh City', 'Hoang Gia Bao', '0912000003', 255000, NULL, 'Waiting for payment confirmation'),
(8, 'delivered', '77 Cach Mang Thang 8, District 10, Ho Chi Minh City', 'Tran Quoc Viet', '0912000005', 315000, 3, 'Delivered successfully'),
(9, 'cancelled', '21 Phan Xich Long, Phu Nhuan, Ho Chi Minh City', 'Do Minh Anh', '0912000006', 450000, 1, 'Payment failed');

-- =========================
-- ORDER_ITEMS
-- =========================
INSERT INTO order_items
(order_id, version_id, product_name_snapshot, version_name_snapshot, unit_price, quantity, subtotal)
VALUES
(1, 1, 'Clean Code', 'Paperback - Vietnamese', 120000, 2, 240000),
(1, 7, 'Atomic Habits', 'Paperback - Vietnamese', 99000, 1, 99000),

(2, 5, 'The Pragmatic Programmer', 'Hardcover - English', 290000, 1, 290000),
(2, 13, 'Think and Grow Rich', 'Paperback - Vietnamese', 88000, 1, 88000),
(2, 16, 'One Piece Vol.1', 'Paperback - Vietnamese', 35000, 2, 70000),

(3, 22, 'Doraemon Vol.1', 'Paperback - Vietnamese', 30000, 3, 90000),
(3, 28, 'Harry Potter and the Sorcerer''s Stone', 'Paperback - Vietnamese', 150000, 1, 150000),
(3, 24, 'Doraemon Vol.1', 'Special Edition - Vietnamese', 120000, 1, 120000),

(4, 19, 'Naruto Vol.1', 'Paperback - Vietnamese', 35000, 1, 35000),
(4, 25, 'Your Name', 'Paperback - Vietnamese', 105000, 2, 210000),
(4, 10, 'Deep Work', 'Paperback - English', 160000, 1, 160000),

(5, 30, 'Harry Potter and the Sorcerer''s Stone', 'Hardcover - English', 450000, 1, 450000);

-- =========================
-- PAYMENTS
-- =========================
INSERT INTO payments
(order_id, payment_method, payment_status, amount, transaction_code, paid_at)
VALUES
(1, 'cod', 'pending', 339000, NULL, NULL),
(2, 'vnpay', 'paid', 448000, 'VNPAY_20260422_0001', '2026-04-22 09:30:00'),
(3, 'bank_transfer', 'pending', 360000, NULL, NULL),
(4, 'momo', 'paid', 405000, 'MOMO_20260422_0002', '2026-04-22 10:20:00'),
(5, 'credit_card', 'failed', 450000, 'CARD_20260422_0003', NULL);

-- =========================
-- ORDER_STATUS_HISTORY
-- =========================
INSERT INTO order_status_history
(order_id, old_status, new_status, changed_by_admin_id, changed_at, note)
VALUES
(1, NULL, 'pending', NULL, '2026-04-22 08:00:00', 'Order created by customer'),
(1, 'pending', 'confirmed', 2, '2026-04-22 08:30:00', 'Admin confirmed order'),

(2, NULL, 'pending', NULL, '2026-04-22 08:10:00', 'Order created by customer'),
(2, 'pending', 'confirmed', 2, '2026-04-22 08:35:00', 'Payment verified'),
(2, 'confirmed', 'shipping', 2, '2026-04-22 09:50:00', 'Order handed to delivery unit'),

(3, NULL, 'pending', NULL, '2026-04-22 10:00:00', 'Order created by customer'),

(4, NULL, 'pending', NULL, '2026-04-21 11:00:00', 'Order created by customer'),
(4, 'pending', 'confirmed', 3, '2026-04-21 11:20:00', 'Payment verified'),
(4, 'confirmed', 'shipping', 3, '2026-04-21 13:00:00', 'Shipped out'),
(4, 'shipping', 'delivered', 3, '2026-04-22 09:00:00', 'Customer received order'),

(5, NULL, 'pending', NULL, '2026-04-22 12:00:00', 'Order created by customer'),
(5, 'pending', 'cancelled', 1, '2026-04-22 12:30:00', 'Payment failed, order cancelled');

-- =========================
-- CONTACTS
-- =========================
INSERT INTO contacts
(full_name, email, subject, message, contact_status, handled_by_admin_id)
VALUES
('Nguyen Thi Hong', 'hong@gmail.com', 'Hỏi về thời gian giao hàng', 'Cho mình hỏi đơn hàng ở Hà Nội thường giao trong bao lâu?', 'replied', 2),
('Tran Van Duc', 'duc@gmail.com', 'Hỗ trợ đổi trả', 'Nếu sách bị lỗi in hoặc rách trang thì đổi trả như thế nào?', 'in_progress', 2),
('Le Phuong Anh', 'phuonganh@gmail.com', 'Hỏi về sản phẩm', 'Bên mình còn bản hardcover của Atomic Habits không?', 'new', NULL),
('Vo Minh Tuan', 'tuan@gmail.com', 'Liên hệ hợp tác', 'Mình muốn liên hệ hợp tác phân phối sách.', 'closed', 1),
('Pham Gia Han', 'giah@gmail.com', 'Cập nhật thông tin', 'Mình cần đổi địa chỉ giao hàng cho đơn đã đặt.', 'new', NULL);

-- =========================
-- FAQS
-- =========================
INSERT INTO faqs
(question, answer, category, is_active, created_by_admin_id)
VALUES
('Thời gian giao hàng mất bao lâu?', 'Thông thường đơn hàng nội thành mất 1-3 ngày làm việc, ngoại thành mất 3-5 ngày.', 'Shipping', 1, 1),
('Tôi có thể thanh toán bằng cách nào?', 'Bạn có thể thanh toán bằng COD, chuyển khoản, MoMo, VNPay hoặc thẻ tín dụng.', 'Payment', 1, 1),
('Làm sao để đổi trả sách?', 'Bạn có thể liên hệ bộ phận hỗ trợ trong vòng 7 ngày kể từ khi nhận hàng nếu sách bị lỗi in, rách trang hoặc giao sai sản phẩm.', 'Return', 1, 2),
('Tôi có cần tài khoản để đặt hàng không?', 'Có, bạn nên đăng nhập tài khoản để theo dõi đơn hàng và lịch sử mua sách.', 'Account', 1, 2),
('Sách hết hàng có nhập lại không?', 'Tùy từng đầu sách, hệ thống sẽ cập nhật khi có hàng mới.', 'Product', 1, 3);

-- =========================================================
-- SOME USEFUL TEST QUERIES
-- =========================================================

-- 1. Xem tất cả user + phân loại admin/customer
-- SELECT 
--     u.user_id,
--     u.full_name,
--     u.email,
--     CASE
--         WHEN a.admin_id IS NOT NULL THEN 'Admin'
--         WHEN c.customer_id IS NOT NULL THEN 'Customer'
--         ELSE 'Unknown'
--     END AS user_type
-- FROM users u
-- LEFT JOIN admins a ON u.user_id = a.admin_id
-- LEFT JOIN customers c ON u.user_id = c.customer_id;

-- 2. Xem customer đang hoạt động / bị ban
-- SELECT 
--     u.full_name,
--     u.email,
--     c.customer_status
-- FROM customers c
-- JOIN users u ON c.customer_id = u.user_id;

-- 3. Xem admin cấp cao
-- SELECT 
--     u.full_name,
--     a.salary,
--     a.is_super_admin
-- FROM admins a
-- JOIN users u ON a.admin_id = u.user_id;

-- 4. Xem product và category
-- SELECT
--     p.product_id,
--     p.product_name,
--     c.category_name
-- FROM product_categories pc
-- JOIN products p ON pc.product_id = p.product_id
-- JOIN categories c ON pc.category_id = c.category_id
-- ORDER BY p.product_id, c.category_name;

-- 5. Xem tất cả version của product
-- SELECT
--     p.product_name,
--     pv.version_name,
--     pv.format_type,
--     pv.price,
--     pv.stock_quantity,
--     pv.version_status
-- FROM product_versions pv
-- JOIN products p ON pv.product_id = p.product_id
-- ORDER BY p.product_id, pv.version_id;

-- 6. Xem cart của customer
-- SELECT
--     u.full_name AS customer_name,
--     ca.cart_id,
--     p.product_name,
--     pv.version_name,
--     ci.quantity,
--     pv.price
-- FROM carts ca
-- JOIN customers c ON ca.customer_id = c.customer_id
-- JOIN users u ON c.customer_id = u.user_id
-- JOIN cart_items ci ON ca.cart_id = ci.cart_id
-- JOIN product_versions pv ON ci.version_id = pv.version_id
-- JOIN products p ON pv.product_id = p.product_id
-- ORDER BY ca.cart_id;

-- 7. Xem chi tiết order
-- SELECT
--     o.order_id,
--     u.full_name AS customer_name,
--     o.order_status,
--     oi.product_name_snapshot,
--     oi.version_name_snapshot,
--     oi.unit_price,
--     oi.quantity,
--     oi.subtotal
-- FROM orders o
-- JOIN customers c ON o.customer_id = c.customer_id
-- JOIN users u ON c.customer_id = u.user_id
-- JOIN order_items oi ON o.order_id = oi.order_id
-- ORDER BY o.order_id, oi.order_item_id;

-- 8. Xem payment của order
-- SELECT
--     o.order_id,
--     u.full_name AS customer_name,
--     p.payment_method,
--     p.payment_status,
--     p.amount,
--     p.transaction_code,
--     p.paid_at
-- FROM payments p
-- JOIN orders o ON p.order_id = o.order_id
-- JOIN customers c ON o.customer_id = c.customer_id
-- JOIN users u ON c.customer_id = u.user_id
-- ORDER BY o.order_id;

-- 9. Xem lịch sử trạng thái đơn hàng
-- SELECT
--     osh.order_id,
--     osh.old_status,
--     osh.new_status,
--     osh.changed_at,
--     admin_user.full_name AS changed_by,
--     osh.note
-- FROM order_status_history osh
-- LEFT JOIN admins a ON osh.changed_by_admin_id = a.admin_id
-- LEFT JOIN users admin_user ON a.admin_id = admin_user.user_id
-- ORDER BY osh.order_id, osh.changed_at;

-- 10. Xem contact messages
-- SELECT
--     ct.contact_id,
--     ct.full_name,
--     ct.email,
--     ct.subject,
--     ct.contact_status,
--     u.full_name AS handled_by
-- FROM contacts ct
-- LEFT JOIN admins a ON ct.handled_by_admin_id = a.admin_id
-- LEFT JOIN users u ON a.admin_id = u.user_id
-- ORDER BY ct.contact_id;