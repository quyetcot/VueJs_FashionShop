// i18n.js
import { createI18n } from 'vue-i18n'

// Định nghĩa các ngôn ngữ hỗ trợ
const messages = {
  vi: {
    global: {
      hello: 'Xin chào'
    },
    admin: {
      dashboard: 'Thống kê',
      categories: 'Danh Mục',
      product: 'Sản Phẩm',
      voucher:'Mã Giảm Giá',

      category: {
        id: 'ID',
        name: 'Tên Danh Mục',
        image: 'Ảnh'
      },
      action: 'Thao tác',
      add: 'Thêm mới',
      add_category: 'Thêm Mới Danh Mục'
    },

    auth: {
      login: 'Đăng nhập',
      account: 'Tài khoản',
      email: 'Email',
      password: 'Mật khẩu',
      forgot_password: 'Quên mật khẩu?',
      sign_up_now: 'Đăng ký ngay',
      login_with_google: 'Đăng nhập bằng Google',
      logout: 'Đăng xuất'
    },
    placeholder: {
      email: 'Nhập Email',
      password: 'Nhập mật khẩu',
      search: 'Tìm kiếm'
    },
    message: {
      required: 'Vui lòng nhập thông tin',
      incorrect_format: 'Định dạng không đúng',
      password_min_length: 'Mật khẩu phải có ít nhất 8 ký tự',
      password_uppercase: 'Mật khẩu phải có ít nhất một chữ hoa',
      password_lowercase: 'Mật khẩu phải có ít nhất một chữ thường',
      password_number: 'Mật khẩu phải có ít nhất một số',
      password_special_char: 'Mật khẩu phải có ít nhất một ký tự đặc biệt',

      // Thông báo cho tên danh mục
      category_name_min_length: 'Tên danh mục phải có ít nhất 3 ký tự.',
      category_name_max_length: 'Tên danh mục không được dài quá 50 ký tự.',
      category_name_invalid_characters: 'Tên danh mục chỉ được chứa chữ cái, số và khoảng trắng.'
    }
  },
  en: {
    global: {
      hello: 'Hello'
    },
    auth: {
      login: 'Login',
      account: 'Account',
      email: 'Email',
      password: 'Password',
      forgot_password: 'Forgot Password?',
      sign_up_now: 'Sign up now',
      login_with_google: 'Login with Google'
    },
    placeholder: {
      email: 'Type Email',
      password: 'Type password'
    },
    message: {
      required: 'This field is required',
      incorrect_format: 'The format is incorrect',
      password_min_length: 'Password must be at least 8 characters long',
      password_uppercase: 'Password must contain at least one uppercase letter',
      password_lowercase: 'Password must contain at least one lowercase letter',
      password_number: 'Password must contain at least one number',
      password_special_char:
        'Password must contain at least one special character'
    }
  }
}

const i18n = createI18n({
  locale: 'vi', // Mặc định ngôn ngữ là tiếng Việt
  messages
})

export default i18n
