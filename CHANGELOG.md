# Nhật ký thay đổi

Định dạng theo [Keep a Changelog](https://keepachangelog.com/vi/1.1.0/),
đánh số theo [Semantic Versioning](https://semver.org/lang/vi/).

## [1.0.0] — chưa phát hành

Bản phát hành công khai đầu tiên.

### Quản trị nội dung
- Loại nội dung: Bài viết, Trang, Dịch vụ, Dự án, Sản phẩm — đăng ký bằng code,
  mở rộng thêm được qua plugin
- Taxonomy: Danh mục, Thẻ, Loại sản phẩm, Thẻ sản phẩm (tách bạch giữa bài viết
  và sản phẩm)
- Danh sách nội dung dạng bảng và dạng lưới, chỉnh sửa và xoá hàng loạt
- Thư viện ảnh: tự nén WebP, tự tạo ảnh thu nhỏ, sửa thẻ Alt tại chỗ
- Trình soạn thảo 2 cột, nội dung được lọc mã độc phía máy chủ bằng HTMLPurifier

### SEO
- Trang quản trị SEO riêng: mẫu tiêu đề/mô tả, sitemap, robots.txt, IndexNow
- Module SEO nhúng trong từng loại nội dung, trình bày theo tab
- Schema.org tự sinh: Article, Service, Product, CreativeWork, CollectionPage,
  Blog, ProfilePage, ContactPage, BreadcrumbList, FAQPage
- sitemap.xml, feed RSS, llms.txt
- Nguồn cấp dữ liệu sản phẩm cho Google Merchant Center, có nêu rõ lý do khi
  sản phẩm chưa đủ điều kiện

### Giao diện
- Hệ thống theme có kế thừa (theme con chỉ cần ghi đè file muốn đổi)
- Theme mặc định tự dựng trang chủ từ nội dung thật, mục nào chưa có thì tự ẩn
- Trang danh mục, thẻ, tìm kiếm, tác giả, và trang chi tiết cho từng loại nội dung
- Sáng/tối theo thiết lập trình duyệt, phông chữ tự lưu trữ có hỗ trợ tiếng Việt

### Vận hành
- Trình cài đặt qua trình duyệt, không cần dòng lệnh
- Phân quyền theo vai trò và capability
- Trang Sức khoẻ Website: sao lưu, chặn IP, ghi nhận lỗi và 404, truy vấn chậm
- Thống kê truy cập tự lưu trữ, đo Core Web Vitals thật từ trình duyệt người dùng
- Giới hạn tần suất đăng nhập và gửi liên hệ, chuyển hướng URL

### Mở rộng
- Hệ thống hook: `add_action`, `do_action`, `add_filter`, `apply_filters`
- Nạp plugin từ `content/plugins/` — plugin lỗi bị bỏ qua và ghi log,
  không làm sập website
- `content/` tách khỏi lõi: theme, plugin và ảnh của bạn không bị bản cập nhật
  ghi đè
