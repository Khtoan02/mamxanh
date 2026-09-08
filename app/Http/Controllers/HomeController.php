<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Post;

class HomeController extends Controller
{
    public function index()
    {
        $projects = Post::ofType('project')->published()->with('meta')->latest('published_at')->get();

        return view('theme::home', [
            'services' => Post::ofType('service')->published()->latest('published_at')->limit(3)->get(),
            'projects' => $projects->take(3),
            // Tên khách hàng thật lấy trực tiếp từ field "Khách hàng" đã có
            // trên Dự án — không phải danh sách đối tác riêng tự nhập, nên
            // không có nguồn nào để bịa thêm; rỗng thì chương "Đối tác đồng
            // hành" tự ẩn (cùng pattern @if(isNotEmpty) như Dịch vụ).
            'partners' => $projects->map(fn ($p) => $p->getMeta('client_name'))->filter()->unique()->values(),
            'products' => Post::ofType('product')->published()->latest('published_at')->limit(3)->get(),
            'recentPosts' => Post::ofType('post')->published()->with('author')->latest('published_at')->limit(3)->get(),
        ]);
    }
}
