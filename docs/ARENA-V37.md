# StreakFit Arena V3.7 — Glass & Motion

## Phạm vi
Bản vá giao diện dựa trên ZIP V3.6 Live Atmosphere, không phải repository đầy đủ. Không sửa database, migration, cấu hình ứng dụng, API, bộ tính điểm, xác thực hoặc logic GPS. Các view PHP chỉ đổi cách trình bày và nhãn; tên trường, action, CSRF và route cũ được giữ nguyên.

## Cài đặt
1. Sao lưu hoặc commit công việc hiện có. So sánh những file cùng tên nếu nhóm đã cập nhật kể từ V3.6.
2. Giải nén ZIP, gộp `app/`, `public/`, `docs/` vào thư mục gốc dự án. Không xóa các thư mục gốc. Không ghi đè `.env`.
3. Chạy ứng dụng theo cấu hình hiện có; ví dụ `php -S 127.0.0.1:8000 -t public public/index.php` với APP_URL phù hợp.
4. Ctrl+F5 và kiểm tra Dashboard, Cộng đồng, Bảng vàng, Lịch tập, Đăng nhập, Đăng ký, Quên mật khẩu, Đặt mật khẩu mới, Admin.

## Thay đổi
- Hệ font hệ thống có hỗ trợ tiếng Việt, giảm tracking âm, tăng line-height và cỡ chữ ở nhãn, form và bài đăng. Không tải font từ CDN.
- Việt hóa các nhãn Arena, sửa phiên bản hiển thị trong menu, đổi ngày tiếng Anh sang T2–CN ở danh sách lịch tuần; ghi rõ liên kết đến lịch tuần tới.
- Glass lens cho dock, cập nhật theo tab hiện tại qua sự kiện `sf:arena-view`, resize và thay đổi class. Tab phụ dùng cùng màu nhận diện, cyan trong Cộng đồng. Không thêm màn che chuyển trang.
- Tương tác nút, phản hồi focus và vùng bấm lớn hơn; giảm thời gian chuyển tab nội bộ xuống 190 ms.
- Login/Register: bố cục hai vùng, lớp ánh sáng chéo, animation xuất hiện riêng theo mode; liên kết native vẫn đi đúng route. Nút hiện/ẩn mật khẩu chỉ thao tác cục bộ và tự ẩn khi gửi form. Không lưu mật khẩu hoặc thay đổi cơ chế xác thực.
- Respect prefers-reduced-motion; fallback CSS và link/form nguyên bản khi không có JS. Không thêm framework, dependency, API hoặc asset tải từ bên ngoài.

## Kiểm tra đã thực hiện
- PHP lint mọi view sửa; Node syntax check hai JS sửa/thêm.
- Chromium: render HTML từ PHP fixture, nạp asset thật từ source vào trang kiểm thử; 1440×960, 390×844 và 320×740. Kiểm tra chọn Cộng đồng cập nhật dock và aria-current, không tràn ngang, hiện/ẩn mật khẩu, các trường và action đăng ký, không phát hiện JS error trong các ca đã chạy.
- Chạy lại `php tests/arena_v33_rank.php`, `php tests/arena_final.php`, `node tests/arena_atmosphere.js`: đạt.
- Kiểm thử giao diện dùng dữ liệu mẫu, chế độ giảm chuyển động, không có MySQL. Không kiểm thử gửi form vào hệ thống thực, GPS, PayOS, Gemini, nhiều tài khoản, Safari/iOS hoặc mọi trang Admin. Không xác nhận các commit mới hơn V3.6 trên repository nhóm.
