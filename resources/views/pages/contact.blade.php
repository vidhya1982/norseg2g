<div class="container py-5">

    <div class="contact-hero text-center mb-5">
        <h1 class="fw-bold">Contact <span class="gradient-text">gsm2go</span></h1>
        <p>Have questions about eSIMs, plans or business solutions?</p>
    </div>

    <div class="form-card">
        <form x-data="formHandler()" @submit.prevent="handleSubmit($event)" data-livewire-submit="submit">

            <div class="row g-4">
                <div class="col-md-6">
                    <label>First Name</label>
                    <input wire:model.defer="first_name" class="form-control" data-validate="required|min:2"
                        data-message="First name is required (min 2 chars)">
                </div>

                <div class="col-md-6">
                    <label>Last Name</label>
                    <input wire:model.defer="last_name" class="form-control" data-validate="required|min:2"
                        data-message="Last name is required (min 2 chars)">
                </div>

                <div class="col-md-6">
                    <label>Phone</label>
                    <div class="input-group">
                        <select wire:model="country_code" class="form-select" style="max-width:160px">
                            @foreach ($countries as $c)
                                <option value="+{{ $c->phonecode }}">
                                    {{ $c->country_name }} +{{ $c->phonecode }}
                                </option>
                            @endforeach
                        </select>
                        <input wire:model.defer="phone" class="form-control" data-validate="required|numeric|min:7"
                            data-message="Phone number is invalid">
                    </div>
                </div>

                <div class="col-md-6">
                    <label>Email</label>
                    <input wire:model.defer="email" class="form-control" data-validate="required|email"
                        data-message="Please enter a valid email">
                </div>

                <div class="col-12">
                    <label>Your Message</label>
                    <textarea wire:model.defer="message" class="form-control" data-validate="required|min:10"
                        data-message="Message must be at least 10 characters"></textarea>
                </div>


                <div class="col-md-6 d-flex justify-content-end">
                    <img id="captcha-img" src="{{ captcha_src('default') }}" style="cursor:pointer" class="w-25 h-100">

                    <button type="button" class="btn btn-light"
                        onclick="document.getElementById('captcha-img').src='{{ captcha_src('default') }}&'+Math.random()">
                        <i class="fa fa-rotate-right"></i>
                    </button>
                </div>


                <div class="col-md-4">
                    <input wire:model.defer="captcha" class="form-control" placeholder="Enter Captcha"
                        data-validate="required" data-message="Captcha is required">
                </div>

                <div class="col-12 text-center">

                    <button class="view-button" wire:loading.attr="disabled">
                        <span wire:loading.remove>Send Message</span>
                        <span wire:loading>
                            <span class="spinner-border spinner-border-sm"></span>
                            Sending… please wait
                        </span>
                    </button>

                </div>
            </div>
        </form>
    </div>
</div>
