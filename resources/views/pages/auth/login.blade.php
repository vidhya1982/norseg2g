<div class="auth-page">
  <div class="container py-5 row justify-content-center m-auto">
    <div class="col-4 login-hero d-lg-block d-none"></div>

    <div class="col-lg-8 col-12 row justify-content-center">
      <div>
        <div class="auth-card p-5">
          <div class="auth-hero">
            <h1>
              Login to <span class="gradient-text">gsm2go</span>
            </h1>
            <p>Start roaming smarter with global eSIM access</p>
          </div>

          <!-- EMAIL LOGIN -->
          <form x-data="formHandler()" @submit.prevent="handleSubmit($event)" data-livewire-submit="login" novalidate>
            {{-- EMAIL --}}
            <div class="mb-3 floating-input">


              <input type="email" wire:model.defer="email" class="form-control @error('email') is-invalid @enderror"
                data-validate="required|email" data-message-required="Email is required"
                data-message-email="Enter a valid email address">
              <label class="form-label">Email</label>
              @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- PASSWORD (only when email verified) --}}
            <div class="mb-3 floating-input" x-show="@entangle('emailVerified')" x-transition>


              <input type="password" wire:model.defer="password"
                class="form-control @error('password') is-invalid @enderror">
              <label class="form-label">Password</label>
              @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="text-end mt-2">
              <a href="{{ route('forgot-password') }}" class="text-sm">
                Forgot Password?
              </a>
            </div>

            {{-- SUBMIT --}}
            <button class="btn login-btn w-100" wire:loading.attr="disabled">
              <span wire:loading.remove>
                <span x-show="!@entangle('emailVerified')">Continue</span>
                <span x-show="@entangle('emailVerified')">Login</span>
              </span>

              <span wire:loading>
                <span class="spinner-border spinner-border-sm"></span>
                Please wait…
              </span>
            </button>
          </form>


          <div class="auth-divider">
            <span>or</span>
          </div>

          <!-- APPLE LOGIN -->
          <button type="button" wire:click="apple" class="btn auth-btn-outline w-100 mb-2">
            <i class="fab fa-apple me-2"></i> Continue with Apple
          </button>
          <!-- Google LOGIN -->
          <button type="button" wire:click="google" class="btn auth-btn-outline w-100">
            <i class="fab fa-google me-2"></i> Continue with Google
          </button>


          <!-- SIGNUP LINK -->
          <div class="auth-footer mt-4 text-center">
            <p class="mb-2">Don’t have an account?</p>
            <a href="{{ route('sign-up') }}" class="btn login-btn w-50">
              Create an Account
            </a>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>