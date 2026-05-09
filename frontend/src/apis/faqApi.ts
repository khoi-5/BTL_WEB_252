import axios from "axios";

const API_BASE = "http://localhost:8000/api";

export type FaqItem = {
  faq_id: number;
  question: string;
  answer: string;
  category: string | null;
};

// Public: get active FAQs (optionally filter by category)
export const getFaqsApi = async (category?: string): Promise<{ success: boolean; data: FaqItem[] }> => {
  const res = await axios.get(`${API_BASE}/faqs`, {
    params: category ? { category } : {},
  });
  return res.data;
};

// Public: get FAQ categories
export const getFaqCategoriesApi = async (): Promise<{ success: boolean; data: string[] }> => {
  const res = await axios.get(`${API_BASE}/faqs/categories`);
  return res.data;
};
