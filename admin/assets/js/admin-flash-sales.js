const API_BASE = "http://localhost:8000/api";

function getImageUrl(imageUrl) {
    if (!imageUrl || imageUrl.trim() === "") {
        return "http://localhost:8000/uploads/flash-sales/default-flash-sale.jpg";
    }
    if (imageUrl.startsWith("http")) {
        return imageUrl;
    }
    return `http://localhost:8000/${imageUrl}`;
}

function formatDate(dateStr) {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    return date.toLocaleString('vi-VN');
}

function formatForInput(dateStr) {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
    return date.toISOString().slice(0, 16);
}

async function loadFlashSales() {
    const url = `${API_BASE}/admin/flash-sales`;

    try {
        const response = await fetch(url);
        const result = await response.json();

        if (!result.success) {
            showFlashSaleMessage(result.message || "Không tải được danh sách", "danger");
            return;
        }

        renderFlashSales(result.data || []);
    } catch (error) {
        console.error(error);
        showFlashSaleMessage("Lỗi kết nối server", "danger");
    }
}

function renderFlashSales(sales) {
    const listEl = document.getElementById("flashSaleList");

    if (!sales || sales.length === 0) {
        listEl.innerHTML = `
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        Không có chương trình Flash Sale nào.
                    </div>
                </div>
            </div>
        `;
        return;
    }

    listEl.innerHTML = sales.map(sale => {
        let statusBadge = "";
        if (sale.sale_status === "active") {
            statusBadge = `<span class="badge bg-success">Đang diễn ra</span>`;
        } else if (sale.sale_status === "upcoming") {
            statusBadge = `<span class="badge bg-warning text-dark">Sắp diễn ra</span>`;
        } else {
            statusBadge = `<span class="badge bg-secondary">Đã kết thúc</span>`;
        }

        const isActive = sale.flash_sale_is_active == 1 
            ? `<span class="badge bg-primary">Bật</span>` 
            : `<span class="badge bg-danger">Tắt</span>`;

        return `
        <div class="col-lg-6 col-md-12 mt-4">
            <div class="card card-bordered h-100">
                <img 
                    class="card-img-top img-fluid"
                    src="${getImageUrl(sale.flash_sale_image)}"
                    alt="${escapeHtml(sale.flash_sale_name)}"
                    style="height: 200px; object-fit: cover;"
                    onerror="this.src='http://localhost:8000/uploads/flash-sales/default-flash-sale.jpg'"
                >

                <div class="card-body d-flex flex-column">
                    <h5 class="title">${escapeHtml(sale.flash_sale_name)} ${statusBadge} ${isActive}</h5>

                    <p class="mb-1 mt-2">
                        <strong>Bắt đầu:</strong> ${formatDate(sale.flash_sale_start_time)}
                    </p>

                    <p class="mb-1">
                        <strong>Kết thúc:</strong> ${formatDate(sale.flash_sale_end_time)}
                    </p>

                    <p class="card-text mt-2 flex-grow-1">
                        ${escapeHtml(sale.flash_sale_description || "Không có mô tả")}
                    </p>

                    <div class="mt-3">
                        <button class="btn btn-warning btn-sm" onclick="editFlashSale(${sale.flash_sale_id})">
                            Sửa
                        </button>

                        <button class="btn btn-danger btn-sm" onclick="deleteFlashSale(${sale.flash_sale_id})">
                            Xóa
                        </button>
                    </div>
                </div>
            </div>
        </div>
        `;
    }).join("");
}

function searchFlashSales() {
    const q = document.getElementById("flashSaleSearchInput").value.toLowerCase();
    const items = document.querySelectorAll("#flashSaleList .col-lg-6");
    
    items.forEach(item => {
        const title = item.querySelector(".title").innerText.toLowerCase();
        if (title.includes(q)) {
            item.style.display = "";
        } else {
            item.style.display = "none";
        }
    });
}

function clearSearch() {
    document.getElementById("flashSaleSearchInput").value = "";
    const items = document.querySelectorAll("#flashSaleList .col-lg-6");
    items.forEach(item => item.style.display = "");
}

