# StreakFit Arena V4.0 — Prism Workspace

Cập nhật từ V3.9 (đã cài V3.8/V3.7 và các bản trước). Chép gộp `app/`, `public/`, `docs/` vào dự án. Không xóa thư mục gốc, không sửa `.env`, không chạy migration. Nếu nhóm đã sửa view trùng tên, merge diff trước khi ghi đè.

## Thay đổi
- Sidebar desktop dùng các ô biểu tượng màu, 3 nhóm chức năng, trạng thái chọn theo route, hiệu ứng hover/ấn và chuyển tab. Thu gọn 236 px → 78 px; lưu tùy chọn trong localStorage nếu trình duyệt cho phép. Mobile giữ dock.
- Các trang nhóm thể thao, nộp quỹ và đường GPS sử dụng chung component sidebar, dock, icon; quyền và form hiện tại giữ nguyên. Trang admin giữ layout quản trị riêng. Không hiển thị số thông báo giả trên view độc lập chưa có dữ liệu đếm.
- Tổng quan giữ hai số buổi, CTA ghi nhận và core WebGL hiện tại. Cột thành viên trên desktop rộng lấy từ activityMembers sẵn có, không suy đoán trạng thái online. Dấu xanh chỉ biểu thị đang có buổi tập, không phải online.
- Thẻ nhóm thể thao có grid responsive, màu theo môn, vẫn dùng form GET/POST và CSRF cũ. Thời tiết trên các trang độc lập được thu vào details; không thay đổi quyền GPS hay API.
- Không thêm backend, SQL, dữ liệu cá nhân mới, API hoặc dependency.

## Kiểm tra
- PHP lint 6 view/component; Node syntax JS mới.
- Chromium với HTML render từ PHP và fixture dữ liệu: 1440/1100/390/320 px, sidebar/dock, tổng quan, trạng thái buổi tập, biểu đồ, timeline, ảnh; nhóm/quỹ/GPS, nav active, thu gọn/mở rộng, right rail, không tràn ngang.
- Link native được giữ nguyên. Môi trường Chromium chặn truy cập trực tiếp localhost nên các trang được render từ fixture riêng và nạp vào trình duyệt; chưa kiểm thử hành trình HTTP đầy đủ, quyền DB thật, camera/GPS, giao dịch, thiết bị iOS.
- Chạy local `php -S 127.0.0.1:8000 -t public public/index.php`, Ctrl+F5. Kiểm tra nhóm, nộp quỹ, GPS từ buổi tập, quay lại Tổng quan và thử thu gọn/mở rộng. Không push trước khi đối chiếu diff với nhánh mới nhất của nhóm.
