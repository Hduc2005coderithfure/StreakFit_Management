# StreakFit Arena V3.3 — Community & Rank Energy

## Cài đặt
Bản patch V3.3 dùng trên V3.2. Gói tích lũy V3–V3.3 chứa cả các file thay đổi trước đây, không chứa toàn bộ website. Giữ database, `.env`, `storage`, config và các file không có trong gói. Sao lưu/commit trước khi ghi đè. Copy các thư mục `app`, `public`, `docs`, `tests` vào thư mục gốc; chỉ ghi đè file trùng, không xóa thư mục cũ.

Nếu dùng local, kiểm tra `APP_URL=http://127.0.0.1:8000` trong `.env` nếu ứng dụng chạy ở root, giữ nguyên cấu hình DB. Chạy MySQL/MariaDB, mở Terminal tại thư mục dự án:

```powershell
php -S 127.0.0.1:8000 -t public public/index.php
```

Giữ Terminal chạy. Nhấn Ctrl+F5 sau khi cập nhật. Nếu chạy Apache/XAMPP hoặc hosting, giữ cấu hình hiện tại. Không cần Node/npm, migration hoặc nhập lại database.

## Đường dẫn
- `/dashboard?view=overview` — trung tâm kỷ luật, nút nhanh, năng lượng rank.
- `/dashboard?view=community` — Cộng đồng, hoạt động, bộ lọc, bài viết, nhóm thể thao.
- `/leaderboard?view=rank` — rank 3D và thanh năng lượng cá nhân; `&inspect=kim-cuong` mở rank tương ứng.
- `/leaderboard?view=board` — bảng xếp hạng; `/leaderboard?view=court` — tòa án.
- `/profile?section=schedule` — lịch 7 ngày và giờ tập giữ từ V3.2.
- `/notifications` — thông báo qua chuông header và menu tài khoản.

## Thay đổi V3.3
- Điều hướng chính 5 mục: Tổng quan, Cộng đồng, Check-in, Bảng vàng, Cá nhân. Cộng đồng có URL trực tiếp, mục chính, tab phụ, nút nhanh, link drawer; không mất phần feed khi đổi giao diện.
- Thanh năng lượng sử dụng tổng điểm `users.total_points`, ngưỡng từ `sf_rank_catalog()`; 1 điểm = 1 năng lượng trong cấp. Ví dụ 4.500 điểm ở Bạch Kim (3.001→7.001): 1.499/4.000, hiển thị 37,4% (làm tròn xuống 1 chữ số), còn 2.501 điểm. Không đổi luật tính điểm hoặc tạo tiền tệ mới. Rank tối đa hiển thị MAX, không chia cho 0.
- Cộng đồng có hero, số liệu thực từ danh sách gần đây, hiệu ứng quỹ đạo, link nhóm thể thao, bảng vàng và check-in.
- Thả cảm xúc dùng API `/api/action` có xác thực/CSRF hiện hữu, trả tổng đếm từ DB. Cập nhật chỉ bài viết, khóa gửi trong lúc xử lý; không tự gửi lại khi lỗi mạng. Không JS vẫn dùng form POST. Bình luận/checkout hợp lệ chuyển về Cộng đồng; bình luận và reaction POST có anchor bài viết. Không triển khai WebSocket; tài khoản khác thấy thay đổi khi tải lại.
- Giữ cinematic V3, lịch V3.2, tab rank/bảng vàng/tòa án. Không can thiệp luồng thanh toán, camera, điểm, schema.
- PHP development router chỉ phục vụ trực tiếp file tĩnh tồn tại trong `public/assets` và thuộc whitelist extension; production không bị ảnh hưởng.

## Kiểm thử
- `php tests/arena_v33_rank.php` — 13 trường hợp ngưỡng rank, điểm thiếu, tiến độ, rank tối đa và tám node.
- PHP lint/Node syntax checks cho file thay đổi.
- Chromium: render PHP với fixture độc lập; chọn tab Cộng đồng không reload; lọc; thêm/xóa reaction qua API mock; rank/select; tab bảng vàng/tòa án; giữ lịch 7 ngày; mobile 390x844 không tràn ngang; không pageerror. History API được mô phỏng do chính sách trình duyệt thử nghiệm không cho điều hướng mạng. Fixture/mock không đưa vào source giao hàng.
- Kiểm thử PHP router thật: GET CSS, JS, SVG trả HTTP 200 và đúng nội dung; đường dẫn traversal thử nghiệm trả 404.
- Chưa chạy MySQL thật, kiểm thử multi-account, thanh toán/camera/GPU thực tế. Cần kiểm tra tại môi trường local của bạn trước khi đưa production.

## Rollback
Dùng Git để so sánh và phục hồi các file patch về commit trước. Không rollback dữ liệu DB vì patch không có migration.
