@extends('install.layout', ['step' => 2])

@section('content')
    <h2 class="font-heading text-lg font-bold">Bước 2: Cấu hình cơ sở dữ liệu</h2>
    <p class="mt-1 mb-5 text-sm text-muted-foreground">Nhập thông tin kết nối MySQL. Nhấn "Test Connection" để kiểm tra trước khi tiếp tục.</p>

    @if ($errors->any())
        <x-alert variant="destructive" class="mb-5">{{ $errors->first() }}</x-alert>
    @endif

    <form method="POST" action="{{ route('install.database.store') }}" id="db-form" class="space-y-4">
        @csrf

        <div>
            <x-label for="host">Database Host</x-label>
            <x-input type="text" id="host" name="host" value="{{ old('host', $defaults['host']) }}" required />
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <x-label for="port">Port</x-label>
                <x-input type="text" id="port" name="port" value="{{ old('port', $defaults['port']) }}" required />
            </div>
            <div>
                <x-label for="prefix">Table Prefix</x-label>
                <x-input type="text" id="prefix" name="prefix" value="{{ old('prefix', $defaults['prefix']) }}" />
            </div>
        </div>

        <div>
            <x-label for="database">Database Name</x-label>
            <x-input type="text" id="database" name="database" value="{{ old('database', $defaults['database']) }}" required />
        </div>

        <div>
            <x-label for="username">Username</x-label>
            <x-input type="text" id="username" name="username" value="{{ old('username', $defaults['username']) }}" required />
        </div>

        <div>
            <x-label for="password">Password</x-label>
            <x-input type="password" id="password" name="password" />
        </div>

        <div id="test-result" class="text-sm min-h-5"></div>

        <x-button type="button" variant="outline" id="test-btn">Test Connection</x-button>
        <x-button type="submit" variant="primary">Tiếp tục</x-button>
    </form>

    <script>
        document.getElementById('test-btn').addEventListener('click', async function () {
            const btn = this;
            const result = document.getElementById('test-result');
            const form = document.getElementById('db-form');
            const data = new FormData(form);

            btn.disabled = true;
            btn.textContent = 'Đang kiểm tra...';
            result.textContent = '';

            try {
                const res = await fetch(@json(route('install.database.test')), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value,
                        'Accept': 'application/json',
                    },
                    body: data,
                });
                const json = await res.json();
                result.textContent = json.message;
                result.className = 'text-sm min-h-5 ' + (json.success ? 'text-brand-green' : 'text-destructive');
            } catch (e) {
                result.textContent = 'Không gửi được yêu cầu kiểm tra.';
                result.className = 'text-sm min-h-5 text-destructive';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Test Connection';
            }
        });
    </script>
@endsection
