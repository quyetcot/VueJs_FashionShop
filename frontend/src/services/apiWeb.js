import axios from "axios";

const apiWeb = axios.create({
  baseURL: process.env.VUE_APP_API_URL || "http://127.0.0.1:8000/api/v1",
  timeout: 10000,
  headers: {
    "Content-Type": "application/json",
  },
});

// Thêm interceptor để xử lý token (nếu có)
apiWeb.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem("authToken"); // Lấy token từ localStorage
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Thêm interceptor để xử lý lỗi
apiWeb.interceptors.response.use(
  (response) => response,
  (error) => {
    console.error("API Error:", error.response?.data || error.message);
    return Promise.reject(error);
  }
);

export default apiWeb;
