# Cấu trúc thư mục StreakFit

Sơ đồ này mô tả cấu trúc hiện có của dự án. Các file dữ liệu runtime trong `storage/`, cấu hình bí mật trong `.env` và nội dung Git không được liệt kê chi tiết.

```text
StreakFit/
├── app/                         # Mã nguồn ứng dụng PHP
│   ├── Controllers/             # Nhận request, điều phối service và view
│   │   ├── Api/                 # Endpoint API
│   │   └── ...Controller.php    # Controller theo từng tính năng
│   ├── Core/                    # Router, request/response, session, DB, base classes
│   ├── Helpers/                 # Hàm tiện ích dùng chung
│   ├── Legacy/                  # Adapter cho luồng cũ đang chuyển đổi
│   ├── Middlewares/             # Xác thực, CSRF và kiểm tra request
│   ├── Models/                  # Truy vấn và ánh xạ dữ liệu
│   ├── Services/                # Quy tắc nghiệp vụ và tích hợp dịch vụ
│   ├── StreakFit/               # Hàm tương thích/tiện ích StreakFit
│   └── Views/                   # Giao diện PHP theo tính năng
│       ├── admin/
│       │   ├── pages/
│       │   └── partials/
│       ├── auth/
│       ├── community/
│       ├── court/
│       ├── dashboard/
│       ├── layouts/
│       ├── leaderboard/
│       └── workout/
├── assets/                      # Tài nguyên frontend cấp dự án
│   ├── css/
│   └── js/
├── config/                      # Cấu hình ứng dụng và database
├── cron/                        # Tác vụ định kỳ chạy bằng PHP CLI
├── database/
│   ├── migrations/              # SQL nâng cấp database hiện có
│   └── streakfit.sql            # Schema và dữ liệu mẫu để cài mới
├── docs/                        # Tài liệu kiến trúc, cấu hình và kiểm thử
├── public/                      # Document root Apache; nội dung công khai
│   ├── assets/
│   │   ├── css/
│   │   └── js/
│   ├── .htaccess
│   └── index.php                # Front controller
├── storage/                     # Ảnh tải lên và dữ liệu runtime
├── tests/                       # Kiểm thử PHP và JavaScript
│   └── fixtures/                # Dữ liệu mẫu phục vụ kiểm thử
├── views/                       # Một số view tương thích luồng cũ
├── .env                         # Cấu hình bí mật ở máy chạy (không commit)
├── .env.example                 # Mẫu biến môi trường
├── .htaccess                    # Điều hướng URL vào public/front controller
├── composer.json                # Khai báo Composer/autoload
├── index.php                    # Điểm vào tương thích ở thư mục gốc
└── README.md                    # Hướng dẫn cài đặt và vận hành
```

## Luồng xử lý chính

```text
Trình duyệt
    ↓
public/index.php
    ↓
app/Core/Router.php
    ↓
app/Controllers/<tính năng>Controller.php
    ├── app/Services/             Quy tắc nghiệp vụ
    │       ↓
    │   app/Models/               Truy cập dữ liệu qua PDO
    │       ↓
    │   MySQL/MariaDB
    └── app/Views/                Kết xuất giao diện

public/assets/                    CSS, JavaScript và tài nguyên trình duyệt
```

## Vị trí nên tìm khi làm việc

| Muốn thay đổi | Bắt đầu tìm ở |
|---|---|
| URL, định tuyến, request/response | `app/Core/`, `public/index.php` |
| Một tính năng hoặc màn hình | Controller tương ứng, rồi `app/Services/` và `app/Views/` |
| Truy vấn database | `app/Models/`, `app/Core/Database.php` |
| Schema hoặc nâng cấp database | `database/streakfit.sql`, `database/migrations/` |
| CSS/JavaScript giao diện | `public/assets/` (kiểm tra `assets/` nếu luồng đang dùng tài nguyên cấp dự án) |
| Tác vụ định kỳ | `cron/` |
| Kiểm thử | `tests/` |
| Cấu hình môi trường | `.env.example`, `config/` (không đưa bí mật vào tài liệu hoặc Git) |

> Sơ đồ là bản mô phỏng để định hướng. Trước khi sửa, hãy kiểm tra file và luồng thực tế liên quan vì một số phần cũ/mới có thể cùng tồn tại trong giai đoạn chuyển đổi.
