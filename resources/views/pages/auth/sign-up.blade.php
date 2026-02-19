<div class="auth-page page-background">
  <div class="container py-5 row justify-content-center m-auto">

    <!-- HERO -->
    <div class="col-4 sign-hero d-lg-block d-none"></div>

    <div class="col-lg-8 col-12 row justify-content-end">
      <div class="">

        <div class="auth-card ">
          <div class=" auth-hero">
            <h1>
              Create your <span class="gradient-text">gsm2go</span> Account
            </h1>
            <p>Start roaming smarter with global eSIM access</p>
          </div>
          <form method="POST" action="#">


            <!-- PERSONAL INFO -->
            <div class="form-section">
              <h6 class="section-title">Personal Information</h6>

              <div class="row g-4">
                <div class="col-md-6">
                  <div class="floating-group">
                    <input type="text" class="form-control signup-input" required>
                    <label>First Name *</label>
                    <small>As in Passport / Driver License</small>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="floating-group">
                    <input type="text" class="form-control signup-input" required>
                    <label>Last Name *</label>
                    <small>As in Passport / Driver License</small>
                  </div>
                </div>
              </div>
            </div>

            <!-- CONTACT INFO -->
            <div class="form-section">
              <h6 class="section-title">Contact Details</h6>

              <div class="row g-4">
                <div class="col-md-6">
                  <div class="floating-group">
                    <input type="email" class="form-control signup-input" required>
                    <label>Email *</label>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="floating-group">
                   
                    <div class="input-group">
                      <select wire:model="country_code" class="form-select" style="max-width:150px">
                        @foreach ($countries as $c)
                          <option value="+{{ $c->phonecode }}">
                            {{ $c->country_name }} +{{ $c->phonecode }}
                          </option>
                        @endforeach
                      </select>
                      <input wire:model.defer="phone" class="form-control signup-input"
                        data-validate="required|numeric|min:7" data-message="Phone number is invalid" 
                        placeholder="Phone Number">
                        
                    </div>
                    
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="floating-group">
                    <input type="text" class="form-control signup-input">
                    <label>Company</label>
                  </div>
                </div>



                <div class="col-md-6 position-relative country-wrapper">
                  <div class="floating-group">
                  

                    <input type="text" class="form-control signup-input country-input" readonly value="{{ $country }}">
  <label >Country</label>
                    <ul class="country-dropdown d-none">
                      @foreach($countries as $item)
                        <li data-name="{{ $item->country_name }}" data-code="{{ $item->dial_code }}">
                          {{ $item->country_name }}
                          <span>{{ $item->dial_code }}</span>
                        </li>
                      @endforeach
                    </ul>

                  </div>
                </div>

              </div>
            </div>

            <!-- CAPTCHA + SUBMIT -->
            <div class="row g-4 align-items-end mt-2">

              <div class="col-md-6">
                <label class="static-label">Security Check</label>
                <div class="captcha-signup">
                  <input type="text" class="form-control signup-input" placeholder="Enter Captcha">
                  <img id="captcha-img" src="{{ captcha_src('default') }}">
                  <button type="button"
                    onclick="document.getElementById('captcha-img').src='{{ captcha_src('default') }}&'+Math.random()">
                    <i class="fa fa-rotate-right"></i>
                  </button>
                </div>
              </div>

              <div class="col-md-6">
                <button class="btn login-btn w-100">
                  Create Account
                </button>
              </div>

            </div>
          </form>


          <!-- DIVIDER -->
          <div class="auth-divider">
            <span>or continue with</span>
          </div>

          <!-- SOCIAL -->
          <div class="social-auth">
            <button class="btn social-btn apple auth-btn-outline">
              <i class="fab fa-apple"></i> Sign up with Apple
            </button>

            <button class="btn social-btn google auth-btn-outline">
              <i class="fab fa-google"></i> Sign up with Google
            </button>
          </div>

          <!-- FOOTER -->
          <div class="auth-footer">
            <p>Already have an account?</p>
            <a href="{{ route('login') }}" class="btn login-btn w-50">
              Login
            </a>
          </div>

        </div>

      </div>
    </div>
  </div>
</div>