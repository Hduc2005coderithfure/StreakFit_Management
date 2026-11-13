# Kết quả kiểm thử User/Admin, GPS và thanh toán

Môi trường: PHP 8.2 trên XAMPP, MariaDB local, database riêng streakfit_test và streakfit_test_seed.

| Bộ kiểm thử | Kết quả |
|---|---|
| tests/run.php: nghiệp vụ hiện có | 46 đạt, 0 lỗi |
| tests/features.php: nhóm, GPS, check-in/check-out, thanh toán | 42 đạt, 0 lỗi |
| tests/http.php: đăng ký/đăng nhập, trang User/Admin, biểu mẫu, GPS và QR ngân hàng | 30 đạt, 0 lỗi |
| tests/seed_import.php: schema, dữ liệu mẫu và migrations | Đạt |
| PHP lint và JavaScript route.js syntax | Đạt |
| tests/route-metrics.php: khoảng cách chuẩn, dừng, mất tín hiệu, tốc độ bất thường, nhiễu GPS | 7 đạt |
| tests/route-metrics.js: tính quãng đường trên giao diện | 8 đạt |
| tests/route-browser.js: mô phỏng cấp quyền, GPS, lưu máy chủ, dừng/tiếp tục | 9 đạt |

Quãng đường và thời gian GPS được lưu ở bảng workouts, đi kèm môn thể thao. Đã kiểm tra tọa độ mẫu cách nhau khoảng 111,19 m; kiểm tra ghi kilomet vào database và đặt lại về 0 khi xóa GPS. Mô phỏng trình duyệt không thay thế thử chạy ngoài trời.

Các lỗi đã sửa trong quá trình rà soát: bộ test nhập schema cũ thiếu migrations; lời gọi test Admin thiếu tham số phân quyền; fixture render Admin không khớp view hiện tại (thay bằng kiểm thử HTTP); tài khoản bị khóa còn phiên đăng nhập có thể gửi một số POST; lịch sử buổi tập thiếu ID cần cho liên kết GPS.

GPS đã kiểm tra lưu CSDL, gửi lại không trùng điểm, CSRF, quyền xem/ghi/xóa, giới hạn tọa độ và thời gian. Check-out 1.801 giây đã được kiểm tra qua service thực, xác nhận bản ghi valid và tăng số buổi; không cần chờ đồng hồ 30 phút trong test.

Thanh toán dùng gateway giả trong test, đồng thời đối chiếu HMAC với vector mẫu chính thức payOS. Đã kiểm tra chữ ký giả, sự kiện lặp, tiền chưa thực nhận, chuyển đúng tiền, sai số tiền, chỉ tạo một khoản đóng và chặn rời quỹ khi đang thanh toán.

QR ngân hàng được kiểm tra thêm: Admin lưu tài khoản nhận tiền, User tạo QR và mã nội dung riêng, tạo lại không tạo thêm yêu cầu, không xác nhận khi chưa đối chiếu, từ chối số tiền/nội dung sai, ghi sổ một lần khi Admin xác nhận đúng. Kiểm thử HTTP đi qua cả màn hình User và Admin.

Chưa kiểm tra với tiền thật, tài khoản ngân hàng thật hoặc Google Maps key thật vì chưa có cấu hình. Chưa chạy thử GPS trên điện thoại ngoài trời, quyền vị trí thực tế và khóa màn hình. Đọc docs/sports-gps-payments.md để bật dịch vụ và kiểm tra thực tế. Đây là kết quả các ca nêu trên, không phải chứng nhận toàn bộ hệ thống không còn lỗi.
