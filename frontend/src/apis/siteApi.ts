import axios from "axios";

const API_BASE = "http://localhost:8000/api";

// Public: get site settings (optionally by group)
export const getSiteSettingsApi = async (group?: string) => {
  const res = await axios.get(`${API_BASE}/settings`, {
    params: group ? { group } : {},
  });
  return res.data;
};

// Public: get all categories (flat)
export const getCategoriesApi = async () => {
  const res = await axios.get(`${API_BASE}/categories`);
  return res.data;
};

// Public: get category tree (nested)
export const getCategoryTreeApi = async () => {
  const res = await axios.get(`${API_BASE}/categories/tree`);
  return res.data;
};

// Public: get products by category
export const getProductsByCategoryApi = async (categoryId: number, page = 1, limit = 12) => {
  const res = await axios.get(`${API_BASE}/categories/products`, {
    params: { id: categoryId, page, limit },
  });
  return res.data;
};

// Public: get active flash sales with products
export const getFlashSalesApi = async () => {
  const res = await axios.get(`${API_BASE}/flash-sales`);
  return res.data;
};

// Public: get active product sections
export const getSectionsApi = async () => {
  const res = await axios.get(`${API_BASE}/sections`);
  return res.data;
};

// Upload avatar
export const uploadAvatarApi = async (userId: number, file: File) => {
  const formData = new FormData();
  formData.append("user_id", String(userId));
  formData.append("avatar", file);
  const res = await axios.post(`${API_BASE}/customer/upload-avatar`, formData, {
    headers: { "Content-Type": "multipart/form-data" },
  });
  return res.data;
};
