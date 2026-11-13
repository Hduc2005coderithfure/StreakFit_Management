# StreakFit Arena V3.6 — Live Atmosphere

Bản tích lũy V3.5 + thời tiết theo vị trí và bốn chế độ giao diện theo thời gian. Không tích hợp Strava. Không cần migration, API key, Node.js trên server hoặc thay đổi .env.

## Cài đặt

1. Giải nén gói V3.6 vào thư mục gốc dự án theo kiểu gộp thư mục, không xóa source gốc.
2. Nếu từng cài V3.4, dọn các file Strava theo `docs/ARENA-FINAL.md`. Không xóa database hoặc cấu hình cá nhân.
3. Chạy PHP như trước: `php -S 127.0.0.1:8000 -t public public/index.php`.
4. Mở `/dashboard`, bỏ qua cinematic nếu đang mở, nhấn **Bật thời tiết** và cho phép định vị. Trình duyệt cần HTTPS hoặc localhost/127.0.0.1. Địa chỉ IP LAN qua HTTP thường không hỗ trợ Geolocation.
5. Nút **Cập nhật** lấy vị trí mới và dữ liệu mới, nút **Tắt** dừng cập nhật, xóa cache thời tiết trong tab và lưu lựa chọn tắt.

## Hành vi

- Mặc định giao diện tự đổi theo giờ thiết bị; không gọi GPS hay API thời tiết trước khi người dùng chọn bật. Trang đăng nhập chỉ dùng đồng hồ, không định vị. Admin Center giữ giao diện quản trị cũ.
- Sau khi bật, lưu tùy chọn bật/tắt theo thành viên trên trình duyệt; dữ liệu thời tiết tạm thời lưu sessionStorage theo thành viên. Khi quyền geolocation đã được cấp, trang đang mở tự cập nhật khoảng 20 phút/lần; không tạo yêu cầu quyền mới trong nền. Nếu trình duyệt không hỗ trợ Permissions API, dùng nút Cập nhật thủ công. Không chạy nền sau khi đóng trang.
- Dùng `getCurrentPosition` với `enableHighAccuracy`, timeout 12 giây, cache vị trí tối đa 60 giây. Độ chính xác thực phụ thuộc thiết bị, trình duyệt, điều kiện GPS/Wi-Fi. Không thay đổi luồng `route.js` ghi đường tập GPS.
- Làm tròn vĩ độ/kinh độ 2 chữ số thập phân trước khi gửi HTTPS trực tiếp tới `api.open-meteo.com`. Không lưu tọa độ, không ghi database; bên cung cấp vẫn thấy vị trí gần đúng và IP kết nối. Không đưa thời tiết hay GPS vào bảng tin, rank, AI hoặc thanh toán.
- Dữ liệu lấy từ Open-Meteo Weather Forecast API: nhiệt độ, cảm nhận, độ ẩm, gió, mã WMO, giờ mặt trời mọc/lặn và múi giờ tự động. Đây là dữ liệu mô hình thời tiết, không phải cảm biến đo tại chỗ. Nguồn và thời điểm mô hình hiển thị rõ.
- Khi có dữ liệu ngày hiện tại, bình minh/hoàng hôn dùng cửa sổ ±45 phút quanh giờ mọc/lặn thực; nếu không, dùng mốc 06:00/18:00 theo đồng hồ thiết bị. Giao diện tự kiểm tra giờ mỗi 30 giây.
- Nếu API lỗi, giữ dữ liệu tạm tối đa 60 phút, ghi trạng thái lỗi/dữ liệu cũ; quá hạn thì ẩn nhiệt độ và hiệu ứng theo thời tiết. Chuyển theo giờ vẫn hoạt động. Request timeout 10 giây.
- Các kiểu thời tiết: quang, mây, sương, mưa, tuyết, dông (không nháy chớp); hoạt ảnh tạm dừng khi tab ẩn, tôn trọng prefers-reduced-motion. Không thay đổi màu rank theo thời tiết.

## Điều khoản API

Open-Meteo: https://open-meteo.com/en/docs ; https://open-meteo.com/en/terms . Free API chỉ cho mục đích phi thương mại, giới hạn dưới 10.000 call/ngày, dưới 5.000 call/giờ và dưới 600 call/phút theo điều khoản hiện hành; cần ghi nguồn CC BY 4.0. Nếu triển khai kinh doanh, chủ dự án cần chọn gói phù hợp hoặc tắt tính năng. Không cam kết độ chính xác hoặc uptime của nhà cung cấp.

## File mới và file sửa so với V3.5

- Sửa `app/Views/dashboard/index.php`, `app/Views/community/index.php`, `app/Views/auth/login.php`.
- Thêm `app/Views/components/arena-atmosphere.php`, `public/assets/css/arena-atmosphere.css`, `public/assets/js/arena-atmosphere.js`, `tests/arena_atmosphere.js`, tài liệu này.

## Kiểm thử

- `php -l` cho các view đã sửa và component mới: PASS.
- `node --check public/assets/js/arena-atmosphere.js` và `node tests/arena_atmosphere.js`: PASS. Kiểm tra 4 pha, timezone, ngày, mã WMO, làm tròn GPS, giá trị không hợp lệ.
- Chromium render PHP fixture với API/GPS/đồng hồ/storage mô phỏng: PASS cho opt-in, không request trước khi bật, tọa độ làm tròn, mưa/hoàng hôn, ban đêm, từ chối định vị, lỗi API 503, nút tắt, Cộng đồng, rank, lịch 7 ngày, mobile không tràn ngang và không có lỗi JS trong bài kiểm thử.
- Chưa kiểm tra GPS vật lý, dữ liệu Open-Meteo trực tiếp từ máy người dùng, database MySQL thật và điều hướng mạng trình duyệt thật do môi trường kiểm thử chặn truy cập mạng. Ảnh preview dùng dữ liệu fixture, không phải số liệu thời tiết hiện tại.
