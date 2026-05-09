import axios from "axios";

const API_BASE = "http://localhost:8000/api";

// Public: submit contact form
export const submitContactApi = async (payload: {
  full_name: string;
  email: string;
  subject?: string;
  message: string;
}) => {
  const res = await axios.post(`${API_BASE}/contact`, payload);
  return res.data;
};
