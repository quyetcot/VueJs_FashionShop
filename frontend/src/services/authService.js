import apiWeb from "./apiWeb";
import apis from "./apis";

const authService = {
  async login(email, password) {
    try {
      const response = await apiWeb.post(apis.login, { email, password });
      console.log(response);
      localStorage.setItem("authToken", response.data.token);
      return response.data.data;
    } catch (error) {
      console.error("Login Error:", error.response?.data || error.message);
      throw error.response?.data || { message: "Login failed" }; // Ném lỗi để component xử lý
    }
  },
};
export default authService;
