<?php

namespace App\Http\Controllers\Frontend;

use App\Business;
use App\Contact;
use App\Notifications\StorefrontResetPassword;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
            'email' => 'required|string|max:255',
        ]);

        $businessId = $this->resolveBusinessId($request);
        $business = Business::findOrFail($businessId);
        $contact = $this->findStoreCustomerByLogin($validated['email'], $businessId);

        if (empty($contact)) {
            return $this->authBack($request, false, __('storefront.auth.no_account_for_email'));
        }

        $broker = Password::broker('contacts');

        if ($broker->getRepository()->recentlyCreatedToken($contact)) {
            return $this->authBack($request, false, __('storefront.auth.reset_throttled'));
        }

        $token = $broker->createToken($contact);
        $sent = $this->deliverResetLink($contact, $business, $token);

        if (! $sent['sms'] && ! $sent['email']) {
            $broker->deleteToken($contact);

            return $this->authBack($request, false, __('storefront.auth.reset_link_failed'));
        }

        $messageKey = match (true) {
            $sent['sms'] && $sent['email'] => 'storefront.auth.reset_link_sent_both',
            $sent['sms'] => 'storefront.auth.reset_link_sent_sms',
            default => 'storefront.auth.reset_link_sent_email',
        };

        return $this->authBack($request, true, __($messageKey));
    }

    public function showResetPassword(string $token, Request $request)
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('welcome');
        }

        $email = (string) $request->query('email', '');
        $contact = $email !== ''
            ? $this->findStoreCustomerForReset($email, $this->resolveBusinessId($request))
            : null;

        if (empty($contact) || ! Password::broker('contacts')->tokenExists($contact, $token)) {
            return redirect()->route('store.auth.password.request')->with('status', [
                'success' => false,
                'msg' => __('storefront.auth.reset_token_invalid'),
            ]);
        }

        return view('frontend.store.auth.reset_password')->with([
            'token' => $token,
            'email' => $email,
            'showEmail' => ! str_ends_with($email, '@customers.invalid'),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'email' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $contact = $this->findStoreCustomerForReset($validated['email'], $this->resolveBusinessId($request));

        if (empty($contact) || ! Password::broker('contacts')->tokenExists($contact, $validated['token'])) {
            return $this->authBack($request, false, __('storefront.auth.reset_token_invalid'));
        }

        $contact->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        Password::broker('contacts')->deleteToken($contact);

        if (! $request->expectsJson()) {
            return redirect()->route('store.auth.login.form')->with('status', [
                'success' => true,
                'msg' => __('passwords.reset'),
            ]);
        }

        return $this->respond([
            'success' => true,
            'msg' => __('passwords.reset'),
        ]);
    }

    public function register(Request $request)
    {
        $business_id = $this->resolveBusinessId($request);
        $business = Business::findOrFail($business_id);

        $request->merge([
            'mobile' => trim((string) $request->input('mobile')),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('contacts', 'email')->where(function ($q) use ($business_id) {
                    return $q->where('business_id', $business_id);
                }),
            ],
            'password' => 'required|string|min:8|confirmed',
            'mobile' => [
                'required',
                'string',
                'max:30',
                Rule::unique('contacts', 'mobile')->where(function ($q) use ($business_id) {
                    return $q->where('business_id', $business_id)
                        ->where('type', 'app_customer')
                        ->whereNull('deleted_at');
                }),
            ],
        ], [
            'mobile.unique' => __('storefront.auth.mobile_taken'),
        ]);

        $ref_count = $this->commonUtil->setAndGetReferenceCount('contacts', $business_id);

        $contact = Contact::create([
            'business_id' => $business_id,
            'type' => 'app_customer',
            'name' => $validated['name'],
            'email' => $validated['email'] ?: null,
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

    private function authBack(Request $request, bool $success, string $message)
    {
        if ($success) {
            if (! $request->expectsJson()) {
                return back()->with('status', [
                    'success' => true,
                    'msg' => $message,
                ]);
            }

            return $this->respond([
                'success' => true,
                'msg' => $message,
            ]);
        }

        if (! $request->expectsJson()) {
            return back()->withErrors(['email' => $message])->withInput();
        }

        return $this->respond([
            'success' => false,
            'msg' => $message,
        ]);
    }

    private function findStoreCustomerByLogin(string $login, int $businessId): ?Contact
    {
        $login = trim($login);

        return Contact::query()
            ->where('business_id', $businessId)
            ->whereIn('type', ['app_customer', 'both'])
            ->where('contact_status', 'active')
            ->whereNotNull('password')
            ->where('password', '!=', '')
            ->where(function ($query) use ($login) {
                $query->where('email', $login)
                    ->orWhere('mobile', $login);
            })
            ->first();
    }

    private function findStoreCustomerForReset(string $resetKey, int $businessId): ?Contact
    {
        $query = Contact::query()
            ->where('business_id', $businessId)
            ->whereIn('type', ['app_customer', 'both'])
            ->where('contact_status', 'active');

        if (preg_match('/^contact-(\d+)@customers\.invalid$/', $resetKey, $matches)) {
            return $query->whereKey((int) $matches[1])
                ->where(function ($inner) {
                    $inner->whereNull('email')->orWhere('email', '');
                })
                ->first();
        }

        return $query->where('email', $resetKey)->first();
    }

    /**
     * @return array{sms: bool, email: bool}
     */
    private function deliverResetLink(Contact $contact, Business $business, string $token): array
    {
        $url = route('store.auth.password.reset.form', [
            'token' => $token,
            'email' => $contact->getEmailForPasswordReset(),
        ]);

        return [
            'sms' => $this->sendResetSms($contact, $business, $url),
            'email' => $this->sendResetEmail($contact, $business, $token),
        ];
    }

    private function sendResetSms(Contact $contact, Business $business, string $url): bool
    {
        $mobile = trim((string) $contact->mobile);
        $smsSettings = $business->sms_settings ?? [];

        if ($mobile === '' || $mobile === '0' || empty($smsSettings['sms_service'])) {
            return false;
        }

        try {
            $result = $this->commonUtil->sendSms([
                'sms_settings' => $smsSettings,
                'mobile_number' => $mobile,
                'sms_body' => __('storefront.auth.reset_sms', ['url' => $url]),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }

        return $result !== false;
    }

    private function sendResetEmail(Contact $contact, Business $business, string $token): bool
    {
        if (trim((string) $contact->email) === '' || ! $this->applyBusinessMailConfig($business)) {
            return false;
        }

        try {
            $contact->notify(new StorefrontResetPassword($token));
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }

        return true;
    }

    private function applyBusinessMailConfig(Business $business): bool
    {
        $settings = $business->email_settings ?? [];
        $host = $settings['mail_host'] ?? null;
        $from = $settings['mail_from_address'] ?? null;

        if (empty($host) || empty($from)) {
            return false;
        }

        $encryption = $settings['mail_encryption'] ?? null;

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', $host);
        Config::set('mail.mailers.smtp.port', $settings['mail_port'] ?? 587);
        Config::set('mail.mailers.smtp.encryption', $encryption === 'none' ? null : $encryption);
        Config::set('mail.mailers.smtp.username', $settings['mail_username'] ?? null);
        Config::set('mail.mailers.smtp.password', $settings['mail_password'] ?? null);
        Config::set('mail.from.address', $from);
        Config::set('mail.from.name', $settings['mail_from_name'] ?? config('app.name'));

        Mail::purge('smtp');

        return true;
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

