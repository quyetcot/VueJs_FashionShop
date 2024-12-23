// i18n.js
import { createI18n } from 'vue-i18n';

// Định nghĩa các ngôn ngữ hỗ trợ
const messages = {
  en: {
    global: {
      hello: 'Hello',
    },
    auth: {
      login: 'Login',
      account: 'Account',
      email: 'Email',
      password: 'Password',
      forgot_password: 'Forgot Password?',
      sign_up_now: 'Sign up now',
    },
    placeholder: {
      email: 'Type Email',
      password: 'Type password',
    },
    message: {
      required: 'This field is required',
      incorrect_format: 'The format is incorrect',
      password_min_length: 'Password must be at least 8 characters long',
      password_uppercase: 'Password must contain at least one uppercase letter',
      password_lowercase: 'Password must contain at least one lowercase letter',
      password_number: 'Password must contain at least one number',
      password_special_char: 'Password must contain at least one special character',
    },
  },
  vi: {
    global: {
      hello: 'Xin chào',
    },
    auth: {
      login: 'Đăng nhập',
      account: 'Tài khoản',
      email: 'Email',
      password: 'Mật khẩu',
      forgot_password: 'Quên mật khẩu?',
      sign_up_now: 'Đăng ký ngay',
    },
    placeholder: {
      email: 'Nhập Email',
      password: 'Nhập mật khẩu',
    },
    message: {
      required: 'Vui lòng nhập thông tin',
      incorrect_format: 'Định dạng không đúng',
      password_min_length: 'Mật khẩu phải có ít nhất 8 ký tự',
      password_uppercase: 'Mật khẩu phải có ít nhất một chữ hoa',
      password_lowercase: 'Mật khẩu phải có ít nhất một chữ thường',
      password_number: 'Mật khẩu phải có ít nhất một số',
      password_special_char: 'Mật khẩu phải có ít nhất một ký tự đặc biệt',
    },
  },
};

const i18n = createI18n({
  locale: 'vi', // Mặc định ngôn ngữ là tiếng Việt
  messages,
});

export default i18n;
