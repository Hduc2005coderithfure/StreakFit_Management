# StreakFit Arena V3.5 Final — Stable Release

## Phạm vi

Bản tích lũy dựa trên `app.zip` gốc + V3.3, giữ toàn bộ nâng cấp V3–V3.3. Không tích hợp Strava, Open-Meteo, wger hoặc các API mới. Giữ Gemini Coach, PayOS và GPS đã có sẵn (các dịch vụ này vẫn phụ thuộc cấu hình riêng nếu sử dụng). Không có migration mới. Không đổi công thức XP, rank, streak, quỹ hay quy tắc check-in/out.

## Cập nhật

1. Sao lưu/commit các thay đổi hiện có, tạo nhánh Git riêng từ nhánh đang làm việc.
2. Giải nén ZIP vào thư mục tạm, copy MERGE các thư mục `app`, `public`, `docs`, `tests`, `config` vào thư mục gốc dự án. Không xóa các thư mục gốc. Không chép đè `.env` (ZIP không chứa file này).
3. `config/app.php` là bản cấu hình gốc trước Strava; nếu bạn đã tự chỉnh cấu hình khác ngoài Strava, so sánh và giữ các tùy chỉnh đó. Không thay đổi thông tin DB hoặc APP_URL đang hoạt động.
4. Nếu đã áp dụng V3.4 Strava, thực hiện dọn file theo phần bên dưới.
5. Chạy `php -S 127.0.0.1:8000 -t public public/index.php` trong thư mục gốc nếu dùng PHP dev server. APP_URL phải tương ứng với địa chỉ bạn sử dụng (ví dụ `http://127.0.0.1:8000`). Nếu dùng XAMPP/Apache, giữ cấu hình đang chạy. Khởi động MySQL.
6. Truy cập `/dashboard?view=overview`, `/dashboard?view=community`, `/leaderboard?view=rank`, `/profile?section=schedule`. Ctrl+F5 lần đầu nếu cần.

## Dọn Strava nếu đã copy V3.4

Ba file tích hợp được khôi phục bằng ZIP: `app/Views/dashboard/index.php`, `public/index.php`, `config/app.php`. Các route Strava không còn tồn tại. Nếu chưa từng cài V3.4 thì bỏ qua bước dọn.

Từ thư mục gốc dự án, PowerShell:

```powershell
$old = @(
  'app/Controllers/StravaController.php',
  'app/Services/StravaClient.php',
  'app/Services/StravaConnections.php',
  'app/Views/strava/index.php',
  'public/assets/css/strava.css',
  'database/migrations/2026_11_15_strava.sql',
  'scripts/strava-webhook.php',
  'tests/strava.php',
  'docs/STRAVA.md',
  '.env.strava.example'
)
$old | ForEach-Object { if (Test-Path -LiteralPath $_ -PathType Leaf) { Remove-Item -LiteralPath $_ } }
```

Chỉ xóa đúng 10 file này; không xóa thư mục khác. Nếu đã thêm `STRAVA_*` vào `.env`, có thể xóa riêng các dòng đó. Không xóa bảng `strava_connections` tự động: nếu đã thực thi migration thì bảng vẫn tồn tại nhưng không còn được ứng dụng sử dụng. Nếu đã thực sự kết nối tài khoản hoặc đăng ký webhook, cần thu hồi quyền trên Strava và hủy webhook trước khi bỏ khóa; không để token không dùng tồn tại lâu dài. Việc xóa dữ liệu token/bảng cần sao lưu và xác nhận riêng.

## Thay đổi V3.5

- Giữ cinematic mở đầu, replay/skip, 8 huy hiệu procedural 3D, Discipline Core, thanh rank theo XP, Community dock, lịch tuần 7 ngày.
- Tự tạo revision CSS/JS theo SHA-256 của nội dung; bỏ stylesheet CDN icon không được sử dụng; chỉ nạp script lịch ở trang lịch, script reaction ở Bảng tin.
- Bỏ preboot ẩn màn hình cũ ở nhóm thể thao và admin; bỏ intro tự mở khi vào deep link Cộng đồng.
- Tối ưu render 3D: không render trước khi canvas được quan sát, dừng khi ngoài viewport/tab ẩn, xử lý resize và tương thích khi thiếu observer.
- Tab nội bộ đưa mục đang chọn vào vùng nhìn; back về URL gốc chọn tab phù hợp. Focus bàn phím rõ hơn; giảm chuyển động theo thiết lập hệ thống; dock và tab mobile dễ bấm hơn.
- Thả cảm xúc có timeout 12 giây, báo lỗi và mở khóa nút; không tự gửi lại khi chưa xác định server đã ghi nhận hay chưa.
- Xác thực dữ liệu lịch: giờ 00:00–23:59, tối đa 7 ngày, không trùng ngày, không nhận cấu trúc mảng bất hợp lệ. Vẫn giữ phép đặt mục tiêu riêng và lịch tuần tới.
- Sau cập nhật hồ sơ, mật khẩu, sức khỏe, quỹ hoặc kháng cáo, quay về đúng khu vực; giữ redirect admin.
- Router chỉ cắt base path đúng ranh giới phân đoạn URL.

## Kiểm thử thực hiện

- PHP lint: 84 file trong app, config, public và tests, đạt.
- Node syntax check: tất cả 11 file JS trong public/assets/js, đạt.
- `php tests/arena_v33_rank.php`: 13 trường hợp đạt.
- `php tests/arena_final.php`: 9 kiểm tra revision asset, route base path, không còn Strava trong các điểm tích hợp chính, đạt.
- PHP dev server thực: CSS, JS, SVG HTTP 200; `/strava` và `/strava/callback` HTTP 404.
- Chromium với PHP fixture (dữ liệu riêng, không có trong ZIP): dock Cộng đồng, đổi tab không reload, bộ lọc, reaction mô phỏng, ngưỡng rank, rank select, Bảng vàng/Tòa án, lịch 7 ngày, mobile 390×844 không tràn ngang, không phát hiện lỗi JS. Do trình duyệt môi trường kiểm thử chặn điều hướng, dùng trang render inline và mô phỏng History/API; không phải kiểm thử MySQL hoặc nhiều tài khoản thực.
- Chưa chạy: toàn bộ kiểm thử DB (`tests/run.php`, `tests/features.php`, `tests/http.php`), camera thực, PayOS thực, GPU thực. Môi trường không có PDO MySQL. Cần kiểm tra bằng database test riêng trước merge production; không chạy test phá hủy trên DB thật.

## Git

Nhánh đề xuất `feature/streakfit-arena-final`. Nếu đã có nhánh làm việc V3.3 chưa merge, có thể tạo nhánh mới từ nhánh đó hoặc tiếp tục chính nhánh cũ để tránh PR trùng lặp. Kiểm tra `git status`, `git branch --show-current`, `git remote -v` trước khi thao tác. Stage từng nhóm file; kiểm tra cả file đã xóa Strava nếu đã cài. Không stage `.env`, upload người dùng, database dump hoặc token. Push nhánh riêng, tạo PR về nhánh chính thực tế của chủ repository; không force push.
