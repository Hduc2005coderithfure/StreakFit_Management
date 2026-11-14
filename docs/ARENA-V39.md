# StreakFit Arena V3.9 — Workspace Edition

## Cài đặt
Áp dụng trên V3.8 Health & Community. Copy gộp các thư mục `app`, `public`, `docs` vào source. Không xóa thư mục gốc. Nếu nhóm đã thay đổi các file trùng tên, so sánh trước khi ghi đè. Không cần thêm migration, API key hoặc sửa `.env`.

## Bố cục
- Desktop từ 1100 CSS px: sidebar có nhóm Luyện tập, Cộng đồng, Cá nhân, mục quản trị nếu được phép. Có thanh XP lấy từ ngưỡng rank hiện hữu. Dock dưới chỉ dành cho màn hình nhỏ hơn.
- `/dashboard?view=overview`: hai số liệu buổi đăng ký và buổi đã tập (tuần hiện tại), 3D core với tỷ lệ mục tiêu tuần, một CTA. Mở form camera tại chỗ; người dùng chủ động mở camera, chụp ảnh và xác nhận. Nếu đã hoàn thành hôm nay, CTA chuyển sang lịch sử. Cinematic vẫn có trên lần vào Tổng quan đầu phiên và có thể xem lại, nhưng chỉ còn hai số liệu chính và nút tập.
- `/dashboard?view=insights`: biểu đồ thời lượng, trung bình/buổi, số ngày tập, cân nặng/BMI/chiều cao, thời tiết và lịch tuần. Giữ quyền riêng tư của số đo. Dữ liệu từ truy vấn và bảng hiện có của V3.8.
- `/dashboard?view=community`: ảnh, bộ lọc, timeline, thành viên đang tập. Tiêu đề ngắn, không thay đổi cơ chế tương tác.
- Tất cả các route backend cũ giữ nguyên. Nghỉ tập vẫn là luồng giải trình/biểu quyết, không tự miễn tập; Quỹ nhóm giữ quy tắc hiện tại. Không có thay đổi logic XP, lịch, thanh toán, GPS hoặc API AI.

## Kiểm thử
PHP lint và JS syntax; Chromium dùng fixture PHP, không dùng MySQL thật. Desktop 1440/1100 và mobile 390/320: kiểm tra sidebar/dock, tab, 3D core, form start/finish, trạng thái đã tập, chart, timeline, ảnh và tràn ngang. Kiểm tra route Thống kê khi JS tắt. Camera vật lý, PayOS, database thật và các thay đổi mới của nhóm chưa kiểm thử. Sidebar chỉ áp dụng member dashboard dùng chung; trang nhóm thể thao/đăng nhập/admin độc lập giữ layout hiện tại.
