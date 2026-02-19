<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use App\Models\User;
use App\Models\ForgotPasswords;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use App\Services\SendGridService;

class ForgotPassword extends Component
{
    public string $email = '';

    /**
     * Send reset password link
     */
    public function sendResetLink()
    {
        // 1️⃣ Validate email
        $this->validate([
            'email' => 'required|email',
        ]);

        //  Find user
        $user = User::where('email', $this->email)->first();

        if (!$user) {
            $this->addError('email', 'Email not found');
            return;
        }

        //  Generate token (OTP jaisa hi hai)
        $plainToken = Str::random(60);

        //  Hash token for DB
        $hashedToken = hash('sha256', $plainToken);

        //  Old tokens delete (one active token only)
        ForgotPasswords::where('email', $user->email)->delete();

        //  Save reset request in DB
        ForgotPasswords::create([
            'user_id'    => $user->id,
            'email'      => $user->email,
            'token'      => $hashedToken,      // HASHED token
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'expires_at' => now()->addMinutes(20),
        ]);

        //  Send email
        $this->sendResetEmail($user, $plainToken);

        session()->flash('success', 'Reset password link sent to your email.');
    }

    /**
     * Send reset password email (SendGrid)
     */
    private function sendResetEmail(User $user, string $plainToken): void
    {
        //  Encrypt user id & token (old param_encrypt ka replacement)
        $encryptedUserId = Crypt::encryptString($user->id);
        $encryptedToken  = Crypt::encryptString($plainToken);

        //  Final reset link (exact tumhare purane system jaisa)
        $link_url = url(
            'reset-password/setpassword/' .
            $encryptedUserId . '/' .
            $encryptedToken
        );

        //  SendGrid email
        SendGridService::send(
            $user->email, 
            'd-55f080303acf49dcb11e5ff6d318c81d', // template id
            [
                'subject'    => 'Reset Your Password',
                'reset_link' => $link_url,
                'email'      => $user->email,
                'firstname'  => $user->fname ?? 'Customer',
            ]
        );
    }

    /**
     * Render view
     */
    public function render()
    {
        return view('livewire.auth.forgot-password')
            ->layout('layouts.app');
    }
}
