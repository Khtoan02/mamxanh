<?php

/**
 * Danh tính bản phát hành CMS.
 *
 * Trước đây phiên bản là chuỗi 'MXD-SRS-2026-V1' viết cứng giữa
 * InstallController — không ai tra được, không so sánh được, không hiển thị
 * ở đâu. WordPress giữ đúng một nguồn sự thật ở wp-includes/version.php và
 * mọi thứ khác đọc từ đó; file này đóng vai trò tương đương.
 */
return [
    // Semantic versioning. Tag git khi phát hành phải khớp: v1.0.0
    'version' => '1.0.0',

    // Yêu cầu tối thiểu — trình cài đặt kiểm tra đúng những con số này chứ
    // không viết lại ở nơi khác.
    //
    // PHP 8.3 chứ KHÔNG phải 8.2: laravel/framework v13 khai báo `php: ^8.3`
    // trong composer.json. Trước đây chỗ này ghi 8.2 nên preflight cho qua
    // một máy chủ PHP 8.2, người dùng cài xong mới gặp lỗi nghiêm trọng —
    // đúng kiểu hỏng tệ nhất: bộ kiểm tra bảo "đạt" rồi sản phẩm vẫn chết.
    'requires' => [
        'php' => '8.3.0',
        'mysql' => '5.7.0',

        // Trình cài đặt kiểm tra đúng danh sách này, và trang giới thiệu
        // cũng đọc chính nó — bảng "Yêu cầu hệ thống" không bao giờ lệch với
        // thứ preflight thật sự kiểm tra.
        'extensions' => ['pdo_mysql', 'mbstring', 'json', 'curl', 'openssl', 'zip', 'xml'],

        // Chỉ cần MỘT trong hai để xử lý ảnh.
        'image_extensions' => ['gd', 'imagick'],
    ],

    // CỐ Ý không có lời gọi hàm nào trong file này — kể cả base_path().
    // Quy trình đóng gói đọc phiên bản bằng `php -r 'require config/mxd.php'`
    // ở NGOÀI Laravel, nên chỉ cần một helper của framework là cả bước phát
    // hành gãy (đã gặp thật). Đường dẫn content/ lấy qua base_path('content')
    // tại nơi sử dụng.
];
