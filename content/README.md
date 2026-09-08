# Thư mục nội dung của bạn

Mọi thứ trong `content/` là **của bạn**, không phải của phần lõi CMS.

Khi bạn cập nhật CMS lên phiên bản mới, toàn bộ file lõi bị thay thế —
nhưng thư mục này **không bao giờ bị đụng tới**. Đây chính là lý do
WordPress có `wp-content/`: theme và ảnh của người dùng phải sống sót qua
mọi lần cập nhật.

```
content/
├── themes/    Giao diện bạn tự cài hoặc tự viết
├── plugins/   Plugin mở rộng chức năng
└── uploads/   Toàn bộ ảnh và tệp bạn tải lên
```

## themes/

Mỗi theme là một thư mục có `theme.json`:

```json
{
    "slug": "theme-cua-toi",
    "name": "Theme của tôi",
    "parent": "default",
    "description": "Kế thừa theme mặc định, chỉ đổi trang chủ"
}
```

Theme ở đây được **ưu tiên hơn** theme cùng tên đi kèm CMS, nên bạn có thể
tạo `content/themes/default/` để đè lên theme mặc định mà không sợ bản cập
nhật ghi đè mất công sức.

Đặt `parent` để chỉ ghi đè vài file, những file còn lại tự kế thừa từ theme
cha. Xem `resources/themes/minimal/` (đi kèm CMS) làm ví dụ.

### Lưu ý quan trọng về CSS

Bản phát hành đi kèm CSS đã biên dịch sẵn — bạn **không cần và không thể**
chạy lại Tailwind trên hosting. Điều đó có nghĩa:

> Theme của bạn chỉ dùng được những class đã có sẵn trong file CSS đi kèm.
> Một class Tailwind chưa từng xuất hiện ở đâu trong CMS sẽ **không có tác
> dụng gì cả** — nó không nằm trong file CSS.

Có hai cách làm đúng:

1. **Dùng lại hệ thống class có sẵn** — cách khuyến khích. Bản phát hành đã
   có sẵn bộ class dựng sẵn: `.stage`, `.stage-center`, `.data-card`,
   `.entry-card`, `.card-media`, `.chapter-number`, `.chapter-rule`, `.lede`,
   `.dropcap`, `.filter-chip`, `.eyebrow`, `.empty-state`, `.reveal`,
   `.reveal-stagger`, `.reveal-image`, `.prose-content`, cùng toàn bộ biến
   màu theo theme sáng/tối.

2. **Tự kèm CSS riêng** — thêm thẻ `<style>` vào template của theme, hoặc đặt
   file `.css` trong thư mục theme rồi nhúng bằng `<link>`. Cách này không phụ
   thuộc Tailwind chút nào.

Nếu bạn có môi trường lập trình (Node.js) và muốn biên dịch lại Tailwind cho
theme của mình, hãy nhớ: Tailwind v4 **bỏ qua mọi đường dẫn bị `.gitignore`
loại ra**, kể cả khi đã khai báo `@source`. Theme nằm trong `content/themes/`
mặc định bị loại, nên phải thêm một dòng ngoại lệ trong `.gitignore`:

```
!/content/themes/ten-theme-cua-ban/
```

## plugins/

Mỗi plugin là một thư mục có `plugin.php`. File đó được nạp sớm, trước khi
CMS đăng ký các loại nội dung, nên plugin dùng được đầy đủ hệ thống hook:

```php
<?php
// content/plugins/vi-du/plugin.php

add_action('mxd_init', function () {
    register_post_type('tuyen_dung', [
        'label' => 'Tin tuyển dụng',
        'label_singular' => 'Tin tuyển dụng',
        'supports' => ['title', 'editor', 'excerpt', 'featured_image'],
    ]);
});
```

Plugin lỗi sẽ được bỏ qua và ghi vào log, **không** làm sập cả website.

Tạm tắt toàn bộ plugin bằng cách thêm `MXD_PLUGINS=false` vào `.env` — dùng
khi cần gỡ lỗi.

## uploads/

Ảnh tải lên qua Thư viện ảnh nằm ở đây. Đừng xoá hay đổi tên file thủ công:
CSDL lưu đường dẫn tới chúng, xoá tay sẽ làm ảnh trong bài viết vỡ hết.
