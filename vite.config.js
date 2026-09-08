import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/admin.js', 'resources/js/rum.js'],
            refresh: true,
            fonts: [
                // Newsreader (chương/tiêu đề/trích dẫn — serif kể chuyện, có
                // italic thật cho phần trích dẫn) + Be Vietnam Pro (nội
                // dung/UI — sans hiện đại do 1 foundry Việt Nam làm riêng
                // cho tiếng Việt). Cả 2 đã xác minh có subset "vietnamese"
                // đầy đủ dấu trước khi chọn (xem ARCHITECTURE.md) — PHẢI
                // khai báo subsets rõ ràng, mặc định của plugin chỉ có
                // 'latin' (không dấu).
                bunny('Newsreader', {
                    weights: [400, 500, 600, 700],
                    styles: ['normal', 'italic'],
                    subsets: ['latin', 'vietnamese'],
                }),
                bunny('Be Vietnam Pro', {
                    weights: [400, 500, 600, 700, 800],
                    subsets: ['latin', 'vietnamese'],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
