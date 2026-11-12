# StreakFit

**Cập nhật nhóm thể thao, GPS và QR ngân hàng:** xem [hướng dẫn cấu hình và kiểm thử](docs/sports-gps-payments.md). Database local đã có bảng mới. Bản dump SQL hiện tại cần chạy các migration theo thứ tự khi cài mới; hướng dẫn trong tài liệu này thay thế ghi chú cài mới cũ bên dưới.

StreakFit là ứng dụng PHP 8.1+ và MySQL/MariaDB dành cho theo dõi tập luyện theo tuần. Apache chỉ phục vụ thư mục `public/`; ảnh tải lên nằm trong `storage/` và được kiểm tra quyền trước khi gửi về trình duyệt.

## Cài mới trên XAMPP

1. Đặt dự án tại `C:\xampp\htdocs\StreakFit` và bật Apache, MySQL.
2. Import `database/streakfit.sql` bằng phpMyAdmin.
3. Sao chép `.env.example` thành `.env`, rồi điền cấu hình database và URL phù hợp.
4. Mở `http://localhost/StreakFit/`.
5. Dữ liệu SQL có tài khoản và nội dung mẫu để xem giao diện. Hãy đổi mật khẩu tài khoản mẫu trước khi cho người khác truy cập.

Bật các extension PHP `pdo_mysql`, `fileinfo`, `mbstring`; bật Apache `mod_rewrite` và `AllowOverride All`. Nếu bật HTTPS, cấu hình `APP_URL` bằng URL HTTPS.

## Nâng cấp cơ sở dữ liệu đã có

Sao lưu database trước, chọn đúng database StreakFit trong phpMyAdmin, rồi chạy **một lần** `database/migrations/2026_11_11_feature_expansion.sql`. Migration bổ sung cột/bảng mới, làm sạch chỉ số sức khỏe cũ nằm ngoài giới hạn, chuẩn hóa tuần của lịch và buổi tập, đóng buổi đã kết thúc nhưng thiếu giờ check-out, hạ buổi thiếu ảnh hoặc trùng ngày thành không hợp lệ, rồi thêm các ràng buộc mới. Kháng cáo đang chờ nhưng thiếu ảnh minh chứng sẽ được đóng ở trạng thái hết hạn. Không chạy migration này trên database mới đã import `database/streakfit.sql`.

Sau đó, để bật phân quyền Admin chi tiết trên database cũ, chạy **một lần** `database/migrations/2026_11_12_admin_permissions.sql`. Migration tạo danh mục quyền và tạm cấp các quyền hiện có cho những tài khoản Admin để giữ nguyên khả năng truy cập; sau khi cập nhật source, vào **Admin → Phân quyền Admin** để bỏ các quyền không cần. Với database mới, chỉ cần import bản `database/streakfit.sql` mới nhất; không chạy migration phân quyền thêm lần nữa.

## Chạy kiểm thử

```powershell
C:\xampp\php\php.exe tests\run.php
C:\xampp\php\php.exe tests\seed_import.php
```

`tests/run.php` xóa và tạo lại database kiểm thử riêng `streakfit_test`; `tests/seed_import.php` xóa và tạo lại `streakfit_test_seed` để kiểm tra toàn bộ file schema và dữ liệu mẫu. Hai script từ chối chạy nếu database đích trùng với database ứng dụng. Có thể đổi tên test DB bằng `STREAKFIT_TEST_DB` hoặc `STREAKFIT_SEED_TEST_DB`, nhưng tên vẫn phải bắt đầu bằng tiền tố test được script cho phép.

## Cron và tác vụ định kỳ

Chạy bằng PHP CLI, không gọi các script này qua trình duyệt. Trong Windows Task Scheduler, dùng chương trình `C:\xampp\php\php.exe` và thêm đường dẫn script làm tham số:

- `C:\xampp\htdocs\StreakFit\cron\streakfit_maintenance.php`: chạy mỗi 5 phút để gửi nhắc lịch trong vòng một giờ, nhắc mục tiêu/streak, tự đóng check-in quá hạn, chốt các tuần đã qua và xử lý quỹ tháng trước.
- `C:\xampp\htdocs\StreakFit\cron\streakfit_weekly_settlement.php`: có thể chạy hằng ngày để chốt lại các tuần đã qua. Việc chốt có khóa và chống ghi trùng, nên chạy lại an toàn.

Lịch tuần và tháng dùng múi giờ `APP_TIMEZONE`. Bảng `cron_runs` ghi thời điểm, trạng thái và kết quả; trang quản trị chỉ xem lịch sử này. Có thể chạy lại tác vụ lỗi từ terminal hoặc Task Scheduler.

## Quy tắc nghiệp vụ được áp dụng

- Buổi hợp lệ đạt thời lượng cấu hình (mặc định 30 phút), có đủ ảnh check-in/check-out và được check-out trước ngưỡng thời gian mở. Mỗi ngày chỉ có một buổi hợp lệ. Tác vụ hoặc thành viên hủy buổi đang mở sẽ ghi giờ kết thúc và trạng thái `abandoned`.
- Mục tiêu tuần tới từ 1 đến 7 buổi; ngày lịch phải thuộc tuần đã chọn. Tuần đã khóa không cho sửa.
- Điểm tuần tính theo `C`, `M`, `E`, `P` và điểm streak theo các tham số quản trị. Điểm tài khoản không giảm dưới 0; chốt điểm, sổ điểm và streak được ghi trong cùng transaction.
- Kháng cáo chỉ nhận cho tuần hiện tại, cần lý do và ảnh minh chứng, giới hạn theo tháng. Mỗi thành viên chỉ bỏ một phiếu cho một đơn và không được tự bỏ phiếu. Số phiếu tối thiểu chỉ bật ý kiến cộng đồng để tham khảo; quản trị viên ra quyết định cuối cùng.
- Thành viên chỉ sửa/xóa bài và bình luận của mình. Báo cáo trùng đang chờ bị từ chối; thao tác quản trị được ghi vào `audit_logs`.
- Giao dịch quỹ chỉ được ghi sổ, không giữ hay chuyển tiền. Khi Cron chốt tháng, thành viên đã tham gia quỹ bị ghi một khoản phạt bằng mức đã biểu quyết cho mỗi tuần đã chốt có `week_start` nằm trong tháng và có buổi bị thiếu. Mỗi khoản có khóa tham chiếu để chạy Cron lại không ghi trùng.

## Cấu trúc

- `public/index.php`: router và front controller.
- `app/Controllers`, `app/Services`, `app/Models`, `app/Views`: điều phối, quy tắc nghiệp vụ, dữ liệu và giao diện.
- `app/Legacy/application.php`: adapter tương thích cho một số luồng POST và màn hình đang chuyển dần sang controller/service.
- `database/streakfit.sql`: schema cài mới và dữ liệu mẫu.
- `database/migrations/`: cập nhật database cũ.
- `tests/`: kiểm thử tích hợp và kiểm tra import schema.
- `cron/`: tác vụ CLI.
- `storage/`: ảnh riêng tư và dữ liệu do ứng dụng tạo.

Các URL chính: `/login`, `/register`, `/dashboard`, `/workouts`, `/workouts/history`, `/court`, `/leaderboard`, `/notifications`, `/profile`, `/admin`.

Đặt lại mật khẩu gửi email qua `mail()` của PHP. Trên XAMPP cần cấu hình sendmail/SMTP trong `php.ini`; nếu không, email đặt lại sẽ không được gửi.
