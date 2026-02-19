<?php

namespace App\Livewire\Auth;

use App\Services\SendGridService;
use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Log;

class Login extends Component
{
    public string $email = '';
    public string $password = '';


    /* =========================
        AUTO CALLBACK DETECT
    ========================== */
    public function mount()
    {
        if (request()->routeIs('google.callback')) {
            $this->handleSocialCallback('google');
        }

        if (request()->routeIs('apple.callback')) {
            $this->handleSocialCallback('apple');
        }
    }


    /* =========================
        EMAIL / PASSWORD LOGIN
    ========================== */
    public function login()
    {
        $this->validate([
            'email' => [
                'required',
                'email',
                function ($attr, $value, $fail) {
                    $blocked = [
                        'gmail.com',
                        'yahoo.com',
                        'hotmail.com',
                        'outlook.com',
                        'live.com',
                        'icloud.com',
                    ];

                    $allowedTestEmails = [
                        'me.sagedev@gmail.com',
                    ];

                    if (in_array(strtolower($value), $allowedTestEmails)) {
                        return;
                    }

                    $domain = strtolower(substr(strrchr($value, '@'), 1));
                    if (in_array($domain, $blocked)) {
                        $fail(
                            'Sorry; we accept Gmail and Apple login but not Gmail email addresses.'
                        );
                    }
                },
            ],
            'password' => 'required',
        ], messages: [
            'email.required' => 'This value is required.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
        ]);

        $user = User::where('email', $this->email)->first();

        // ❗ social-only users (password NULL) block
        if (!$user || empty($user->password)) {
            $this->addError('email', 'Invalid email or password.');
            return;
        }

        $storedPassword = $user->password;
        $passwordValid = false;

        // bcrypt
        if (str_starts_with($storedPassword, '$2y$')) {
            $passwordValid = Hash::check($this->password, $storedPassword);
        }
        // md5
        elseif (strlen($storedPassword) === 32) {
            $passwordValid = md5($this->password) === $storedPassword;
        }

        if (!$passwordValid) {
            $this->addError('email', 'Invalid email or password.');
            return;
        }

        Auth::login($user);
        if (session()->get('redirect_after_login') === 'checkout') {
            session()->forget('redirect_after_login');
            return redirect()->route('checkout');
        }

        return redirect()->route('user.dashboard');
    }

    /* =========================
        GOOGLE LOGIN
    ========================== */
    public function google()
    {
        return redirect()->away(
            Socialite::driver('google')->redirect()->getTargetUrl()
        );
    }

    /* =========================
        APPLE LOGIN
    ========================== */
    public function apple()
    {
        return redirect()->away(
            Socialite::driver('apple')->redirect()->getTargetUrl()
        );
    }

    /* =========================
        SOCIAL CALLBACK HANDLER
    ========================== */
    private function handleSocialCallback(string $provider)
    {
        $social = Socialite::driver($provider)->stateless()->user();

        $email = $social->getEmail();
        $oauthId = $social->getId();

        // Apple Hide-My-Email block (same as old CI)
        if ($provider === 'apple' && str_contains($email, 'privaterelay.appleid.com')) {
            session()->flash(
                'error',
                'For safety of our eSIM services we do not accept Hide My Email addresses.'
            );
            return redirect()->route('login');
        }

        $user = User::where('email', $email)->first();

        $update = [
            'oauth_provider' => $provider,
            'oauth_uid' => $oauthId,
            'oauth_modified' => now(),
            'last_login' => now(),
            'login_type' => 'SSO-' . strtoupper($provider),
        ];

        if ($provider === 'google') {
            $update['picture'] = $social->getAvatar();
        }

        if ($user) {
            if ($user->status !== 'A') {
                session()->flash('error', 'Your account is inactive.');
                return redirect()->route('login');
            }

            $user->update($update);
        } else {
            $user = User::create(array_merge($update, [
                'fname' => $social->user['given_name'] ?? '',
                'lname' => $social->user['family_name'] ?? '',
                'email' => $email,

                'mobile' => '',
                'company' => '',
                'password' => '',
                'emailCode' => '',
                'mobileCode' => '',
                'isdcode' => '',
                'emailMsg' => '',          // ADD THIS
                'verifyMobile' => 'NO',

                'country' => '',
                'role' => 'U',
                'status' => 'A',
                'verifyEmail' => 'YES',
            ]));


        }

        Auth::login($user);


        if (session()->get('redirect_after_login') === 'checkout') {
            session()->forget('redirect_after_login');
            return redirect()->route('checkout');
        }

        return redirect()->route('user.dashboard');
    }



    private function sendLoginEmail(User $user): void
    {
        try {
            SendGridService::send(
                $user->email,
                'd-LOGIN-SUCCESS-TEMPLATE-ID', // SendGrid template id
                [
                    'firstname' => $user->fname ?? '',
                    'lastname' => $user->lname ?? '',
                    'email' => $user->email,
                    'login_type' => $user->login_type,
                ]
            );
        } catch (\Throwable $e) {

            Log::warning('Login email failed', [
                'user_id' => $user->id ?? null,
                'email' => $user->email ?? null,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch(
                'toast',
                type: 'warning',
                message: 'Login successful, but email delivery may be delayed.'
            );
        }
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.app');
    }
}
