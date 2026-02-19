<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Faq as FaqModel;

class Faq extends Component
{
    public $faqs = [];
    public string $lang;

    public function mount()
    {
        // current language
        $this->lang = app()->getLocale();

        // fallback to English
        if (!in_array($this->lang, ['en', 'he', 'ar'])) {
            $this->lang = 'en';
        }

        $this->faqs = FaqModel::where('status', 'A')
            ->orderBy('id')
            ->get();
    }

    public function render()
    {
        return view('livewire.faq')
            ->layout('layouts.app');
    }
}
