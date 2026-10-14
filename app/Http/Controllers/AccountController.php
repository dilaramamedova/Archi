<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            // users.phone is UNIQUE; without this rule a number already taken by
            // another account reached the database and came back as a 500.
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
        ]);

        // `name` is the column the navbar, the checkout prefill and the seller's
        // order view read, so it has to follow the two parts it is built from
        // (RegisterController composes it the same way).
        $validated['name'] = trim($validated['first_name'].' '.($validated['last_name'] ?? ''));

        $user->update($validated);

        return back()->with('success', t('account.profile_updated'));
    }

    public function orders()
    {
        app()->setLocale(session('locale', config('app.locale')));

        $user = auth()->user();

        // The list shows what was ordered, so the line items come with it.
        $orders = $user->orders()->with('items.product')->latest()->paginate(20);

        return view('pages.account.orders', compact('user', 'orders'));
    }
}
