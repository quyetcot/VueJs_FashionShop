import i18n from './locales/i18n' // Import i18n từ file cấu hình
const { t } = i18n.global // Lấy hàm t từ i18n.global

export const constants = {
  email: [
    v => !!v || t('message.required'), // Kiểm tra trường email không rỗng
    v => /.+@.+\..+/.test(v) || t('message.incorrect_format') // Kiểm tra định dạng email
  ],
  password: [
    v => !!v || t('message.required'), // Kiểm tra trường mật khẩu không rỗng
    v => v.length >= 8 || t('message.password_min_length'), // Kiểm tra mật khẩu có tối thiểu 8 ký tự
    v => /[A-Z]/.test(v) || t('message.password_uppercase'), // Kiểm tra có chữ hoa
    v => /[a-z]/.test(v) || t('message.password_lowercase'), // Kiểm tra có chữ thường
    v => /[0-9]/.test(v) || t('message.password_number'), // Kiểm tra có số
    v => /[!@#$%^&*(),.?":{}|<>]/.test(v) || t('message.password_special_char') // Kiểm tra có ký tự đặc biệt
  ],
  // Rule cho tên danh mục
  categoryName: [
    v => !!v || t('message.required'), // Kiểm tra trường tên danh mục không rỗng
    v => v.length >= 3 || t('message.category_name_min_length'), // Kiểm tra tên có tối thiểu 3 ký tự
    v => v.length <= 50 || t('message.category_name_max_length'), // Kiểm tra tên có tối đa 50 ký tự
    v =>
      /^[a-zA-Z0-9 ]*$/.test(v) || t('message.category_name_invalid_characters') // Kiểm tra không có ký tự đặc biệt
  ]
}

export default constants
