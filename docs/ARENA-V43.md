# StreakFit V4.3 — The Forge

## Phạm vi
Bản cập nhật áp dụng lên V4.2 (V4.1 presence migration nếu đã sử dụng). Chỉ nâng cấp hệ thống hình ảnh và trình diễn rank. Không thay đổi cơ sở dữ liệu, logic XP, công thức rank, điểm, chuỗi, API, đăng nhập hoặc chức năng cộng đồng.

## Cài đặt
Copy gộp app/, public/, docs/, tests/ vào thư mục gốc dự án; không xóa file cũ. Giữ nguyên .env. Mở `/leaderboard?view=rank` và Ctrl+F5. Bản này không yêu cầu Node, thư viện CDN hay migration. Các SVG rank cũ không bị xóa; helper mới dùng WebP có phiên bản nội dung.

## Tài nguyên
- 8 `public/assets/ranks/*-v43.webp`: ảnh huy hiệu 768×768 nền trong suốt, tách từ bộ ảnh AI đã tạo trong cuộc trao đổi, không chứa tên, điểm hay trạng thái. Có thể dùng cho sidebar, bảng vàng, hồ sơ, cinematic.
- 8 `*-scene.svg`: môi trường nền riêng, đồ họa vector theo chủ đề lò rèn, thép lạnh, đền vàng, băng, tinh thể, không gian tím, ngai vàng và chiến trường đỏ. Đây là nền cách điệu, không phải cảnh Unreal Engine.
- `arena-v43.css`: bố cục gallery, viền neon, màu riêng, hover/parallax nhỏ trên thiết bị có chuột; tôn trọng giảm chuyển động.
- `arena-3d.js`: mô hình hình học thật, các khung giáp đùn nổi, mặt vát, cánh riêng, đá quý tạo từ mặt tam giác, chi tiết theo từng cấp; đèn đa hướng và phản xạ giả lập. Không phải mô hình UE5/Octane ray tracing, không dùng ảnh làm mesh.
- `arena-ui.js`: đồng bộ ảnh, nền, trạng thái preview và mô hình. Hạng hiện tại vẫn tính từ XP backend; chọn hạng khác không đổi rank thực.
- `functions.php` và dashboard view: sử dụng asset mới, nhãn dữ liệu động.

## Kiểm tra
`php tests/arena_v43_rank_assets.php`
`php tests/arena_v33_rank.php`
`php tests/arena_final.php`
`node --check public/assets/js/arena-3d.js`
`node --check public/assets/js/arena-ui.js`

Đã kiểm tra PHP render với fixture V4.2 và Chromium 1440, 390, 320 px: đủ 8 ảnh 768 px, đúng trạng thái rank trên tài khoản mẫu 4500 XP, chọn đủ 8 hạng, đổi ảnh/nền/tên, điều khiển zoom/reset/xoay bằng bàn phím, không lỗi JS hoặc tràn ngang trong test. Chromium môi trường kiểm thử sử dụng Canvas 3D fallback; WebGL phần cứng chưa xác nhận. Kiểm tra thực tế trên máy người dùng trước khi merge. Không cần kết nối DB cho các kiểm tra trên.

## Git
Stage các file từ patch này sau khi kiểm tra git diff. Không stage .env hoặc dữ liệu người dùng. Nếu nhóm đã thay đổi cùng file helper/dashboard/JS kể từ V4.2, merge nội dung thay vì ghi đè mù.
