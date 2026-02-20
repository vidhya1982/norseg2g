@php
    $country = session('country');

    $languages =
        $country === 'Israel' ? ['en' => 'English', 'he' => 'Hebrew', 'ar' => 'Arabic'] : ['en' => 'English', 'ar' => 'Arabic'];
@endphp



<!-- TOP STRIP -->
<div class="fluid-container header-top py-2 text-center">

    <!-- <span class="text-white">{{ __('home.get_upto') }}</span>
        {{ __('home.newacc') }}
        <button class="plan-btn text-white">{{ __('home.view_plan') }}</button>
        {{ __('home.limited_time') }} -->
    <span class="">Roaming Professionals Since 2009</span>
</div>


<!-- MAIN HEADER -->
<header class="main-header">
    <!-- 1) NAVBAR (same as before, small changes: toggler data-target removed) -->
    <nav class="navbar navbar-expand-lg navbar-light container position-relative py-2">

        <!-- HAMBURGER (left on mobile) -->
        <button id="mobileMenuBtn" class="navbar-toggler d-lg-none" type="button" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </button>

        <!-- LOGO: will be centered on mobile -->
        <a class="navbar-brand logo-center-mobile" href="{{ url('/') }}">
            <img src="{{ asset('images/gsmLogo.png') }}" alt="logo" class="logo-img">
        </a>

        

 <a id="mobileDestBtn" href="{{ auth()->check() ? route('user.dashboard') : route('login') }}">
                <button class="d-lg-none view-button">
                     {{ auth()->check() ? 'My Account' : 'Login' }}<i class="fa-solid fa-user"></i>
                </button>
            </a>
        <!-- DESKTOP MENU + RIGHT SECTION (visible on lg and up) -->
        <div class="collapse navbar-collapse d-none d-lg-flex justify-content-center" id="navbarSupportedContent">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="{{ url('/#topPlans') }}">{{ __('menu.plans') }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('guide') }}">{{ __('menu.guide') }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('faq') }}">{{ __('menu.faq') }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('business') }}">{{ __('menu.postpaid') }}</a>
                </li>
                <li class="nav-item"><a class="nav-link" href="{{ route('about') }}">{{ __('menu.aboutus') }}</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('contact') }}">{{ __('menu.contact') }}</a>
                </li>
            </ul>
        </div>

        <div class="d-none d-lg-flex ml-auto align-items-center right-section gap-3">
           



            <a href="{{ auth()->check() ? route('user.dashboard') : route('login') }}">
                <button class="view-button">
                    {{ auth()->check() ? 'My Account' : 'Login' }} <i class="fa-solid fa-user"></i>
                </button>
            </a>

        </div>

    </nav>


    <!-- 2) MOBILE SIDEBAR (off-canvas) -->
    <div id="mobileSidebar" class="mobile-sidebar" aria-hidden="true" role="dialog" aria-modal="true">
        <button id="closeSidebar" class="close-sidebar" aria-label="Close menu">&times;</button>

        <div class="sidebar-content">
            <ul class="mobile-nav-list">
                <li><a href="{{ url('/#topPlans') }}">Plans</a></li>
                <li><a href="{{ route('guide') }}">Guide</a></li>
                <li><a href="{{ route('faq') }}">FAQ</a></li>
                <li><a href="{{ route('business') }}">For Business</a></li>
                <li><a href="{{ route('about') }}">About Us</a></li>
                <li><a href="{{ route('contact') }}">Contact</a></li>
            </ul>

            <div class="sidebar-bottom">
                <div class="d-flex align-items-center mb-3">
                    <i class="fa-solid fa-globe mr-2"></i>

                   

                </div>
            </div>
        </div>
    </div>

    <!-- overlay -->
    <div id="mobileOverlay" class="mobile-overlay" tabindex="-1" aria-hidden="true"></div>


</header>