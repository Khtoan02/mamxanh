<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #111827; padding: 24px;">
    <h2 style="margin: 0 0 16px;">Có tin nhắn liên hệ mới trên {{ config('app.name') }}</h2>

    <p><strong>Tên:</strong> {{ $contactMessage->name }}</p>
    <p><strong>Email:</strong> {{ $contactMessage->email }}</p>
    @if ($contactMessage->phone)
        <p><strong>Điện thoại:</strong> {{ $contactMessage->phone }}</p>
    @endif
    <p><strong>Nội dung:</strong></p>
    <p style="white-space: pre-line; background: #f3f4f6; padding: 12px; border-radius: 8px;">{{ $contactMessage->message }}</p>

    <p style="margin-top: 24px;">
        <a href="{{ route('admin.contacts.index') }}">Xem trong trang quản trị</a>
    </p>
</body>
</html>
