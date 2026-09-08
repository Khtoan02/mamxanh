<?php

/**
 * Bộ khởi động dự phòng cho shared hosting.
 *
 * Cách cài đúng chuẩn là trỏ document root vào thư mục public/. Nhưng rất
 * nhiều hosting phổ thông khoá cứng document root ở public_html — đúng nhóm
 * người dùng mà một CMS kiểu WordPress phải phục vụ được.
 *
 * Với những host đó, giải nén toàn bộ vào public_html là chạy: .htaccess
 * cạnh file này viết lại mọi request vào public/.
 *
 * NÓI RÕ GIỚI HẠN: file này KHÔNG phải phương án thay thế mod_rewrite. Không
 * có mod_rewrite thì mọi đường dẫn đẹp (/blog, /admin, ...) đều hỏng và file
 * CSS/JS ở public/build cũng không ai phục vụ — Laravel không thể chạy trong
 * điều kiện đó, bất kể có file này hay không. WordPress cũng yêu cầu
 * mod_rewrite cho permalink vì cùng lý do. Trình cài đặt kiểm tra sẵn điều
 * kiện này và báo ngay ở bước đầu.
 *
 * Vai trò thật của file này: khi document root trỏ vào thư mục gốc, request
 * tới '/' được Apache/Nginx đưa thẳng vào đây qua DirectoryIndex trước cả
 * khi luật rewrite kịp chạy trên một số cấu hình. Nó KHÔNG tự dựng lại vòng
 * đời của Laravel, chỉ nạp đúng front controller thật, nên không có chuyện
 * hai đường khởi động lệch nhau.
 */

$frontController = __DIR__.'/public/index.php';

if (! is_file($frontController)) {
    http_response_code(500);
    exit('Không tìm thấy public/index.php — bản giải nén bị thiếu file.');
}

// public/index.php dùng __DIR__ để định vị, nên nó vẫn tự tìm đúng đường
// bất kể được nạp từ đâu.
require $frontController;
