<div>
  <div class="password-card">
  <form wire:submit.prevent="resetPassword">

  <input type="hidden" wire:model="email">
  <input type="hidden" wire:model="token">

  <div class="mb-3 floating-input password-field">
    <label>New Password</label>
    <input type="password" wire:model.defer="password" class="form-control">
    @error('password') <small class="text-danger">{{ $message }}</small> @enderror
  </div>

  <div class="mb-3 floating-input password-field">
    <label>Confirm Password</label>
    <input type="password" wire:model.defer="password_confirmation" class="form-control">
  </div>

  <button class="btn btn-primary w-50">
    Reset Password
  </button>
</form>
</div>
</div>
