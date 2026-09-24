<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminLoginRequest;
use Auth;
use Illuminate\Http\Request;

class AdminAuthController extends Controller
{
    public function create()
    {
        return view('admin.admin-login');
    }

    public function store(AdminLoginRequest $request)
    {
        $validated = $request->validated();
        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        if (Auth::attempt($credentials)) {
            if (Auth::user()->admin_status) {
                $request->session()->regenerate();
                return redirect()->intended('/admin/attendance/list');
            }

            Auth::logout();
            return back()->withErrors(['email' => '管理者権限がありません']);
        }

        return back()->withErrors(['email' => 'ログイン情報が登録されていません']);
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}
