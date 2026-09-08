<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\HealthCheckEmail;
use App\Models\Setting;
use App\Services\EnvironmentWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

/**
 * Settings, split into tabs (general/contact/social/integrations/email/
 * maintenance) rather than one long scrolling form — each tab is its own
 * route + save action, matching the standard CMS pattern (WordPress'
 * options-general.php/options-writing.php/... split) rather than a single
 * flat page. Short, single-line values live in `.env` (via
 * EnvironmentWriter, consistent with the fields already there); only the
 * free-form/multi-line custom scripts live in the `settings` table. The
 * split exists because .env is a line-oriented format: a multi-line
 * tracking snippet written into it would corrupt every key after it.
 */
class SettingsController extends Controller
{
    public function general()
    {
        return view('admin.settings.general', [
            'settings' => [
                'site_title' => config('app.name'),
                'site_tagline' => env('SITE_TAGLINE', ''),
                'locale' => config('app.locale'),
                'timezone' => config('app.timezone'),
                'site_logo' => env('SITE_LOGO', ''),
                'site_favicon' => env('SITE_FAVICON', ''),
            ],
        ]);
    }

    public function updateGeneral(Request $request, EnvironmentWriter $env)
    {
        $data = $request->validate([
            'site_title' => ['required', 'string', 'max:255'],
            'site_tagline' => ['nullable', 'string', 'max:255'],
            'locale' => ['required', 'string', 'in:en,vi'],
            'timezone' => ['required', 'string', 'timezone'],
            'site_logo' => ['nullable', 'string', 'max:500'],
            'site_favicon' => ['nullable', 'string', 'max:500'],
        ]);

        $env->set([
            'APP_NAME' => $data['site_title'],
            'SITE_TAGLINE' => $data['site_tagline'] ?? '',
            'APP_LOCALE' => $data['locale'],
            'APP_TIMEZONE' => $data['timezone'],
            'SITE_LOGO' => $data['site_logo'] ?? '',
            'SITE_FAVICON' => $data['site_favicon'] ?? '',
        ]);

        config([
            'app.name' => $data['site_title'],
            'app.locale' => $data['locale'],
            'app.timezone' => $data['timezone'],
        ]);

        return redirect()->route('admin.settings.general')->with('status', 'Đã lưu cài đặt chung.');
    }

    public function contact()
    {
        return view('admin.settings.contact', [
            'settings' => [
                'contact_email' => env('SITE_CONTACT_EMAIL', ''),
                'contact_phone' => env('SITE_CONTACT_PHONE', ''),
                'address' => env('SITE_ADDRESS', ''),
            ],
        ]);
    }

