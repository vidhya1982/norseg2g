<?php
namespace App\Livewire\Auth;

use App\Models\Country;
use Livewire\Component;

class SignUp extends Component
{
    public $country = 'India';
    public $country_code = '+91';
    public $countries = [];

    protected $listeners = [
        'set-country' => 'updateCountry',
    ];

    public function mount(): void
    {
        $this->countries = Country::orderBy('country_name')->get();
    }

    public function updateCountry(string $name, string $code): void
    {
        $this->country = $name;
        $this->country_code = $code;
    }

    public function render()
    {
        return view('livewire.auth.sign-up')->layout('layouts.app');
    }
}
