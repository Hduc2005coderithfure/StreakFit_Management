# StreakFit Arena V3.2 — Training Planner

## Cập nhật từ V3.1
Giải nén ZIP vào thư mục gốc StreakFit; hợp nhất thư mục và ghi đè file trùng. Không xóa các thư mục cũ. Không cần migration, Node.js hoặc thay .env. Bản này yêu cầu đã cài V3 và V3.1.

- Sửa `app/Views/dashboard/index.php`: tải CSS/JS V3.2 và thay form lịch cũ.
- Sửa `app/Legacy/application.php`: chỉ đổi redirect của save_goal (thành công và lỗi) về `profile?section=schedule`; giữ nguyên xử lý DB, CSRF, transaction và quy tắc khóa tuần.
- Mới `app/Views/components/arena-schedule.php`: bảng 7 ngày, giờ tập, mục tiêu, dữ liệu đã lưu.
- Mới `public/assets/css/arena-v32.css`: giao diện lịch, đồng hồ tiến độ, hiệu ứng tick và nâng cấp vật liệu chung của trang thành viên.
- Mới `public/assets/js/arena-v32.js`: lựa chọn ngày/giờ, preset, sao chép tuần, đồng bộ mục tiêu và kiểm soát gửi form.

## Sử dụng
Mở `http://127.0.0.1:8000/profile?section=schedule`. Tick các ngày; giờ mặc định là 17:00 nếu ngày chưa có giờ. Nhập giờ chung rồi áp dụng, hoặc nhấn preset, hoặc sửa giờ từng ngày. Nhấn Lưu lịch tuần tới. Bỏ chọn ngày sẽ loại ngày khỏi lịch khi lưu. Sao chép tuần này chỉ sao chép ngày trong tuần và giờ, không sao chép ghi chú. Các ghi chú của ngày đã lưu tuần tới được giữ nếu ngày đó vẫn được chọn.

Mục tiêu đã lưu được giữ khi mở trang. Khi sửa ngày, mục tiêu tự khớp với số ngày chọn nếu chưa chỉnh mục tiêu bằng tay trong phiên này. Mục tiêu vẫn có thể độc lập với lịch. Nếu bỏ chọn hết ngày, mục tiêu hiện tại được giữ (backend yêu cầu 1–7). Chỉ chỉnh lịch tuần tới theo nghiệp vụ hiện hành. Không đổi công thức điểm, rank hoặc streak.

## Kiểm thử
- PHP lint ba file PHP: PASS.
- Node --check JS: PASS.
- Chromium dùng PHP fixture và dữ liệu thử: bảy ngày, lịch/goal đã lưu, tick/bỏ tick, chọn giờ chung, giờ riêng, preset, xóa lựa chọn, sao chép tuần, mục tiêu chỉnh riêng, FormData đúng chỉ số và ghi chú, desktop 1440x1000 và mobile 390x844 không tràn ngang: PASS.
- Chưa kiểm thử trên MySQL thật, chưa chạy POST vào database production. Dữ liệu mẫu chỉ sử dụng ở fixture ngoài gói cập nhật.
- Ô giờ dùng native time picker, định dạng 12/24 giờ tùy trình duyệt; preset và ô ngày hiển thị HH:mm.

## Rollback
Khôi phục hai file sửa từ Git V3.1; xóa ba file mới nếu cần. Không cần rollback DB vì không có migration.
