export const constants = {
  email: [
    v => !!v || 'Vui lòng nhập email', // Kiểm tra trường email không rỗng
    v => /.+@.+\..+/.test(v) || 'Email không đúng định dạng' // Kiểm tra định dạng email
  ],
  password: [
    v => !!v || 'Vui lòng nhập mật khẩu', // Kiểm tra trường mật khẩu không rỗng
    v => v.length >= 8 || 'Mật khẩu tối thiểu 8 kí tự', // Kiểm tra mật khẩu có tối thiểu 8 ký tự
    v => /[A-Z]/.test(v) || 'Mật khẩu phải có ít nhất một chữ hoa', // Kiểm tra có chữ hoa
    v => /[a-z]/.test(v) || 'Mật khẩu phải có ít nhất một chữ thường', // Kiểm tra có chữ thường
    v => /[0-9]/.test(v) || 'Mật khẩu phải có ít nhất một số', // Kiểm tra có số
    v =>
      /[!@#$%^&*(),.?":{}|<>]/.test(v) ||
      'Mật khẩu phải có ít nhất một ký tự đặc biệt' // Kiểm tra có ký tự đặc biệt
  ]
}

export default constants