async function editFlashSale(id) {
    try {
        const response = await fetch(`${API_BASE}/admin/flash-sales/detail?id=${id}`);
        const result = await response.json();

        if (!result.success) {
            showFlashSaleMessage(result.message || "Không lấy được chi tiết", "danger");
            return;
        }

        const sale = result.data;

        document.getElementById("flashSaleId").value = sale.flash_sale_id;
        document.getElementById("flashSaleName").value = sale.flash_sale_name || "";
        document.getElementById("startTime").value = formatForInput(sale.flash_sale_start_time);
        document.getElementById("endTime").value = formatForInput(sale.flash_sale_end_time);
        document.getElementById("isActive").value = sale.flash_sale_is_active || "1";
        document.getElementById("flashSaleDesc").value = sale.flash_sale_description || "";

        document.getElementById("flashSaleFormTitle").innerText = "Sửa Flash Sale";
        document.getElementById("submitFlashSaleBtn").innerText = "Cập nhật Flash Sale";

        window.scrollTo({ top: 0, behavior: "smooth" });
    } catch (error) {
        console.error(error);
        showFlashSaleMessage("Lỗi kết nối server", "danger");
    }
}

async function deleteFlashSale(id) {
    const ok = confirm("Bạn có chắc muốn xóa Flash Sale này không?");
    if (!ok) return;

    try {
        const response = await fetch(`${API_BASE}/admin/flash-sales/delete?id=${id}`, {
            method: "DELETE"
        });

        const result = await response.json();

        if (!result.success) {
            showFlashSaleMessage(result.message || "Xóa thất bại", "danger");
            return;
        }

        showFlashSaleMessage("Xóa thành công", "success");
        loadFlashSales();
    } catch (error) {
        console.error(error);
        showFlashSaleMessage("Lỗi kết nối server", "danger");
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("flashSaleForm");

    if (!form) return;

    form.addEventListener("submit", async function (e) {
        e.preventDefault();

        const id = document.getElementById("flashSaleId").value;
        const fileInput = document.getElementById("flashSaleImageFile");
        
        const formData = new FormData();
        formData.append("name", document.getElementById("flashSaleName").value.trim());
        formData.append("start_time", document.getElementById("startTime").value);
        formData.append("end_time", document.getElementById("endTime").value);
        formData.append("is_active", document.getElementById("isActive").value);
        formData.append("description", document.getElementById("flashSaleDesc").value.trim());
        
        if (fileInput.files && fileInput.files.length > 0) {
            formData.append("image", fileInput.files[0]);
        }

        const isUpdate = id !== "";
        const url = isUpdate
            ? `${API_BASE}/admin/flash-sales/update?id=${id}`
            : `${API_BASE}/admin/flash-sales`;

        try {
            const response = await fetch(url, {
                method: "POST", // We use POST for both create and update because of FormData multipart
                body: formData
            });

            const result = await response.json();

            if (!result.success) {
                showFlashSaleMessage(result.message || "Lưu thất bại", "danger");
                return;
            }

            showFlashSaleMessage(
                isUpdate ? "Cập nhật thành công" : "Thêm mới thành công",
                "success"
            );

            resetFlashSaleForm();
            loadFlashSales();
        } catch (error) {
            console.error(error);
            showFlashSaleMessage("Lỗi kết nối server", "danger");
        }
    });

    const searchInput = document.getElementById("flashSaleSearchInput");
    if (searchInput) {
        searchInput.addEventListener("keyup", searchFlashSales);
    }
});

function resetFlashSaleForm() {
    document.getElementById("flashSaleForm").reset();
    document.getElementById("flashSaleId").value = "";
    document.getElementById("flashSaleFormTitle").innerText = "Thêm Flash Sale";
    document.getElementById("submitFlashSaleBtn").innerText = "Thêm Flash Sale";
    showFlashSaleMessage("", "");
}

function showFlashSaleMessage(message, type) {
    const messageEl = document.getElementById("flashSaleMessage");
    if (!messageEl) return;

    if (!message) {
        messageEl.innerHTML = "";
        return;
    }

    messageEl.innerHTML = `
        <span class="text-${type}">
            ${message}
        </span>
    `;
}

function escapeHtml(value) {
    if (value === null || value === undefined) return "";
    return String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}
