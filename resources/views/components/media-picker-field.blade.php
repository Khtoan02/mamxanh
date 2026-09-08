@props(['name', 'label', 'value' => null, 'placeholder' => 'https://...', 'hint' => null])

@php
    // Kept identical to $name (not prefixed) so existing scripts that look
    // up a field by its old plain id (e.g. the SEO checklist reading
    // #featured_image) keep working unchanged.
    $fid = $name;
@endphp

<div>
    <x-label :for="$fid">{{ $label }}</x-label>
    <div class="flex gap-2">
        <x-input type="text" :id="$fid" :name="$name" :value="old($name, $value)" :placeholder="$placeholder" class="flex-1" />
        <button type="button" id="{{ $fid }}-picker-btn" class="inline-flex items-center justify-center rounded-lg border border-border px-3 py-2 text-sm font-medium whitespace-nowrap hover:bg-accent">Chọn từ thư viện</button>
    </div>
    @if ($hint)
        <p class="mt-1 text-xs text-muted-foreground">{{ $hint }}</p>
    @endif
    <img id="{{ $fid }}-preview" src="{{ $value }}" alt="" class="mt-2 h-20 rounded-lg border border-border object-cover {{ $value ? '' : 'hidden' }}">

    @push('scripts')
        <script>
            document.getElementById('{{ $fid }}-picker-btn')?.addEventListener('click', () => {
                window.openMediaPicker((item) => {
                    const input = document.getElementById('{{ $fid }}');
                    input.value = item.url;
                    input.dispatchEvent(new Event('input'));
                    const preview = document.getElementById('{{ $fid }}-preview');
                    preview.src = item.url;
                    preview.classList.remove('hidden');
                });
            });
        </script>
    @endpush
</div>
