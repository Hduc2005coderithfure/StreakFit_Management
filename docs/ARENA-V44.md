# StreakFit Arena V4.4 — Ascension

## Phạm vi
Bản patch cho V4.3. Không thay đổi database, cấu hình API, quy tắc XP, check-in/out, lịch hoặc quỹ. Không cần migration ngoài migration presence từ V4.1 nếu chưa cài. Chép gộp `app/`, `public/`, `docs/`, `tests/` vào root hiện có. Không ghi đè .env.

## Tám huy hiệu
- Đồng: khiên đồng nhỏ gọn, hổ phách, ốc tán.
- Bạc: mũi giáo titanium, thạch anh tím, hai lưỡi thép.
- Vàng: huy chương mặt trời tròn, 8 tia, topaz, không có vương miện.
- Bạch Kim: giáp chữ V, cánh băng, đá cyan.
- Kim Cương: giáp tinh thể xanh và kim cương, halo.
- Tinh Anh: obsidian/rose gold, tinh thể tím, vòng không gian.
- Cao Thủ: vương miện vàng và nguyệt quế, nhiều lớp giáp.
- Chiến Tướng: song long, ruby đỏ và giáp vàng.

Ảnh WebP RGBA 768×768, không chứa chữ hoặc trạng thái. Bốn huy hiệu đầu được cắt từ tấm 2×2 đã tạo mới rồi nội suy về 768×768; bốn rank cao dùng ảnh độc lập đã tạo trong cuộc trao đổi trước. Nền riêng V4.3 được giữ. Thẻ gallery có tỉ lệ hiển thị tăng dần theo cấp. Helper `sf_rank_emblem()` và rank stage dùng tên mới `-v44.webp`, cache version theo nội dung file. Asset cũ giữ để tương thích.

## Tổng quan
`arena-today.php` dùng rank từ tổng XP thật, không lấy rank đang xem tại Bảng vàng. `arena-3d.js` có mesh riêng cho 8 cấp, với khối hình thật và đế/vòng năng lượng tương ứng. Ảnh HD chỉ là fallback khi không tạo được renderer hoặc mất WebGL context. Canvas 2D software fallback vẽ các mặt đa giác 3D theo chiều sâu. Không phải mô hình PBR/GLB hoặc ray tracing. Hiệu ứng nổi, xoay nhẹ, vòng năng lượng và hạt sáng; dừng render khi tab bị ẩn hoặc canvas ra khỏi viewport. Tôn trọng prefers-reduced-motion.

Tiến độ tuần giữ đúng nghiệp vụ V4.3: tử số = `$arenaDone` từ số buổi hợp lệ, mẫu số = `$arenaGoal` từ mục tiêu đăng ký tuần, không phải XP. Số ngày đã đăng ký vẫn là thông số riêng. Mỗi buổi cần đạt tương ứng một mốc, 1/3 = 33%, 3/3 = 100%, 4/3 vẫn hiển thị 4/3 và báo vượt 1 buổi, vòng không vượt 100%. Mục tiêu 0 hiển thị chưa đặt mục tiêu, không chia cho 0. Mục tiêu hợp lệ trong source 1–7 buổi. Không dùng thời lượng đang tập để cộng một buổi chưa được xác nhận. Nút tập luyện và Live Training Mode giữ nguyên. Thời tiết chỉ ở Tổng quan như V4.1.

## Kiểm thử
`php tests/arena_v43_rank_assets.php` (đã cập nhật đường dẫn asset V4.4); `php tests/arena_v33_rank.php`; `node --check public/assets/js/arena-3d.js`; `node --check public/assets/js/arena-ui.js`. Kiểm tra thủ công dashboard với tài khoản thật, rank thấp/cao, mục tiêu 0/3, 1/3, 3/3 và vượt mục tiêu, bảng vàng chọn rank khác không đổi rank thật. Browser fixture được chạy ở 1440/390/320 px, với dữ liệu mẫu. GPU WebGL vật lý, camera và MySQL thật chưa được xác nhận.
