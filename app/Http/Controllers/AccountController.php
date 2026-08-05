<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function profile()
    {
        app()->setLocale(session('locale', config('app.locale')));

        $user = auth()->user();

        return view('pages.account.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
        ]);

        $user = $request->user();
        $user->update($validated);

        return back()->with('success', __('account.profile_updated'));
    }

    public function orders()
    {
        app()->setLocale(session('locale', config('app.locale')));

        $user = auth()->user();
        $orders = $user->orders()->latest()->paginate(20);

        return view('pages.account.orders', compact('user', 'orders'));
    }
}
