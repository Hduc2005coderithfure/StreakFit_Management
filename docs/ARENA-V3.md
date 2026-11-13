# StreakFit — The Discipline Arena v3

## Cài đặt
Bản này là bản cập nhật trực tiếp trên app.zip ngày 13/11/2026, không phải source đầy đủ. Giải nén ZIP vào thư mục gốc của repository StreakFit (thư mục có app/, public/, database/), cho phép ghi đè 3 file hiện có. Không xóa các file cũ khác. Không cần npm, Composer mới, migration hoặc thay đổi .env. Nên commit/sao lưu trước khi áp dụng và thử ở môi trường local.

```bash
git status
git switch -c feature/arena-v3
# Giải nén ZIP vào thư mục gốc, sau đó:
git add app/Views/auth/login.php app/Views/dashboard/index.php app/Views/components/arena-*.php public/assets/css/arena-v3.css public/assets/js/arena-*.js public/assets/js/streakfit-motion.js docs/ARENA-V3.md
git diff --cached --stat
git commit -m "Add cinematic Arena v3 and procedural 3D ranks"
```

Khởi động PHP/MySQL bằng cách đang dùng cho repo. Nếu chạy PHP built-in server với cấu hình APP_URL phù hợp: `php -S 127.0.0.1:8000 -t public public/index.php` (cần PHP 8.1+, PDO MySQL, mbstring và database dự án). Kiểm tra /login, /dashboard, /leaderboard, /workouts. Ctrl+F5 nếu trình duyệt cache asset.

## File thay đổi
- `app/Views/dashboard/index.php` (sửa): nạp các component mới, thay hero cũ, progress rank, danh hiệu, podium, nút điều khiển, bỏ renderer rank cũ khỏi view; giữ nguyên form, route và dữ liệu nghiệp vụ.
- `app/Views/auth/login.php` (sửa): phong cách Arena, scene 3D ở khu showcase desktop; giữ form và CSRF.
- `app/Views/components/arena-data.php` (mới): chuẩn hóa dữ liệu trình bày từ backend; số ngày có lịch khác mục tiêu tuần; số buổi hợp lệ từ weeklyCount; tiến độ và thông điệp.
- `app/Views/components/arena-entry.php` (mới): dialog cinematic, số liệu, nút bỏ qua/tiếp tục, SVG fallback.
- `app/Views/components/arena-command.php` (mới): dashboard mới, Discipline Core, lịch tuần, CTA tập luyện.
- `public/assets/css/arena-v3.css` (mới): hệ thống giao diện Arena, responsive và reduced motion.
- `public/assets/js/arena-3d.js` (mới): renderer WebGL native, mesh procedural, camera phối cảnh, vật liệu shader, tám rank, đấu trường, lõi năng lượng; software Canvas 3D fallback.
- `public/assets/js/arena-ui.js` (mới): điều phối scene, intro/counter, replay, rank select/prev/next/drag/zoom/reset, bàn phím.
- `public/assets/js/streakfit-motion.js` (sửa): ngăn intro cũ chạy cùng cinematic mới; giữ page transition, phản hồi nút, hiệu ứng hoàn thành buổi tập.

## Kiến trúc và quy ước
- PHP vẫn là nguồn dữ liệu chính. Không tính lại điểm, không đổi rank catalog, không thêm endpoint, không đổi database hay service.
- Số ngày đăng ký tính theo planned_workouts trong tuần hiện tại, không dùng target_sessions thay thế. Tiến độ hiển thị theo mục tiêu ban đầu; mục tiêu tính điểm hiệu lực được ghi riêng (0 khi chưa đủ điều kiện). Rank lấy sf_rank() và sf_rank_catalog().
- Cinematic tự mở khi vào dashboard lần đầu trong phiên đăng nhập; lưu khóa sessionStorage theo hash phiên, không lưu ID phiên thô. Có replay ở dashboard/menu tài khoản, bỏ qua và Escape. Không ép phát intro khi mở deep link khác. Nếu prefers-reduced-motion, không tự phát; vẫn có thể mở thủ công.
- Huy hiệu có mesh riêng, dựng bằng code (extrusion, gem, torus, bevel), không có GLB cần tải. Khi WebGL không có, dùng Canvas chiếu hình học 3D; nếu Canvas không dùng được, giữ ảnh SVG/CSS. Các SVG thu nhỏ có sẵn giữ nguyên. Không dùng thêm CDN.
- Renderer dừng khi canvas ngoài vùng nhìn hoặc tab ẩn. Khi đóng intro, buffer, observer và listener được giải phóng.

## Kiểm thử đã thực hiện
- PHP lint: 5 file view/component, PASS.
- Node --check: 3 JS, PASS.
- Chromium headless qua fixture PHP riêng (không đóng gói fixture vào sản phẩm): dữ liệu 3 ngày có lịch, 2 buổi hợp lệ, mục tiêu 4, chuỗi 6; số liệu đếm đúng; đóng, replay, Escape; chuyển đủ 8 rank, nút zoom/reset, phím mũi tên; không có lỗi JS; kiểm tra không tràn ngang ở 390 x 844. PASS ở renderer Canvas 3D; WebGL bị vô hiệu hóa trong môi trường kiểm thử nên chưa xác nhận đường GPU.
- Kiểm tra giao diện 1440 x 900 và 390 x 844 bằng screenshot. Số liệu trong ảnh chỉ là fixture test, không có trong code production.
- NOT RUN: kiểm thử tích hợp database/auth thực, camera thực tế, PayOS, toàn bộ tests/run.php và tests/features.php, WebGL trên GPU thật. Môi trường không có MySQL/PDO MySQL/mbstring; Chromium không cấp WebGL và không cho truy cập URL local. Vì vậy HTML fixture được render bằng PHP rồi nạp cùng CSS/JS trong trình duyệt test, sessionStorage được mô phỏng cho fixture. Không tuyên bố đã kiểm thử đăng nhập/refresh session thật.

## Phạm vi bản này
Bản nâng cấp thực tế tập trung cinematic, dashboard, rank, login và podium. Không phải bản tái thiết hoàn chỉnh tất cả tính năng trong prompt cũ: chưa có Rank Hall 8 phòng riêng với scroll storytelling, sự kiện rank-up lưu server, mô hình PBR/GLB, hệ thống biểu đồ lịch sử mới, real-time cộng đồng mới hoặc tái thiết Admin/Quỹ/Tòa án. Các luồng cũ giữ nguyên và dùng các style chung tại view dashboard. Không triển khai lên hosting hay push Git tự động.

## Rollback
Nếu đã commit, dùng `git revert <commit_arena>` trên nhánh đang làm việc; nếu chưa commit, phục hồi 3 file chỉnh sửa từ Git/sao lưu và xóa các file mới theo danh sách. Không cần rollback database. Kiểm tra các thay đổi local khác trước khi phục hồi để tránh mất phần việc chưa commit.
