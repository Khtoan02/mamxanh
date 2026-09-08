@extends('layouts.admin')

@section('title', $user->exists ? 'Sửa tài khoản' : 'Thêm tài khoản')

@section('content')
    @if ($errors->any())
        <x-alert variant="destructive" class="mb-5">{{ $errors->first() }}</x-alert>
    @endif

    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="space-y-5 max-w-2xl">
        @csrf
        @if ($user->exists)
            @method('PUT')
        @endif

        <x-card class="p-6 space-y-4">
            <div>
                <x-label for="name">Tên</x-label>
                <x-input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required autofocus />
            </div>

            <div>
                <x-label for="email">Email</x-label>
                <x-input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required />
            </div>

            <div>
                <x-label for="role">Role</x-label>
                <select id="role" name="role" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                    @foreach ($roles as $role)
                        <option value="{{ $role->slug }}" @selected(old('role', $user->role) === $role->slug)>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-label for="job_title">Chức danh (không bắt buộc)</x-label>
                <x-input type="text" id="job_title" name="job_title" value="{{ old('job_title', $user->job_title) }}" placeholder="vd: Content Writer, SEO Specialist" />
                <p class="mt-1 text-xs text-muted-foreground">Hiện trong thông tin tác giả của bài viết — giúp thể hiện chuyên môn thật (E-E-A-T).</p>
            </div>

            <div>
                <x-label for="bio">Giới thiệu ngắn (không bắt buộc)</x-label>
                <textarea id="bio" name="bio" rows="3" maxlength="1000" class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring">{{ old('bio', $user->bio) }}</textarea>
            </div>

            <div>
                <x-label for="password">{{ $user->exists ? 'Mật khẩu mới (để trống nếu không đổi)' : 'Mật khẩu' }}</x-label>
                <x-input type="password" id="password" name="password" {{ $user->exists ? '' : 'required' }} minlength="8" />
            </div>

            <div>
                <x-label for="password_confirmation">Xác nhận mật khẩu</x-label>
                <x-input type="password" id="password_confirmation" name="password_confirmation" {{ $user->exists ? '' : 'required' }} minlength="8" />
            </div>
        </x-card>

        <div class="flex gap-3">
            <x-button type="submit" variant="primary" class="!w-auto px-6">{{ $user->exists ? 'Cập nhật' : 'Lưu' }}</x-button>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center rounded-lg border border-border px-4 py-2.5 text-sm font-semibold hover:bg-accent">Huỷ</a>
        </div>
    </form>
@endsection
