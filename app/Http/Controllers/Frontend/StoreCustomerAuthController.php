<?php

namespace App\Http\Controllers\Frontend;

use App\Business;
use App\Contact;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;

class StoreCustomerAuthController extends Controller
{
    public function __construct(private Util $commonUtil) {}

    public function showRegister()
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('welcome');
        }

        return view('frontend.store.auth.register');
    }

    public function showLogin()
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('welcome');
        }

        return view('frontend.store.auth.login');
    }

    public function showForgotPassword()
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('welcome');
        }

        return view('frontend.store.auth.forgot_password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $contact = Contact::where('email', $validated['email'])
            ->whereIn('type', ['customer', 'both'])
            ->where('contact_status', 'active')
            ->first();

        if (empty($contact)) {
            return back()->withErrors(['email' => __('storefront.auth.no_account_for_email')])->withInput();
        }

        $status = Password::broker('contacts')->sendResetLink(
            ['email' => $validated['email']]
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', [
                'success' => true,
                'msg' => __($status),
            ]);
        }

        return back()->withErrors(['email' => __($status)])->withInput();
    }

    public function showResetPassword(string $token, Request $request)
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('welcome');
        }

        if (! $request->filled('email')) {
            return redirect()->route('store.auth.password.request')->with('status', [
                'success' => false,
                'msg' => 'Invalid reset link.',
            ]);
        }

        return view('store.auth.reset_password')->with([
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::broker('contacts')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Contact $contact, string $password) {
                if (! in_array($contact->type, ['customer', 'both'], true)) {
                    return;
                }
                $contact->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('store.auth.login.form')->with('status', [
                'success' => true,
                'msg' => __($status),
            ]);
        }

        return back()->withErrors(['email' => [__($status)]])->withInput();
    }

    public function register(Request $request)
    {
        $business_id = $this->resolveBusinessId($request);
        $business = Business::findOrFail($business_id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('contacts', 'email')->where(function ($q) use ($business_id) {
                    return $q->where('business_id', $business_id);
                }),
            ],
            'password' => 'required|string|min:8|confirmed',
            'mobile' => 'required|string|max:30|unique:contacts,mobile',
        ]);

        $ref_count = $this->commonUtil->setAndGetReferenceCount('contacts', $business_id);

        $contact = Contact::create([
            'business_id' => $business_id,
            'type' => 'app_customer',
            'name' => $validated['name'],
            'email' => $validated['email'],
            'mobile' => $validated['mobile'] ?? '0',
            'contact_status' => 'active',
            'created_by' => $business->owner_id,
            'contact_id' => $this->commonUtil->generateReferenceNumber('contacts', $ref_count, $business_id),
            'password' => Hash::make($validated['password']),
        ]);

        Auth::guard('customer')->login($contact);

        if (! $request->expectsJson()) {
            return redirect()->route('welcome')->with('status', [
                'success' => true,
                'msg' => 'Customer registered successfully.',
            ]);
        }

        return $this->respond([
            'success' => true,
            'msg' => 'Customer registered successfully.',
            'customer' => $this->customerPayload($contact),
        ]);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            // Keep request key as "email" to avoid breaking the form,
            // but allow entering either an email or a mobile number.
            'email' => 'required|string|max:255',
            'password' => 'required|string',
            'remember' => 'nullable|boolean',
        ]);

        $remember = (bool) ($validated['remember'] ?? false);
        $login = trim($validated['email']);

        $contact = Contact::query()
            ->where(function ($q) use ($login) {
                $q->where('email', $login)
                    ->orWhere('mobile', $login);
            })
            ->whereIn('type', ['app_customer', 'both'])
            ->where('contact_status', 'active')
            ->first();

        if (empty($contact) || empty($contact->password) || ! Hash::check($validated['password'], $contact->password)) {
            if (! $request->expectsJson()) {
                return back()->withErrors(['email' => __('storefront.auth.invalid_credentials')])->withInput();
            }

            return $this->respond([
                'success' => false,
                'msg' => __('storefront.auth.invalid_credentials'),
            ]);
        }

        Auth::guard('customer')->login($contact, $remember);

        if (! $request->expectsJson()) {
            return redirect()->intended(route('welcome'));
        }

        return $this->respond([
            'success' => true,
            'msg' => __('storefront.auth.logged_in_success'),
            'customer' => $this->customerPayload($contact),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if (! $request->expectsJson()) {
            return redirect()->route('welcome');
        }

        return $this->respond([
            'success' => true,
            'msg' => 'Logged out.',
        ]);
    }

    private function customerPayload(Contact $contact): array
    {
        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->email,
            'mobile' => $contact->mobile,
            'business_id' => $contact->business_id,
        ];
    }

    private function resolveBusinessId(Request $request): int
    {
return 273;
//         if ($request->filled('business_id')) {
//             return (int) $request->input('business_id');
//         }

//         return (int) Business::query()->value('id');
    }
}

