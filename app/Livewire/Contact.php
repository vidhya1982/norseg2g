<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ContactMessage;
use App\Models\Country;
use App\Services\SendGridService;
use Illuminate\Validation\ValidationException;

class Contact extends Component
{
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $country_code;
    public $message;
    public $captcha;

    public $countries = [];

    protected $rules = [
        'first_name'   => 'required|min:2',
        'last_name'    => 'required|min:2',
        'email'        => 'required|email',
        'phone'        => 'required',
        'country_code' => 'required',
        'message'      => 'required|min:10',
        'captcha'      => 'required|captcha',
    ];

    public function mount(): void
    {
        $this->countries = Country::orderBy('country_name')->get();

        $this->country_code = $this->detectCountryCode();
    }

    /**
     * Detect country code using session country
     * Fallback: Israel (+972)
     */
    private function detectCountryCode(): string
    {
        $sessionCountry = session('country');

        if ($sessionCountry) {
            $country = Country::where('country_name', $sessionCountry)->first();
            if ($country) {
                return '+' . $country->phonecode;
            }
        }

        return '+972';
    }

    public function submit(): void
    {
        if (! $this->validateForm()) {
            return;
        }

        if (! $this->storeMessage()) {
            return;
        }

        $this->sendEmail();

        $this->reset();

        $this->dispatch('toast', type: 'success', message: 'Your message has been received! We will get back to you soon.');
    }

    /**
     * Validate form with proper error message
     */
    private function validateForm(): bool
    {
        try {
            $this->validate();
            return true;
        } catch (ValidationException $e) {
            $message = $e->validator->errors()->has('captcha')
                ? 'Captcha does not match.'
                : 'Some details are missing or incorrect.';

            $this->dispatch('toast', type: 'error', message: $message);
            return false;
        }
    }

    /**
     * Save contact message to database
     */
    private function storeMessage(): bool
    {
        try {
            ContactMessage::create([
                'first_name'   => $this->first_name,
                'last_name'    => $this->last_name,
                'country_code' => $this->country_code,
                'phone'        => $this->phone,
                'email'        => $this->email,
                'message'      => $this->message,
                'ip_address'   => request()->ip(),
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->dispatch(
                'toast',
                type: 'error',
                message: 'We couldn’t save your message right now. Please try again later.'
            );

            return false;
        }
    }

    /**
     * Send email (non-blocking UX)
     */
    private function sendEmail(): void
    {
        try {
            SendGridService::send(
                'esim@gsm2go.com',
                'd-a3d8ef5451ba40b78404a9f9534c7ee8',
                [
                    'firstname' => $this->first_name,
                    'lastname'  => $this->last_name,
                    'email'     => $this->email,
                    'phone'     => $this->country_code . ' ' . $this->phone,
                    'message'   => $this->message,
                ]
            );
        } catch (\Throwable $e) {
            $this->dispatch(
                'toast',
                type: 'warning',
                message: 'Your message has been saved, but email delivery may be delayed.'
            );
        }
    }

    public function render()
    {
        return view('livewire.contact')->layout('layouts.app');
    }
}
