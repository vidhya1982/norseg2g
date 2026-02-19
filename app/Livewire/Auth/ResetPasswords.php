<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use App\Models\User;
use App\Models\ForgotPasswords;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;

class ResetPasswords extends Component
{
    public ?int $userId = null;
    public ?string $plainToken = null;

    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $uid, string $token)
    {
        $this->userId     = Crypt::decryptString($uid);
        $this->plainToken = Crypt::decryptString($token);
    }

    public function resetPassword()
    {
        if (!$this->userId || !$this->plainToken) {
            abort(404);
        }

        $this->validate([
            'password' => 'required|confirmed|min:8',
        ]);

        $record = ForgotPasswords::where('user_id', $this->userId)
            ->where('token', hash('sha256', $this->plainToken))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            $this->addError('password', 'Invalid or expired reset link');
            return;
        }

        $user = User::findOrFail($this->userId);
        $user->password = Hash::make($this->password);
        $user->save();

        $record->used_at = now();
        $record->save();

        return redirect()->route('login')
            ->with('success', 'Password updated successfully');
    }

    public function render()
    {
        return view('livewire.auth.reset-passwords')
            ->layout('layouts.app');
    }
}
