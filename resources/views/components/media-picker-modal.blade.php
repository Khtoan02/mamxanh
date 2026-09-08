{{-- Shared media picker modal — one instance per page, opened via
     window.openMediaPicker(onSelect) from any <x-media-picker-field>. --}}
<div id="media-picker-overlay" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-2xl max-h-[85vh] overflow-y-auto rounded-xl border border-border bg-card p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-heading text-base font-bold">Chọn ảnh</h2>
            <button type="button" id="media-picker-close" class="text-muted-foreground hover:text-foreground">
                <x-icon name="x" />
            </button>
        </div>

        <div id="media-picker-grid" class="grid grid-cols-3 sm:grid-cols-4 gap-2 min-h-[80px]"></div>

        <form id="media-picker-upload-form" class="mt-5 pt-5 border-t border-border flex items-end gap-3">
            @csrf
            <div class="flex-1">
                <x-label for="media-picker-file">Hoặc tải ảnh mới</x-label>
                <input type="file" id="media-picker-file" name="file" accept="image/*" required class="block w-full text-sm text-muted-foreground file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary-foreground">
            </div>
            <x-button type="submit" variant="outline" class="!w-auto px-4 shrink-0">Tải lên</x-button>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        // Assigned on window (not `let`/top-level `function`) because Turbo
        // Drive re-executes this script, unchanged, in the SAME JS realm on
        // every navigation to any page that includes this component (e.g.
        // Settings General -> Content Editor) — a `let`/`function`
        // re-declaration would throw "already been declared" on the second
        // visit. Plain window-property assignment is idempotent.
        window.mediaPickerCallback = null;

        window.openMediaPicker = function (onSelect) {
            window.mediaPickerCallback = onSelect;
            const overlay = document.getElementById('media-picker-overlay');
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
            window.loadMediaPickerGrid();
        };

        window.closeMediaPicker = function () {
            const overlay = document.getElementById('media-picker-overlay');
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
            window.mediaPickerCallback = null;
        };

        window.loadMediaPickerGrid = function () {
            const grid = document.getElementById('media-picker-grid');
            grid.innerHTML = '<p class="col-span-full text-sm text-muted-foreground py-6 text-center">Đang tải...</p>';

            fetch(@json(route('admin.media.picker')), { headers: { Accept: 'application/json' } })
                .then((res) => res.json())
                .then((items) => {
                    if (!items.length) {
                        grid.innerHTML = '<p class="col-span-full text-sm text-muted-foreground py-6 text-center">Chưa có ảnh nào — tải ảnh mới bên dưới.</p>';
                        return;
                    }

                    grid.innerHTML = '';
                    items.forEach((item) => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.title = item.name;
                        btn.className = 'rounded-lg overflow-hidden border border-border hover:border-primary transition-colors';
                        btn.innerHTML = `<img src="${item.thumb}" alt="${item.name}" class="w-full h-20 object-cover">`;
                        btn.addEventListener('click', () => {
                            if (window.mediaPickerCallback) window.mediaPickerCallback(item);
                            window.closeMediaPicker();
                        });
                        grid.appendChild(btn);
                    });
                });
        };

        document.getElementById('media-picker-close').addEventListener('click', window.closeMediaPicker);
        document.getElementById('media-picker-overlay').addEventListener('click', (e) => {
            if (e.target.id === 'media-picker-overlay') window.closeMediaPicker();
        });

        document.getElementById('media-picker-upload-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const form = this;
            const formData = new FormData(form);

            fetch(@json(route('admin.media.store')), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value,
                    'Accept': 'application/json',
                },
                body: formData,
            })
                .then((res) => res.ok ? res.json() : Promise.reject())
                .then((json) => {
                    if (window.mediaPickerCallback) window.mediaPickerCallback({ url: json.location, name: 'upload' });
                    window.closeMediaPicker();
                    form.reset();
                })
                .catch(() => alert('Tải ảnh lên thất bại.'));
        });
    </script>
@endpush
