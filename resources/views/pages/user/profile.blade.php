<div class="dashboard-page page-background">
    <div class="container row py-4">

        <div class="col-lg-3 d-none d-lg-block user-panel-sidebar">
            <livewire:user.sidebar />
        </div>

        <div class="col-12 d-lg-none mb-3">
            @include('pages.user.common.mobile-tabs')
        </div>

        <div class="col-lg-9 col-12 profile-card animate-fade">

            <h2 class=" user-box">Personal Information</h2>

            <form wire:submit.prevent="save" class="profile-form">

                <div class="form-grid">

                    <!-- First Name -->
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" wire:model.defer="first_name" placeholder="Enter first name" value="{{ auth()->user()->fname }}">
                        @error('first_name') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Last Name -->
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" wire:model.defer="last_name" placeholder="Enter last name" value="{{ auth()->user()->lname }}">
                        @error('last_name') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Email -->
                    <div class="form-group full">
                        <label>Email</label>
                        <input type="email" value="{{ auth()->user()->email }}" readonly class="readonly">
                    </div>

                    <!-- Phone -->
                    <div class="form-group full">
                        <label>Phone Number *</label>

                        <div class="phone-wrapper">
                            <p class="country-code mb-0">🇮🇳 +91</p>
                            <input type="text" wire:model.defer="phone" placeholder="Enter phone number" value="{{ auth()->user()->mobile }}">
                        </div>

                        @error('phone') <span class="error">{{ $message }}</span> @enderror
                    </div>

                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        💾 Save Changes
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>