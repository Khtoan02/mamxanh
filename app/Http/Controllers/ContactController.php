<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\NewContactMessage;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function show()
    {
        return view('theme::contact');
    }

    public function store(Request $request)
    {
        // Honeypot: ô này bị ẩn khỏi mắt người và khỏi trình đọc màn hình,
        // nên chỉ bot tự động điền form mới nhập vào. Trả về đúng thông báo
        // thành công như thường để bot không biết mình đã bị chặn và thử
        // cách khác. Đây là lớp lọc rác duy nhất KHÔNG bắt người thật phải
        // giải captcha — đã có throttle:10,1 ở route lo phần dội bom.
        if (filled($request->input('website'))) {
            return back()->with('status', 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        // Lets Marketing attribute this lead back to the channel that first
        // brought this visitor in (see MarketingController) — read from the
        // pageview-tracking cookie, never accepted as request input.
        $data['visitor_id'] = $request->cookie('mxd_vid');

        $contactMessage = ContactMessage::create($data);

        // Best-effort — the message is already safely in the DB (visible at
        // /admin/contacts) regardless of whether SMTP is configured yet.
        // MAIL_MAILER=log until real SMTP creds are added to .env.
        try {
            Mail::to(env('SITE_CONTACT_EMAIL', config('mail.from.address')))
                ->send(new NewContactMessage($contactMessage));
        } catch (\Throwable $e) {
            Log::warning('Failed to send contact notification email: '.$e->getMessage());
        }

        return back()->with('status', 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất.');
    }
}
