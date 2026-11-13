# Nhóm thể thao, GPS và nộp quỹ

## Chức năng

- User: tạo/sửa/đóng nhóm mình quản lý, lọc theo môn, tham gia/rời nhóm; điểm hẹn, giờ hẹn và mở vị trí trên Google Maps.
- Admin: quản lý nhóm bằng quyền groups.manage; xem đơn chuyển khoản bằng fund.view, kiểm tra với payOS bằng fund.manage.
- Khi check-in, chọn môn chạy bộ, đạp xe, đi bộ hoặc môn khác. Trong buổi tập đang mở: chọn **Theo dõi đường tập GPS → Bật theo dõi GPS**. Cấp quyền vị trí và giữ trang mở. Giao diện hiển thị km, tốc độ trung bình và nhịp độ chạy; lịch sử lưu môn và số km của từng buổi.
- **Dừng theo dõi** tạm ngừng GPS, không kết thúc buổi tập. Về Check-out để kết thúc. Các điểm chưa gửi được giữ trong sessionStorage của tab; ứng dụng sẽ thử gửi lại khi mạng phục hồi. Không đóng tab khi còn báo chưa lưu.
- Khi chưa có Google Maps key, sơ đồ đường GPS và số km vẫn dùng được. Google Maps key bật phần nền bản đồ.
- GPS được lưu vào bảng workout_route_points, riêng theo buổi tập. Người khác không xem được; Admin cần quyền workouts.view. Quãng đường là ước tính, không cộng điểm theo km.
- Nộp quỹ: Admin vào **Đối soát chuyển khoản QR**, nhập BIN ngân hàng, số tài khoản và tên chủ tài khoản. User tham gia quỹ trong Hồ sơ → Quỹ, rồi mở **Nộp quỹ qua QR**. Mỗi thành viên có QR VietQR ghi sẵn số tiền và mã nội dung riêng. QR này tạo lệnh chuyển tiền thật đến tài khoản Admin đã nhập; việc hiển thị QR không xác nhận tiền đã vào.
- Admin vào trang Quản lý quỹ và chọn **Xác nhận tham gia & đã đóng quỹ**. Hệ thống ghi sổ khoản đóng, cập nhật yêu cầu QR sang đã thanh toán, thông báo User và gửi email; không cần nhập thông tin sao kê.
- Nếu có khóa payOS, User cũng có lựa chọn thanh toán payOS tự động đối soát. Không mở đồng thời hai đơn cho cùng khoản nộp.
- Chỉ ghi một khoản đóng quỹ sau khi xác minh chữ ký và truy vấn máy chủ payOS: đúng mã đơn, mã link, số tiền và trạng thái PAID. Return URL trên trình duyệt không tự xác nhận tiền. Sai số tiền/đóng trùng được đưa vào trạng thái cần đối soát.

## Cấu hình thực tế

1. Tạo .env từ .env.example. Không commit khóa bí mật.
2. Google Cloud: bật **Maps JavaScript API**, cấu hình billing theo tài khoản Google, tạo khóa và giới hạn HTTP referrer theo tên miền ứng dụng; giới hạn API cho Maps JavaScript API. Điền GOOGLE_MAPS_API_KEY.
3. Đối với QR ngân hàng do Admin quản lý, không cần khóa API: Admin nhập tài khoản nhận tiền trên trang Đối soát chuyển khoản QR. Kiểm tra lại QR bằng ứng dụng ngân hàng trước khi cho User sử dụng.
4. Tùy chọn tự động đối soát: tạo kênh thanh toán payOS và liên kết tài khoản ngân hàng nhận quỹ. Điền PAYOS_CLIENT_ID, PAYOS_API_KEY, PAYOS_CHECKSUM_KEY vào .env trên máy chủ.
5. Với payOS, đặt APP_URL thành URL HTTPS thực tế (kèm thư mục con nếu có). Đăng ký webhook trên payOS: APP_URL/api/payos/webhook. Máy chủ payOS phải truy cập được URL này; localhost đơn thuần không nhận webhook từ bên ngoài.
6. Mở nộp quỹ bằng tài khoản tham gia quỹ và tạo QR. Admin xác nhận thành viên và khoản đóng tại trang Quản lý quỹ; User nhận thông báo sau khi Admin xác nhận. payOS vẫn có thể tự cập nhật trạng thái khi được cấu hình.

Tài khoản ngân hàng thực tế chưa được cung cấp nên Admin phải nhập thông tin nhận tiền; chưa có giao dịch tiền thật được thực hiện trong phiên triển khai. Bộ test dùng thông tin ngân hàng mẫu và gateway giả, không gửi tiền. GPS trên thiết bị cần HTTPS (localhost được trình duyệt cho phép); trình duyệt có thể tạm dừng khi màn hình khóa, vì vậy đây là theo dõi khi trang còn hoạt động, chưa phải ứng dụng GPS chạy nền.

## Database

- Database local hiện tại đã được bổ sung migration 2026_11_13_sports_payments.sql, 2026_11_14_bank_transfer.sql và 2026_11_15_workout_distance.sql.
- Cài mới: import database/streakfit.sql rồi chạy lần lượt các migration 2026_11_11_feature_expansion.sql, 2026_11_12_admin_permissions.sql, 2026_11_13_sports_payments.sql, 2026_11_14_bank_transfer.sql. File SQL gốc hiện là bản dump cũ, cần các migration.
- Database đã có tính năng/phân quyền trước đây: chỉ chạy migration mới. Không chạy lại feature expansion trên database đã có các cột/ràng buộc đó.
- Sau các migration cài mới nêu trên, chạy 2026_11_15_workout_distance.sql để thêm môn thể thao, km và thời gian GPS.

## Kiểm thử

Chạy theo thứ tự trên XAMPP:

    C:\xampp\php\php.exe tests\run.php
    C:\xampp\php\php.exe tests\features.php
    C:\xampp\php\php.exe tests\http.php
    C:\xampp\php\php.exe tests\seed_import.php

Các script chỉ sử dụng database test. Bộ HTTP khởi động server riêng tại 127.0.0.1:18763 và tắt sau khi chạy.

Tình huống cần thử trên điện thoại trước khi đưa vào vận hành: từ chối quyền GPS, mất mạng, khóa màn hình, khôi phục trang, check-out, xem lại bản đồ; hủy QR, chuyển thiếu/thừa, mất webhook và đối soát lại. Khoản cần đối soát tiền thừa/đóng trùng phải được xử lý với ngân hàng/payOS; ứng dụng chưa tự hoàn tiền.

Tài liệu chính thức: [VietQR Quick Link](https://www.vietqr.io/qr-dong), [payOS API](https://payos.vn/docs/api/), [xác minh chữ ký](https://payos.vn/docs/tich-hop-webhook/kiem-tra-du-lieu-voi-signature/), [Maps JavaScript API](https://developers.google.com/maps/documentation/javascript/load-maps-js-api), [Geolocation](https://www.w3.org/TR/geolocation/).
