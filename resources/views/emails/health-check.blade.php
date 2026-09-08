<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #111827; padding: 24px;">
    <h2 style="margin: 0 0 16px;">✅ Email thử nghiệm thành công</h2>

    <p>Đây là email thử nghiệm được gửi từ trang <strong>Sức khỏe Website</strong> của {{ config('app.name') }}.</p>
    <p>Nếu bạn nhận được email này, trình gửi email (<code>{{ config('mail.default') }}</code>) đang hoạt động đúng.</p>

    <p style="margin-top: 24px; color: #6b7280; font-size: 13px;">Gửi lúc {{ now()->format('H:i d/m/Y') }}</p>
</body>
</html>