    public function updateContact(Request $request, EnvironmentWriter $env)
    {
        $data = $request->validate([
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $env->set([
            'SITE_CONTACT_EMAIL' => $data['contact_email'] ?? '',
            'SITE_CONTACT_PHONE' => $data['contact_phone'] ?? '',
            'SITE_ADDRESS' => $data['address'] ?? '',
        ]);

        return redirect()->route('admin.settings.contact')->with('status', 'Đã lưu thông tin liên hệ.');
    }

    public function social()
    {
        return view('admin.settings.social', [
            'settings' => [
                'facebook_url' => env('SITE_FACEBOOK_URL', ''),
                'zalo_url' => env('SITE_ZALO_URL', ''),
                'instagram_url' => env('SITE_INSTAGRAM_URL', ''),
                'tiktok_url' => env('SITE_TIKTOK_URL', ''),
                'youtube_url' => env('SITE_YOUTUBE_URL', ''),
            ],
        ]);
    }

    public function updateSocial(Request $request, EnvironmentWriter $env)
    {
        $data = $request->validate([
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'zalo_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'tiktok_url' => ['nullable', 'url', 'max:255'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
        ]);

        $env->set([
            'SITE_FACEBOOK_URL' => $data['facebook_url'] ?? '',
            'SITE_ZALO_URL' => $data['zalo_url'] ?? '',
            'SITE_INSTAGRAM_URL' => $data['instagram_url'] ?? '',
            'SITE_TIKTOK_URL' => $data['tiktok_url'] ?? '',
            'SITE_YOUTUBE_URL' => $data['youtube_url'] ?? '',
        ]);

        return redirect()->route('admin.settings.social')->with('status', 'Đã lưu mạng xã hội.');
    }

    public function integrations()
    {
        return view('admin.settings.integrations', [
            'settings' => [
                'custom_head_scripts' => Setting::get('custom_head_scripts', ''),
                'custom_footer_scripts' => Setting::get('custom_footer_scripts', ''),
                'woocommerce_url' => Setting::get('woocommerce_url', ''),
                'woocommerce_consumer_key' => Setting::get('woocommerce_consumer_key', ''),
                'woocommerce_consumer_secret' => Setting::get('woocommerce_consumer_secret', ''),
                'woocommerce_sync_enabled' => Setting::get('woocommerce_sync_enabled', ''),
            ],
        ]);
    }

    public function updateIntegrations(Request $request)
    {
        $data = $request->validate([
            'custom_head_scripts' => ['nullable', 'string', 'max:20000'],
            'custom_footer_scripts' => ['nullable', 'string', 'max:20000'],
            'woocommerce_url' => ['nullable', 'url', 'max:255'],
            'woocommerce_consumer_key' => ['nullable', 'string', 'max:255'],
            'woocommerce_consumer_secret' => ['nullable', 'string', 'max:255'],
            'woocommerce_sync_enabled' => ['nullable', 'boolean'],
        ]);

        Setting::set('custom_head_scripts', $data['custom_head_scripts'] ?? '');
        Setting::set('custom_footer_scripts', $data['custom_footer_scripts'] ?? '');
        Setting::set('woocommerce_url', $data['woocommerce_url'] ?? '');
        Setting::set('woocommerce_consumer_key', $data['woocommerce_consumer_key'] ?? '');
        Setting::set('woocommerce_consumer_secret', $data['woocommerce_consumer_secret'] ?? '');
        Setting::set('woocommerce_sync_enabled', ($data['woocommerce_sync_enabled'] ?? false) ? '1' : '');

        return redirect()->route('admin.settings.integrations')->with('status', 'Đã lưu tích hợp.');
    }

    public function email()
    {
        return view('admin.settings.email', [
            'settings' => [
                'mail_mailer' => config('mail.default'),
                'mail_host' => env('MAIL_HOST', ''),
                'mail_port' => env('MAIL_PORT', ''),
                'mail_username' => env('MAIL_USERNAME', ''),
                'mail_password' => env('MAIL_PASSWORD', ''),
                'mail_scheme' => env('MAIL_SCHEME', ''),
                'mail_from_address' => env('MAIL_FROM_ADDRESS', ''),
                'mail_from_name' => env('MAIL_FROM_NAME', ''),
            ],
        ]);
    }

    public function updateEmail(Request $request, EnvironmentWriter $env)
    {
        $data = $request->validate([
            'mail_mailer' => ['required', 'string', 'in:log,smtp'],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_scheme' => ['nullable', 'string', 'in:,smtps'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
        ]);

        $env->set([
            'MAIL_MAILER' => $data['mail_mailer'],
            'MAIL_HOST' => $data['mail_host'] ?? '',
            'MAIL_PORT' => (string) ($data['mail_port'] ?? ''),
            'MAIL_USERNAME' => $data['mail_username'] ?? '',
            // Only overwrite the stored password when a new one was actually
            // typed — the field is rendered blank for security, so a save
            // with it left empty must not wipe out working credentials.
            ...(filled($data['mail_password'] ?? null) ? ['MAIL_PASSWORD' => $data['mail_password']] : []),
            'MAIL_SCHEME' => $data['mail_scheme'] ?? '',
            'MAIL_FROM_ADDRESS' => $data['mail_from_address'] ?? '',
            'MAIL_FROM_NAME' => $data['mail_from_name'] ?? '',
        ]);

        return redirect()->route('admin.settings.email')->with('status', 'Đã lưu cấu hình email.');
    }

    public function sendTestEmail(Request $request)
    {
        try {
            Mail::to($request->user()->email)->send(new HealthCheckEmail);

            return back()->with('status', 'Đã gửi email thử nghiệm tới '.$request->user()->email);
        } catch (\Throwable $e) {
            return back()->withErrors(['email' => 'Gửi thất bại: '.mb_substr($e->getMessage(), 0, 200)]);
        }
    }

    public function maintenance()
    {
        return view('admin.settings.maintenance', [
            'isDown' => app()->isDownForMaintenance(),
        ]);
    }

    public function toggleMaintenance(Request $request)
    {
        if (app()->isDownForMaintenance()) {
            Artisan::call('up');

            return back()->with('status', 'Website đã hoạt động trở lại.');
        }

        // /admin/* and /login stay reachable — the excluded paths are
        // configured in App\Http\Middleware\PreventRequestsDuringMaintenance
        // (Laravel's `down` command no longer takes an --except CLI option;
        // it reads exclusions from that middleware class instead).
        Artisan::call('down');

        return back()->with('status', 'Đã bật chế độ bảo trì — khách chỉ thấy trang bảo trì, bạn vẫn vào được /admin.');
    }
}
