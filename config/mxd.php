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
    'requires' => [
        'php' => '8.2.0',
        'mysql' => '5.7.0',
    ],

    // CỐ Ý không có lời gọi hàm nào trong file này — kể cả base_path().
    // Quy trình đóng gói đọc phiên bản bằng `php -r 'require config/mxd.php'`
    // ở NGOÀI Laravel, nên chỉ cần một helper của framework là cả bước phát
    // hành gãy (đã gặp thật). Đường dẫn content/ lấy qua base_path('content')
    // tại nơi sử dụng.
];
