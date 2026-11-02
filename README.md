# CF7 chống spam

Mu-plugin chặn spam cho Contact Form 7 mà không cần cài thêm plugin hay dịch vụ bên ngoài.

## Vấn đề

- **Antispam Bee không bảo vệ form liên hệ.** Plugin này chỉ lọc bình luận và trackback, nên không can thiệp vào Contact Form 7.
- **Từ Contact Form 7 6.2, filter `wpcf7_is_tel` không còn chạy khi form được gửi.** Code cũ giới hạn số điện thoại Việt Nam vẫn nằm đó nhưng mất tác dụng, và website không báo lỗi gì.

## Plugin làm gì

Áp dụng cho mọi form Contact Form 7 trên website:

1. **Kiểm tra số di động Việt Nam** qua hook `wpcf7_validate_tel` và `wpcf7_validate_tel*`. Nhập sai sẽ có thông báo ngay dưới ô điện thoại.
2. **Bẫy bot** gồm một ô honeypot ẩn và một token chỉ được điền khi có thao tác thật trên form. Lần gửi bị chặn được đánh dấu spam và không gửi email.

Các lần bị chặn bởi bẫy bot được ghi vào log lỗi với tiền tố `[cf7-chong-spam]`. Các lần sai số điện thoại không được ghi log.

## Yêu cầu

- WordPress 6.1 trở lên
- Contact Form 7
- Ô điện thoại trong form phải là thẻ `[tel]` hoặc `[tel*]`

Đã chạy thử trên Contact Form 7 6.2.4.1 và PHP 8.4. Với bản 6.1.x, tính tương thích mới được đối chiếu qua source code.

## Cài đặt

1. Chép `cf7-chong-spam.php` vào `wp-content/mu-plugins/`. Nếu chưa có thư mục này thì tạo mới.
2. Đổi `cf7vn_hp` và `cf7vn_tk` thành tên riêng của website, thay ở tất cả các chỗ xuất hiện. Tránh các tên như `website` hay `email2` vì trình duyệt có thể tự điền vào ô ẩn.
3. **Xóa mọi lớp cache:** plugin cache, cache máy chủ (Nginx, LiteSpeed, Varnish) và CDN nếu có cache HTML. Trang cũ trong cache chưa có token, nên nếu bỏ qua bước này, khách thật gửi form sẽ bị chặn nhầm.
4. Mở trang có form ở tab ẩn danh, xem mã nguồn và tìm tên trường token để chắc chắn trang đã được cập nhật.

## Kiểm tra

| Cách thử | Kết quả đúng |
| --- | --- |
| Điền số `+14155550100` | Báo lỗi ngay dưới ô điện thoại |
| Điền số di động Việt Nam | Gửi thành công và email về hộp thư |
| Gửi ở form trong popup hoặc trang sản phẩm, nếu có | Gửi thành công và email về hộp thư |

## Giới hạn

- Số quốc tế và số cố định như 028 bị từ chối. Nếu có khách nước ngoài, hãy bỏ hai dòng `add_filter` của `cf7vn_validate_tel` và chỉ giữ phần bẫy bot.
- Trình duyệt tắt JavaScript không gửi được form.
- Người thật điền số Việt Nam giả vẫn có thể lọt qua.

## Giấy phép

GPL-2.0-or-later
