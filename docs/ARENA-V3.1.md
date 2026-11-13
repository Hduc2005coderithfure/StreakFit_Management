# StreakFit Arena V3.1 — Floating Navigation

Yêu cầu: đã cài Arena V3 trên source gốc. Gói này chỉ chứa các file thay đổi so với V3, không phải dự án đầy đủ.

## Cài đặt
Giải nén ZIP, copy gộp (merge) thư mục app, public và docs vào thư mục gốc StreakFit; ghi đè file trùng. Không xóa các thư mục cũ. Không sửa .env, database, dependency hoặc cấu hình máy chủ. Refresh bằng Ctrl+F5.

## Thay đổi
- Giữ cinematic V3 (arena-entry.php và arena-3d.js không đổi).
- Bỏ page wipe và thời gian trì hoãn 240 ms, bỏ intro cũ trên các trang khác, bỏ fade-in toàn trang.
- Dock nổi 5 điểm đến, biểu tượng SVG, hover lift và spotlight. Tab hiện tại có viền vàng.
- Bảng tin: Tổng quan / Cộng đồng (cùng document, không reload) / Lịch tập (route hồ sơ).
- Bảng vàng: Rank 3D / Bảng vàng / Tòa án (cùng document, không reload); kỳ tuần/tháng/tích lũy vẫn truy vấn server.
- Cá nhân: 7 tab điều hướng tới section hiện có, cuộn ngang trên mobile; form xử lý theo backend hiện tại.
- Luyện tập: Check-in/out, Lịch sử và Lịch tập.
- Giữ tab Bảng tin hoặc Tòa án khi backend redirect sau thao tác POST; sessionStorage chỉ lưu UI trong phiên hiện tại.
- Không sửa nghiệp vụ backend. Các trang riêng vẫn dùng native navigation; không dùng SPA giả hay AJAX toàn trang.

## Kiểm tra đã chạy
PHP lint 6 file PHP; Node --check 2 file JS. Render PHP view với dữ liệu kiểm thử riêng; Chromium kiểm tra mở/bỏ qua cinematic, chuyển tab và lọc feed, mô phỏng lưu/khôi phục tab sau POST, chọn rank, chọn kỳ (render server), đường dẫn Tòa án, các section Cá nhân, Lịch sử, bố cục 1440×900 và 390×844; không phát hiện JS exception trong kịch bản kiểm tra.

Do chính sách trình duyệt thử nghiệm chặn điều hướng mạng, giao diện được kiểm tra bằng HTML từ PHP fixture và CSS/JS nhúng trực tiếp; History API và sessionStorage được mô phỏng. Chưa kiểm thử điều hướng trình duyệt thật, DB thật, camera hoặc PayOS. Fixture và dữ liệu thử không có trong ZIP. Cần kiểm tra thực tế trên local của bạn trước khi push Git.

Rollback: phục hồi các file trùng từ commit V3 trước khi cài. File docs này không ảnh hưởng runtime.
