<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\File;

class GallerySlideshow extends Component
{
    public $images = [];

    public function mount()
    {
        $path = public_path('images/gallery');

        if (File::exists($path)) {
            $files = File::files($path);

            foreach ($files as $file) {
                $this->images[] = $file->getFilename();
            }
        }
    }

    public function render()
    {
        return view('livewire.gallery-slideshow')->layout('layouts.app');
    }
}