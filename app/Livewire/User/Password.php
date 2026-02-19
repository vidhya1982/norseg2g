<?php

namespace App\Livewire\User;

use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class Password extends Component
{
    public $current_password;
    public $password;
    public $password_confirmation;

    /**
     * Validation rules
     */
    protected function rules()
    {
        return [
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ];
    }

    /**
     * Update password
     */
    public function updatePassword()
    {
        // 1️⃣ Validate inputs
        $this->validate();

        $user = auth()->user();
        $currentPassword = $this->current_password;
        $storedPassword = $user->password;

        $passwordValid = false;

        /* =====================================
           2️⃣ CHECK PASSWORD (BCRYPT OR MD5)
        ===================================== */

        // ✅ Bcrypt (new users)
        if (str_starts_with($storedPassword, '$2y$')) {
            $passwordValid = Hash::check($currentPassword, $storedPassword);
        }

        // ✅ MD5 (legacy users)
        elseif (strlen($storedPassword) === 32) {
            $passwordValid = md5($currentPassword) === $storedPassword;
        }

        /* =====================================
           ❌ INVALID CURRENT PASSWORD
        ===================================== */
        if (! $passwordValid) {

            $this->dispatch('toast',
                type: 'error',
                message: 'Current password is incorrect'
            );

            throw ValidationException::withMessages([
                'current_password' => 'Current password is incorrect.',
            ]);
        }

        /* =====================================
           3️⃣ UPDATE PASSWORD (ALWAYS BCRYPT)
        ===================================== */
        $user->update([
            'password' => Hash::make($this->password),
        ]);

        /* =====================================
           4️⃣ RESET FORM
        ===================================== */
        $this->reset([
            'current_password',
            'password',
            'password_confirmation',
        ]);

        /* =====================================
           ✅ SUCCESS
        ===================================== */
        $this->dispatch('toast',
            type: 'success',
            message: 'Password updated successfully'
        );
    }

    /**
     * Render view
     */
    public function render()
    {
        return view('livewire.user.password')
            ->layout('layouts.app');
    }
}
