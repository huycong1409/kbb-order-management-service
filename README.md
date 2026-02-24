# KBB Order Management Service

Hệ thống quản lý đơn hàng nội bộ cho **KingBamboo** — hỗ trợ import đơn từ Shopee, tính toán lợi nhuận tự động, báo cáo theo tháng và dashboard tổng quan.

---

## Mục lục

- [Tính năng](#tính-năng)
- [Tech Stack](#tech-stack)
- [Cấu trúc dự án](#cấu-trúc-dự-án)
- [Cài đặt](#cài-đặt)
- [Cấu hình môi trường](#cấu-hình-môi-trường)
- [Database](#database)
- [Công thức tính lợi nhuận](#công-thức-tính-lợi-nhuận)
- [Import Excel (Shopee)](#import-excel-shopee)
- [Export Excel](#export-excel)
- [Báo cáo & Dashboard](#báo-cáo--dashboard)
- [Routes](#routes)
- [Deploy](#deploy)

---

## Tính năng

| Nhóm | Tính năng |
|------|-----------|
| **Đơn hàng** | Import Excel từ Shopee, xem chi tiết, lọc/tìm kiếm, sắp xếp theo cột, export Excel |
| **Lợi nhuận** | Tính tự động: doanh thu − phí cố định − thuế 1.5% − giá vốn |
| **Dashboard** | Tổng đơn, doanh thu, lợi nhuận, tỷ suất LN, so sánh tháng trước, biểu đồ ngày |
| **Báo cáo** | Báo cáo tháng theo ngày, chi phí ADS, chi phí KOL, biểu đồ 3 chỉ số |
| **Shop** | Quản lý nhiều shop, phân loại theo platform (Shopee/Lazada/Tiki) |
| **Sản phẩm** | Quản lý sản phẩm + phân loại, track giá vốn từng variant |
| **Tài khoản** | Quản lý user đăng nhập, phân quyền cơ bản |
| **UX** | Copy mã đơn, cảnh báo đơn lỗ, modal xem nhanh, animation mượt |

---

## Tech Stack

| Thành phần | Version |
|-----------|---------|
| PHP | 8.2+ |
| Laravel | 12.x |
| MySQL / SQLite | 8.0+ |
| Vite | 7.x |
| Bootstrap | 5.3 (CDN) |
| Bootstrap Icons | 1.11 (CDN) |
| Chart.js | 4.4 (CDN) |
| Maatwebsite/Excel | 3.1 |

---

## Cấu trúc dự án

```
app/
├── Exports/
│   └── OrdersExport.php          # Export đơn hàng ra Excel
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── OrderController.php
│   │   ├── ProductController.php
│   │   ├── ReportController.php
│   │   ├── ShopController.php
│   │   └── UserController.php
│   └── Requests/
│       ├── Order/ImportOrderRequest.php
│       ├── Product/
│       ├── Report/
│       └── Shop/
├── Models/
│   ├── Order.php                 # Đơn hàng (accessors: profit, total_selling...)
│   ├── OrderItem.php             # Dòng sản phẩm trong đơn
│   ├── Product.php
│   ├── ProductVariant.php
│   ├── Shop.php
│   ├── DailyReport.php           # Chi phí ADS theo ngày
│   └── MonthlyKolCost.php        # Chi phí KOL theo tháng
├── Repositories/
│   ├── Contracts/                # Interfaces
│   ├── OrderRepository.php
│   ├── ProductRepository.php
│   ├── ReportRepository.php
│   └── ShopRepository.php
├── Services/
│   ├── OrderImportService.php    # Parse & import Excel Shopee
│   ├── OrderService.php
│   ├── ProductService.php
│   ├── ReportService.php
│   └── ShopService.php
└── Providers/
    └── RepositoryServiceProvider.php

database/migrations/
├── create_shops_table.php
├── create_products_table.php
├── create_product_variants_table.php
├── create_orders_table.php
├── create_order_items_table.php
├── create_daily_reports_table.php
└── create_monthly_kol_costs_table.php

resources/
├── css/
│   ├── app.css                   # Entry point (import các partials)
│   ├── _variables.css            # CSS custom properties
│   ├── _layout.css               # Sidebar, topbar, main-content
│   ├── _components.css           # Card, table, button, badge...
│   └── _animations.css           # Keyframes & animation rules
└── views/
    ├── auth/login.blade.php
    ├── dashboard/index.blade.php
    ├── layouts/app.blade.php
    ├── orders/                   # index, import, show
    ├── products/                 # index, create, edit + partials
    ├── reports/monthly.blade.php
    ├── shops/                    # index, create, edit
    └── users/                    # index, create, edit
```

---

## Cài đặt

### Yêu cầu

- PHP >= 8.2 với extensions: `ext-zip`, `ext-gd`, `ext-bcmath`
- Composer
- Node.js >= 18 + npm
- MySQL 8.0+ hoặc SQLite

### Các bước

```bash
# 1. Clone & cài dependencies
git clone <repo-url>
cd kbb-order-management-service

composer install

# 2. Cấu hình môi trường
cp .env.example .env
php artisan key:generate

# 3. Tạo database và migrate
php artisan migrate

# 4. Tạo user admin đầu tiên
php artisan tinker
# >>> \App\Models\User::create(['name'=>'Admin','email'=>'admin@kbb.vn','password'=>bcrypt('password')])

# 5. Build CSS/JS
npm install
npm run build

# 6. Khởi động server
php artisan serve
```

Truy cập: `http://127.0.0.1:8000`

### Dev (hot-reload)

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev
```

---

## Cấu hình môi trường

Các biến quan trọng trong `.env`:

```env
APP_NAME="KBB Order Management"
APP_ENV=local          # production khi deploy
APP_DEBUG=true         # false khi deploy
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kbb_orders
DB_USERNAME=root
DB_PASSWORD=

# Tăng giới hạn upload nếu file Excel lớn
# Cần chỉnh thêm php.ini: upload_max_filesize, post_max_size
```

---

## Database

### Sơ đồ quan hệ

```
shops
  └── products
        └── product_variants
  └── orders
        └── order_items  ──→  products / product_variants (nullable FK)
  └── daily_reports
  └── monthly_kol_costs
```

### Bảng chính

#### `orders`
| Cột | Kiểu | Mô tả |
|-----|------|-------|
| id | bigint | PK |
| shop_id | bigint | FK → shops |
| order_code | varchar | Mã đơn Shopee |
| package_code | varchar | Mã gói hàng |
| order_date | date | Ngày đặt |
| status | varchar | Trạng thái (mặc định: completed) |
| fixed_fee | decimal(15,2) | Phí cố định |
| service_fee | decimal(15,2) | Phí dịch vụ |
| payment_fee | decimal(15,2) | Phí thanh toán |
| pi_ship | decimal(15,2) | Phí ship dự kiến |
| tracking_number | varchar | Mã vận đơn |
| buyer_username | varchar | Tên đăng nhập người mua |
| recipient_name | varchar | Tên người nhận |
| province | varchar | Tỉnh/thành |
| ... | | Địa chỉ, SĐT, ghi chú |

**Unique constraint:** `(shop_id, order_code)` — tránh import trùng.

#### `order_items`
| Cột | Kiểu | Mô tả |
|-----|------|-------|
| order_id | bigint | FK → orders |
| product_id | bigint | FK nullable → products |
| product_name | varchar | Snapshot tên SP tại thời điểm import |
| variant_name | varchar | Snapshot tên phân loại |
| quantity | int | Số lượng |
| cost_price | decimal(15,2) | Giá vốn |
| selling_price | decimal(15,2) | Giá bán thực tế |

#### `daily_reports`
| Cột | Kiểu | Mô tả |
|-----|------|-------|
| shop_id | bigint | FK → shops |
| report_date | date | Ngày báo cáo |
| ads_raw_input | varchar | Nhập thô kiểu "₫324.431" |
| ads_fee | decimal(15,2) | Chi phí ADS đã parse |
| ads_refund | decimal(15,2) | Hoàn ADS |

---

## Công thức tính lợi nhuận

```
Lợi nhuận đơn hàng
= Tổng giá bán
  − (fixed_fee + service_fee + payment_fee + pi_ship)   ← phí chung của đơn
  − Tổng thuế (selling_price × 1.5% mỗi item)
  − Tổng giá vốn (quantity × cost_price mỗi item)
```

Tất cả được tính qua PHP accessors trong `Order` model — không lưu DB, tính động mỗi lần load.

### Lợi nhuận báo cáo tháng

```
LN trước ADS  = Σ lợi nhuận đơn theo ngày
Chi phí ADS   = ads_fee − ads_refund
LN sau ADS    = LN trước ADS − Chi phí ADS − Chi phí KOL (chia đều theo ngày)
```

---

## Import Excel (Shopee)

### Định dạng file

- File `.xlsx` / `.xls` xuất từ **Seller Center Shopee**
- Sheet có tên `orders` hoặc sheet đầu tiên nếu không tìm thấy
- Chỉ import các dòng có **Trạng thái = "Hoàn thành"**
- Tối đa **20 MB** mỗi file

### Mapping cột (0-indexed)

| Cột | Index | Dữ liệu |
|-----|-------|---------|
| Mã đơn hàng | 0 | order_code |
| Mã gói hàng | 1 | package_code |
| Ngày đặt | 2 | order_date |
| Trạng thái | 3 | status |
| Mã vận đơn | 7 | tracking_number |
| Đơn vị vận chuyển | 8 | shipping_carrier |
| SKU sản phẩm | 15 | product_sku |
| Tên sản phẩm | 16 | product_name |
| SKU phân loại | 19 | variant_sku |
| Tên phân loại | 20 | variant_name |
| Giá gốc | 21 | original_price |
| Giá sau khuyến mãi | 25 | sale_price |
| Số lượng | 26 | quantity |
| Tổng giá bán | 28 | selling_price |
| Phí ship dự kiến | 40 | pi_ship |
| Hình thức thanh toán | 48 | payment_method |
| Phí cố định | 49 | fixed_fee |
| Phí dịch vụ | 50 | service_fee |
| Phí thanh toán | 51 | payment_fee |
| Tên đăng nhập người mua | 53 | buyer_username |
| Tên người nhận | 54 | recipient_name |
| Số điện thoại | 55 | phone |
| Tỉnh/Thành phố | 56 | province |
| Địa chỉ | 59 | address |
| Ghi chú | 61 | note |

### Xử lý trùng lặp

Import cùng file nhiều lần **không tạo dữ liệu trùng**:
- Dùng `updateOrCreate(shop_id, order_code)` — nếu đã tồn tại thì **update**
- Items cũ bị xoá và tạo lại từ file mới
- Bảo vệ thêm ở tầng DB: unique constraint `(shop_id, order_code)`

---

## Export Excel

Xuất đơn hàng theo bộ lọc hiện tại ra file `.xlsx`:

**15 cột output:**
Mã đơn · Ngày đặt · Shop · Tên sản phẩm · Phân loại · SL · Giá vốn · Tổng giá bán · Phí cố định · Phí DV · Phí TT · Pi Ship · Thuế 1.5% · Tổng vốn · Lợi nhuận

- Đơn có nhiều sản phẩm → nhiều dòng, phí chung chỉ hiển thị ở dòng đầu
- Header row: chữ trắng nền tối
- Auto-size cột

---

## Báo cáo & Dashboard

### Dashboard (`/dashboard`)

- **4 stat cards:** Tổng đơn · Doanh thu · Lợi nhuận · Tỷ suất LN
- **Delta badge:** So sánh % với tháng trước (▲ xanh / ▼ đỏ)
- **Bar chart:** Lợi nhuận theo ngày trong tháng (xanh = dương, đỏ = âm)
- **Top 10 sản phẩm** bán chạy với thanh tiến trình

### Báo cáo tháng (`/reports/monthly`)

- Bảng theo ngày: doanh thu, đơn, LN trước ADS, ADS, KOL, LN sau ADS
- Chỉnh sửa ADS inline (lưu tự động)
- Biểu đồ 3 chỉ số: LN trước ADS / Chi phí ADS / LN sau ADS
- Toggle bar/line chart

---

## Routes

| Method | URL | Chức năng |
|--------|-----|-----------|
| GET | `/dashboard` | Dashboard tổng quan |
| GET | `/shops` | Danh sách shop |
| GET/POST | `/shops/create` | Tạo shop |
| GET/PUT | `/shops/{id}/edit` | Sửa shop |
| DELETE | `/shops/{id}` | Xoá shop |
| GET | `/shops/{shopId}/products` | Sản phẩm của shop |
| GET | `/orders` | Danh sách đơn hàng |
| GET | `/orders/export` | Export Excel |
| GET | `/orders/import/form` | Form import |
| POST | `/orders/import` | Thực hiện import |
| GET | `/orders/{id}` | Chi tiết đơn |
| GET | `/orders/{id}/preview` | Preview JSON (AJAX) |
| GET | `/reports/monthly` | Báo cáo tháng |
| POST | `/reports/daily-ads` | Cập nhật ADS |
| POST | `/reports/monthly-kol` | Cập nhật KOL |
| GET | `/users` | Quản lý tài khoản |

---

## Deploy

### Chuẩn bị (chạy local trước khi push)

```bash
# Build assets
npm run build

# Commit cả public/build/
git add public/build/
git commit -m "build: update assets"
git push
```

### Trên server

```bash
git pull

composer install --no-dev --optimize-autoloader

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```

> **Lưu ý:** `public/build/` đã được bỏ khỏi `.gitignore` nên không cần cài Node/npm trên server.

### Webserver (Nginx)

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/kbb-order-management/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Permissions

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## CSS Architecture

Styles được quản lý theo file phân lớp rõ ràng, bundle qua Vite:

```
resources/css/
├── app.css           # @import các partials theo thứ tự
├── _variables.css    # CSS custom properties (màu, font, spacing)
├── _layout.css       # Sidebar, topbar, main-content, responsive
├── _components.css   # Card, table, button, badge, stat-card, utilities
└── _animations.css   # @keyframes + animation applications
```

Khi sửa CSS: chỉnh file tương ứng → `npm run build` → commit.

---

*KingBamboo Internal Tool — v1.0*
