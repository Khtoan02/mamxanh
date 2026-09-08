<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Support\WooCommerceClient;
use App\Support\WooCommerceSyncService;

class WooCommerceController extends Controller
{
    public function import(WooCommerceSyncService $sync, WooCommerceClient $client)
    {
        if (! $client->isConfigured()) {
            return back()->with('status', 'Chưa cấu hình WooCommerce — vào Cài đặt → Tích hợp trước.');
        }

        $result = $sync->import();

        return back()->with('status', "Đã nhập {$result['imported']} sản phẩm mới, bỏ qua {$result['skipped']} sản phẩm đã liên kết.");
    }

    public function resync(Post $post, WooCommerceSyncService $sync)
    {
        abort_unless($post->post_type === 'product', 404);

        $sync->push($post);

        $error = $post->getMeta('woocommerce_sync_error');

        return back()->with('status', $error ?: 'Đã đồng bộ sang WooCommerce.');
    }
}
