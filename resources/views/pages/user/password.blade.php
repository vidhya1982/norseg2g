<div class="dashboard-page page-background">
    <div class="row container py-4">

        {{-- Sidebar --}}
        <div class="col-lg-3 d-none d-lg-block user-panel-sidebar">
              <livewire:user.sidebar />
        </div>

        <div class="password-card col-lg-9 col-12">
            <div class="user-box mb-4">
                <h3>Change Password</h3>
                <p>Keep your account secure by updating your password</p>
            </div>

            <div class="">
                <form wire:submit.prevent="updatePassword">
                    {{-- Current Password --}}
                    <div class="floating-input password-field
                        @error('current_password') input-error shake @enderror">

                        <input type="password"
                               class="password-input"
                               placeholder=" "
                               wire:model.defer="current_password"
                               required>

                        <label>Current Password</label>

                        <span class="toggle-password">
                            <i class="fa-regular fa-eye"></i>
                        </span>

                        @error('current_password')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- New Password --}}
                    <div class="floating-input password-field
                        @error('password') input-error shake @enderror">

                        <input type="password"
                               id="new_password"
                               class="password-input"
                               placeholder=" "
                               wire:model.defer="password"
                               required>

                        <label>New Password</label>

                        <span class="toggle-password">
                            <i class="fa-regular fa-eye"></i>
                        </span>

                        @error('password')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Confirm Password --}}
                    <div class="floating-input password-field">

                        <input type="password"
                               id="confirm_password"
                               class="password-input"
                               placeholder=" "
                               wire:model.defer="password_confirmation"
                               required>

                        <label>Confirm New Password</label>

                        <span class="toggle-password">
                            <i class="fa-regular fa-eye"></i>
                        </span>
                    </div>

                    {{-- Submit --}}
                    <div class="form-actions mt-4">
                        <button type="submit" class="btn-save">
                            Update Password
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

