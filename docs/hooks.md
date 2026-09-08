# Hook Catalog

Action & Filter hook API — xem `backend/app/Support/HookRegistry.php` cho phần triển khai, `backend/app/helpers.php` cho các hàm global.

## API

```php
add_action(string $hook, callable $callback, int $priority = 10): void
do_action(string $hook, mixed ...$args): void

add_filter(string $hook, callable $callback, int $priority = 10): void
apply_filters(string $hook, mixed $value, mixed ...$args): mixed
```

- `priority` mặc định `10`, số nhỏ hơn chạy trước.
- Đăng ký hook trong `AppServiceProvider::registerCoreHooks()` (core) hoặc trong plugin/module riêng khi hệ thống Plugin Manager được xây dựng.

## Action hooks hiện có

| Hook | Bắn ra khi nào | Tham số |
|---|---|---|
| `mxd_after_login` | Ngay sau khi user đăng nhập thành công (`AuthenticatedSessionController::store`) | `App\Models\User $user` |

Listener mặc định: cập nhật `users.last_login_at`.

## Filter hooks hiện có

_Chưa có filter nào được đăng ký trong core — thêm vào bảng này khi tạo mới._

## Quy ước đặt tên

`mxd_{thời điểm}_{đối tượng}` — ví dụ `mxd_after_post_created`, `mxd_before_post_deleted`, `mxd_post_content` (filter). Xem SRS mục IV.1.a để biết ví dụ gốc.
