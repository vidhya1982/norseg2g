<div class="container forget-password ">
  <div class=" password-card">
    <form wire:submit.prevent="sendResetLink">
      <div class="mb-3 floating-input password-field">

        <input type="email" wire:model.defer="email" class="form-control">
        <label>Email</label>
        @error('email') <small class="text-danger">{{ $message }}</small> @enderror
      </div>

      <button class="btn btn-primary w-100">
        Send Reset Link
      </button>

      @if (session()->has('success'))
        <div class="alert alert-success mt-3">
          {{ session('success') }}
        </div>
      @endif
    </form>
  </div>
</div>