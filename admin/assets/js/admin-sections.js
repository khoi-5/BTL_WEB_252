const API_BASE = "http://localhost:8000/api";

function getImageUrl(imageUrl) {
    if (!imageUrl || imageUrl.trim() === "") {
        return "http://localhost:8000/uploads/sections/default-section.jpg";
    }
    if (imageUrl.startsWith("http")) {
        return imageUrl;
    }
    return `http://localhost:8000/${imageUrl}`;
}

async function loadSections() {
    const url = `${API_BASE}/admin/sections`;

    try {
        const response = await fetch(url);
        const result = await response.json();

        if (!result.success) {
            showSectionMessage(result.message || "Không tải được danh sách", "danger");
            return;
        }

        renderSections(result.data || []);
    } catch (error) {
        console.error(error);
        showSectionMessage("Lỗi kết nối server", "danger");
    }
}

function renderSections(sections) {
    const listEl = document.getElementById("sectionList");

    if (!sections || sections.length === 0) {
        listEl.innerHTML = `
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        Không có khối danh mục nào.
                    </div>
                </div>
            </div>
        `;
        return;
    }

    listEl.innerHTML = sections.map(sec => {
        const isActive = sec.section_status == 1 
            ? `<span class="badge bg-success">Đang hiển thị</span>` 
            : `<span class="badge bg-secondary">Đang ẩn</span>`;

        return `
        <div class="col-lg-6 col-md-12 mt-4">
            <div class="card card-bordered h-100">
                <img 
                    class="card-img-top img-fluid"
                    src="${getImageUrl(sec.section_image)}"
                    alt="${escapeHtml(sec.section_name)}"
                    style="height: 200px; object-fit: cover;"
                    onerror="this.src='http://localhost:8000/uploads/sections/default-section.jpg'"
                >

                <div class="card-body d-flex flex-column">
                    <h5 class="title">${escapeHtml(sec.section_name)} ${isActive}</h5>

                    <p class="card-text mt-2 flex-grow-1">
                        ${escapeHtml(sec.section_description || "Không có mô tả")}
                    </p>

                    <div class="mt-3">
                        <button class="btn btn-warning btn-sm" onclick="editSection(${sec.section_id})">
                            Sửa
                        </button>

                        <button class="btn btn-danger btn-sm" onclick="deleteSection(${sec.section_id})">
                            Xóa
                        </button>
                    </div>
                </div>
            </div>
        </div>
        `;
    }).join("");
}

function searchSections() {
    const q = document.getElementById("sectionSearchInput").value.toLowerCase();
    const items = document.querySelectorAll("#sectionList .col-lg-6");
    
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
    document.getElementById("sectionSearchInput").value = "";
    const items = document.querySelectorAll("#sectionList .col-lg-6");
    items.forEach(item => item.style.display = "");
}

async function editSection(id) {
    try {
        const response = await fetch(`${API_BASE}/admin/sections/detail?id=${id}`);
        const result = await response.json();

        if (!result.success) {
            showSectionMessage(result.message || "Không lấy được chi tiết", "danger");
            return;
        }

        const sec = result.data;

        document.getElementById("sectionId").value = sec.section_id;
        document.getElementById("sectionName").value = sec.section_name || "";
        document.getElementById("sectionStatus").value = sec.section_status || "1";
        document.getElementById("sectionDesc").value = sec.section_description || "";

        document.getElementById("sectionFormTitle").innerText = "Sửa Danh mục";
        document.getElementById("submitSectionBtn").innerText = "Cập nhật Danh mục";

        window.scrollTo({ top: 0, behavior: "smooth" });
    } catch (error) {
        console.error(error);
        showSectionMessage("Lỗi kết nối server", "danger");
    }
}

async function deleteSection(id) {
    const ok = confirm("Bạn có chắc muốn xóa khối danh mục này không?");
    if (!ok) return;

    try {
        const response = await fetch(`${API_BASE}/admin/sections/delete?id=${id}`, {
            method: "DELETE"
        });

        const result = await response.json();

        if (!result.success) {
            showSectionMessage(result.message || "Xóa thất bại", "danger");
            return;
        }

        showSectionMessage("Xóa thành công", "success");
        loadSections();
    } catch (error) {
        console.error(error);
        showSectionMessage("Lỗi kết nối server", "danger");
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("sectionForm");

    if (!form) return;

    form.addEventListener("submit", async function (e) {
        e.preventDefault();

        const id = document.getElementById("sectionId").value;
        const fileInput = document.getElementById("sectionImageFile");
        
        const formData = new FormData();
        formData.append("name", document.getElementById("sectionName").value.trim());
        formData.append("status", document.getElementById("sectionStatus").value);
        formData.append("description", document.getElementById("sectionDesc").value.trim());
        
        if (fileInput.files && fileInput.files.length > 0) {
            formData.append("image", fileInput.files[0]);
        }

        const isUpdate = id !== "";
        const url = isUpdate
            ? `${API_BASE}/admin/sections/update?id=${id}`
            : `${API_BASE}/admin/sections`;

        try {
            const response = await fetch(url, {
                method: "POST", // We use POST for both create and update because of FormData multipart
                body: formData
            });

            const result = await response.json();

            if (!result.success) {
                showSectionMessage(result.message || "Lưu thất bại", "danger");
                return;
            }

            showSectionMessage(
                isUpdate ? "Cập nhật thành công" : "Thêm mới thành công",
                "success"
            );

            resetSectionForm();
            loadSections();
        } catch (error) {
            console.error(error);
            showSectionMessage("Lỗi kết nối server", "danger");
        }
    });

    const searchInput = document.getElementById("sectionSearchInput");
    if (searchInput) {
        searchInput.addEventListener("keyup", searchSections);
    }
});

function resetSectionForm() {
    document.getElementById("sectionForm").reset();
    document.getElementById("sectionId").value = "";
    document.getElementById("sectionFormTitle").innerText = "Thêm Danh mục";
    document.getElementById("submitSectionBtn").innerText = "Thêm Danh mục";
    showSectionMessage("", "");
}

function showSectionMessage(message, type) {
    const messageEl = document.getElementById("sectionMessage");
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
