import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { adminLoginApi } from "@/apis/authApi";
import { useUserStore } from "@/store/userStore";

function AdminLogin() {
  const navigate = useNavigate();
  const login = useUserStore((s) => s.login);

  const [identifier, setIdentifier] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setError("");

    try {
      const data = await adminLoginApi({
        identifier,
        password,
      });

      if (data.success) {
        login(data.data);
        navigate("/admin");
      } else {
        setError(data.message || "Admin login failed");
      }
    } catch (err: any) {
      setError(err.response?.data?.message || "Could not connect to the server");
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-900 px-4">
      <form onSubmit={handleLogin} className="w-full max-w-md bg-white p-8 rounded-xl shadow">
        <h1 className="text-2xl font-bold text-center mb-6">Admin Login</h1>

        <input
          className="w-full border rounded-lg px-4 py-2 mb-4"
          placeholder="Email or phone number"
          value={identifier}
          onChange={(e) => setIdentifier(e.target.value)}
        />

        <input
          className="w-full border rounded-lg px-4 py-2 mb-4"
          type="password"
          placeholder="Password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
        />

        {error && <p className="text-red-500 text-sm mb-4">{error}</p>}

        <button className="w-full bg-slate-900 text-white rounded-lg py-2">
          Admin Login
        </button>
      </form>
    </div>
  );
}

export default AdminLogin;
