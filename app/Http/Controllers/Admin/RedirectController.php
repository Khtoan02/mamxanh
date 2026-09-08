<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.redirects.index', [
            'redirects' => Redirect::latest()->paginate(30),
            'prefillSource' => $request->query('source', ''),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        Redirect::create($data);

        return back()->with('status', 'Đã tạo redirect.');
    }

    public function update(Request $request, Redirect $redirect)
    {
        $data = $this->validated($request);

        $redirect->update($data);

        return back()->with('status', 'Đã cập nhật redirect.');
    }

    public function destroy(Redirect $redirect)
    {
        $redirect->delete();

        return back()->with('status', 'Đã xoá redirect.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'source' => ['required', 'string', 'max:500'],
            'target' => ['required', 'string', 'max:500'],
            'match_type' => ['required', 'in:'.implode(',', array_keys(Redirect::MATCH_TYPES))],
            'status_code' => ['required', 'integer', 'in:301,302,307,308'],
        ]);

        $data['source'] = ltrim(trim($data['source']), '/');
        $data['is_active'] = true;

        return $data;
    }
}
