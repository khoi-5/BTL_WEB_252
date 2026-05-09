const fs = require('fs');
const path = require('path');

const dir = __dirname;
const files = fs.readdirSync(dir).filter(f => f.endsWith('.html'));

const sidebarAddition = `
                            <li>
                                <a href="flash-sales.html">
                                    <i class="fa-solid fa-bolt"></i>
                                    <span>Flash Sales</span>
                                </a>
                            </li>
                            <li>
                                <a href="sections.html">
                                    <i class="fa-solid fa-layer-group"></i>
                                    <span>Danh mục trang chủ</span>
                                </a>
                            </li>
`;

files.forEach(file => {
    let content = fs.readFileSync(path.join(dir, file), 'utf8');
    
    // Add to sidebar
    if (!content.includes('flash-sales.html') && content.includes('<li id="superAdminMenu"')) {
        content = content.replace('<li id="superAdminMenu"', sidebarAddition + '\n                            <li id="superAdminMenu"');
    }
    
    // If it's flash-sales.html, replace its content specific to products
    if (file === 'flash-sales.html') {
        content = content.replace(/Quản lý sản phẩm/g, 'Quản lý Flash Sales');
        content = content.replace(/Thêm, sửa, xóa và tìm kiếm sản phẩm/g, 'Thêm, sửa, xóa các chương trình khuyến mãi chớp nhoáng');
        content = content.replace(/productFormTitle/g, 'flashSaleFormTitle');
        content = content.replace(/Thêm sản phẩm/g, 'Thêm Flash Sale');
        content = content.replace(/resetProductForm\(\)/g, 'resetFlashSaleForm()');
        content = content.replace(/productForm/g, 'flashSaleForm');
        content = content.replace(/productId/g, 'flashSaleId');
        
        // Form replacement
        const formStart = content.indexOf('<div class="row">');
        const formEnd = content.indexOf('<p id="productMessage"></p>');
        if (formStart !== -1 && formEnd !== -1) {
            const formHtml = `
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Tên chương trình (Flash Sale)</label>
                                            <input type="text" class="form-control" id="flashSaleName" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Thời gian bắt đầu</label>
                                            <input type="datetime-local" class="form-control" id="startTime" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Thời gian kết thúc</label>
                                            <input type="datetime-local" class="form-control" id="endTime" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Trạng thái (Bật/Tắt)</label>
                                            <select class="form-control" id="isActive">
                                                <option value="1">Đang kích hoạt</option>
                                                <option value="0">Vô hiệu hóa</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Ảnh Banner (Tùy chọn)</label>
                                            <input type="file" class="form-control" id="flashSaleImageFile" accept="image/*">
                                            <input type="hidden" id="flashSaleImageUrl">
                                        </div>
                                        <div class="col-12 mb-3">
                                            <label class="form-label">Mô tả chương trình</label>
                                            <textarea class="form-control" id="flashSaleDesc" rows="4"></textarea>
                                        </div>
            `;
            content = content.substring(0, formStart + '<div class="row">'.length) + formHtml + content.substring(formEnd);
        }
        
        content = content.replace(/productMessage/g, 'flashSaleMessage');
        content = content.replace(/submitProductBtn/g, 'submitFlashSaleBtn');
        content = content.replace(/Cập nhật sản phẩm/g, 'Cập nhật Flash Sale');
        
        // Search replacement
        content = content.replace(/productSearchInput/g, 'flashSaleSearchInput');
        content = content.replace(/Tìm theo tên sản phẩm, thương hiệu, SKU.../g, 'Tìm kiếm Flash Sale...');
        content = content.replace(/searchProducts\(\)/g, 'searchFlashSales()');
        content = content.replace(/loadProducts\(1\)/g, 'loadFlashSales()');
        content = content.replace(/productList/g, 'flashSaleList');
        content = content.replace(/productPagination/g, 'flashSalePagination');
        content = content.replace(/admin-products\.js/g, 'admin-flash-sales.js');
        content = content.replace(/loadProducts\(\);/g, 'loadFlashSales();');
    }
    
    // If it's sections.html, replace its content
    if (file === 'sections.html') {
        content = content.replace(/Quản lý sản phẩm/g, 'Quản lý Danh mục trang chủ (Sections)');
        content = content.replace(/Thêm, sửa, xóa và tìm kiếm sản phẩm/g, 'Tùy chỉnh các khối danh mục sản phẩm hiển thị trên trang chủ');
        content = content.replace(/productFormTitle/g, 'sectionFormTitle');
        content = content.replace(/Thêm sản phẩm/g, 'Thêm Danh mục');
        content = content.replace(/resetProductForm\(\)/g, 'resetSectionForm()');
        content = content.replace(/productForm/g, 'sectionForm');
        content = content.replace(/productId/g, 'sectionId');
        
        // Form replacement
        const formStart = content.indexOf('<div class="row">');
        const formEnd = content.indexOf('<p id="productMessage"></p>');
        if (formStart !== -1 && formEnd !== -1) {
            const formHtml = `
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Tên khối danh mục (VD: Sách Bán Chạy)</label>
                                            <input type="text" class="form-control" id="sectionName" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Trạng thái (Bật/Tắt hiển thị)</label>
                                            <select class="form-control" id="sectionStatus">
                                                <option value="1">Hiển thị</option>
                                                <option value="0">Ẩn</option>
                                            </select>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Ảnh Banner cho khối này</label>
                                            <input type="file" class="form-control" id="sectionImageFile" accept="image/*">
                                            <input type="hidden" id="sectionImageUrl">
                                        </div>
                                        <div class="col-12 mb-3">
                                            <label class="form-label">Mô tả phụ</label>
                                            <textarea class="form-control" id="sectionDesc" rows="3"></textarea>
                                        </div>
            `;
            content = content.substring(0, formStart + '<div class="row">'.length) + formHtml + content.substring(formEnd);
        }
        
        content = content.replace(/productMessage/g, 'sectionMessage');
        content = content.replace(/submitProductBtn/g, 'submitSectionBtn');
        content = content.replace(/Cập nhật sản phẩm/g, 'Cập nhật Danh mục');
        
        // Search replacement - removing search for sections as there are usually few
        content = content.replace(/Tìm theo tên sản phẩm, thương hiệu, SKU.../g, 'Lọc danh mục...');
        content = content.replace(/productSearchInput/g, 'sectionSearchInput');
        content = content.replace(/searchProducts\(\)/g, 'loadSections()');
        content = content.replace(/loadProducts\(1\)/g, 'loadSections()');
        content = content.replace(/productList/g, 'sectionList');
        content = content.replace(/productPagination/g, 'sectionPagination');
        content = content.replace(/admin-products\.js/g, 'admin-sections.js');
        content = content.replace(/loadProducts\(\);/g, 'loadSections();');
    }
    
    fs.writeFileSync(path.join(dir, file), content);
});
console.log("Done updating admin html files");
