import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { customerRegisterApi } from "@/apis/authApi";
import { useToast } from "@/hooks/useToast";
import {
  isRequired,
  isValidVietnamPhone,
  isValidPassword,
  PASSWORD_MIN_LENGTH,
} from "@/lib/validators";

function Register() {
  const navigate = useNavigate();
  const { toast, showToast } = useToast();

  const [form, setForm] = useState({
    full_name: "",
    email: "",
    phone: "",
    password: "",
    shipping_address: "",
    receiver_name: "",
    receiver_phone: "",
  });

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setForm((prev) => ({
      ...prev,
      [e.target.name]: e.target.value,
    }));
  };

  const handleRegister = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();

    const payload = {
      full_name: form.full_name.trim(),
      email: form.email.trim(),
      phone: form.phone.trim(),
      password: form.password.trim(),
      shipping_address: form.shipping_address.trim(),
      receiver_name: form.receiver_name.trim(),
      receiver_phone: form.receiver_phone.trim(),
    };

    if (
      !isRequired(payload.full_name) ||
      !isRequired(payload.email) ||
      !isRequired(payload.phone) ||
      !isRequired(payload.password) ||
      !isRequired(payload.shipping_address)
    ) {
      showToast("Please fill in all required fields.", "error");
      return;
    }

    if (!isValidVietnamPhone(payload.phone)) {
      showToast("Account phone number must have 10 digits and start with 0.", "error");
      return;
    }

    if (
      isRequired(payload.receiver_phone) &&
      !isValidVietnamPhone(payload.receiver_phone)
    ) {
      showToast("Receiver phone number must have 10 digits and start with 0.", "error");
      return;
    }

    if (!isValidPassword(payload.password)) {
      showToast(
        `Password must be at least ${PASSWORD_MIN_LENGTH} characters.`,
        "error"
      );
      return;
    }

    try {
      const data = await customerRegisterApi({
        full_name: payload.full_name,
        email: payload.email,
        phone: payload.phone,
        password: payload.password,
        shipping_address: payload.shipping_address,
        receiver_name: payload.receiver_name || payload.full_name,
        receiver_phone: payload.receiver_phone || payload.phone,
      });

      if (!data.success) {
        showToast(data.message || "Registration failed.", "error");
        return;
      }

      showToast("Registration successful. Please log in.", "success");

      setTimeout(() => {
        navigate("/login");
      }, 800);
    } catch (err: any) {
      showToast(
        err.response?.data?.message ||
          err.message ||
          "Could not connect to the server.",
        "error"
      );
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-100 px-4 py-8">
      <form
        onSubmit={handleRegister}
        className="w-full max-w-md bg-white p-8 rounded-xl shadow"
      >
        <h1 className="text-2xl font-bold text-center mb-6">Register</h1>

        <input
          name="full_name"
          className="w-full border rounded-lg px-4 py-2 mb-3"
          type="text"
          placeholder="Full name"
          value={form.full_name}
          onChange={handleChange}
        />

        <input
          name="email"
          className="w-full border rounded-lg px-4 py-2 mb-3"
          type="email"
          placeholder="Email"
          value={form.email}
          onChange={handleChange}
        />

        <input
          name="phone"
          className="w-full border rounded-lg px-4 py-2 mb-3"
          type="text"
          placeholder="Account phone number"
          value={form.phone}
          onChange={handleChange}
        />

        <input
          name="password"
          className="w-full border rounded-lg px-4 py-2 mb-3"
          type="password"
          placeholder="Password"
          value={form.password}
          onChange={handleChange}
        />

        <input
          name="shipping_address"
          className="w-full border rounded-lg px-4 py-2 mb-3"
          type="text"
          placeholder="Shipping address"
          value={form.shipping_address}
          onChange={handleChange}
        />

        <input
          name="receiver_name"
          className="w-full border rounded-lg px-4 py-2 mb-3"
          type="text"
          placeholder="Receiver name (optional)"
          value={form.receiver_name}
          onChange={handleChange}
        />

        <input
          name="receiver_phone"
          className="w-full border rounded-lg px-4 py-2 mb-4"
          type="text"
          placeholder="Receiver phone (optional)"
          value={form.receiver_phone}
          onChange={handleChange}
        />

        <button
          type="submit"
          className="w-full bg-slate-900 text-white rounded-lg py-2"
        >
          Register
        </button>

        <button
          type="button"
          onClick={() => navigate("/login")}
          className="w-full mt-4 text-blue-600 text-sm"
        >
          Already have an account? Login
        </button>
      </form>

      {toast.show && (
        <div className={`toast ${toast.type} show`}>
          {toast.message}
        </div>
      )}
    </div>
  );
}

export default Register;
