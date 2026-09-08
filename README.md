# MXD CMS

Hệ quản trị nội dung mã nguồn mở viết trên Laravel. Cài đặt qua trình duyệt,
không cần dòng lệnh. Tối ưu sẵn cho SEO và Google.

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

## Yêu cầu

| Thành phần | Tối thiểu |
|---|---|
| PHP | 8.2 |
| MySQL / MariaDB | 5.7 / 10.3 |
| Apache | `mod_rewrite` bật (hoặc Nginx có cấu hình tương đương) |
| Extension | `pdo_mysql`, `mbstring`, `json`, `curl`, `openssl`, `zip`, `xml`, và `gd` hoặc `imagick` |

Không cần Node.js trên máy chủ — bản phát hành đã kèm sẵn CSS/JS biên dịch.

## Cài đặt

1. Tải file `.zip` ở mục [Releases](https://github.com/Khtoan02/mxd/releases).
2. Giải nén và tải toàn bộ lên hosting:
   - **Nếu trỏ được document root** (VPS, cPanel có Terminal): đặt mã nguồn ở
     đâu cũng được, trỏ document root vào thư mục `public/`. Đây là cách an
     toàn nhất.
   - **Nếu không trỏ được** (shared hosting khoá cứng `public_html`): giải nén
     thẳng toàn bộ vào `public_html`. File `.htaccess` đi kèm sẽ tự lo phần
     còn lại.
3. Tạo một cơ sở dữ liệu MySQL trống.
4. Mở trình duyệt vào tên miền của bạn. Trình cài đặt tự hiện ra — điền thông
   tin CSDL và tài khoản quản trị. Xong.

Bạn không cần tạo `.env`, không cần chạy `composer`, không cần gõ lệnh nào.

## Nếu bạn cài từ mã nguồn Git

Repo này không kèm `vendor/` (thư viện PHP). Sau khi clone:

```bash
cd backend && composer install
```

Rồi mở `/install` trên trình duyệt như trên. Nếu bạn sửa CSS/JS, chạy
`npm install && npm run build` trước khi triển khai.

## Nội dung của bạn nằm ở đâu

Thư mục `content/` là của bạn, **bản cập nhật không bao giờ đụng tới**:

```
content/
├── themes/    Giao diện bạn tự cài hoặc tự viết
├── plugins/   Plugin mở rộng chức năng
└── uploads/   Ảnh và tệp bạn tải lên
```

Cập nhật CMS = thay toàn bộ file lõi, giữ nguyên `content/` và `.env`.
Xem [content/README.md](backend/content/README.md) để biết cách viết theme và plugin.

## Tính năng chính

- **Nội dung**: Bài viết, Trang, Dịch vụ, Dự án, Sản phẩm; danh mục và thẻ
  tách riêng cho bài viết và sản phẩm; sửa/xoá hàng loạt
- **SEO**: mẫu tiêu đề và mô tả, sitemap, robots.txt, IndexNow, Schema.org tự
  sinh theo từng loại nội dung, RSS, nguồn cấp sản phẩm cho Google Merchant Center
- **Ảnh**: tự nén WebP, tự tạo ảnh thu nhỏ, quản lý thẻ Alt
- **Giao diện**: hệ thống theme có kế thừa; theme con chỉ cần ghi đè file muốn đổi
- **Mở rộng**: hệ thống hook kiểu WordPress (`add_action`, `add_filter`); plugin
  lỗi bị bỏ qua và ghi log chứ không làm sập website
- **Vận hành**: phân quyền theo vai trò, sao lưu, chặn IP, ghi nhận lỗi và 404,
  thống kê truy cập tự lưu trữ, đo Core Web Vitals thật

## Tài liệu

- [Nhật ký thay đổi](CHANGELOG.md)
- [Kiến trúc và các quyết định thiết kế](docs/ARCHITECTURE.md)
- [Viết theme và plugin](backend/content/README.md)

## Giấy phép

[MIT](LICENSE) — bạn được tự do dùng, sửa và phân phối, kể cả cho mục đích
thương mại.
