<footer class=" pt-5 pb-3">
    <div class="container">
        <div class="row footer-info">
            <!-- Logo & Contact -->
            <div class="col-md-4 mb-4">
                <img src="{{ asset('images/logo.png') }}" alt="gsm2go" class="img-fluid mb-3" style="width: 150px;">

                <p class="mb-3">{{ __('footer.text1') }}</p>

                <div class="d-md-flex contact-info-footer">
                    <div class="d-flex align-items-center mb-2">
                        <div class="text-white rounded-circle">
                            <i class="fa-brands fa-whatsapp"></i>
                        </div>
                        <div class="contact-content">
                            <small class="">{{ __('footer.text9') }}</small><br>
                            <a href="https://api.whatsapp.com/send?phone=447624041999">
                                <strong>+447624041999</strong></a>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mb-3">
                        <div class="text-white rounded-circle">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-content">
                            <small class="">{{ __('footer.text11') }}</small><br>
                            <strong>{{ __('footer.text12') }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Useful Links -->
            <div class="col-md-3 mb-4 footer-links">
                <h6 class="fw-bold">{{ __('footer.text2') }}</h6>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-decoration-none ">{{ __('footer.link_text_uk') }}</a></li>
                    <li><a href="#" class="text-decoration-none ">{{ __('footer.link_text_usa') }}</a></li>
                    <li><a href="#" class="text-decoration-none ">{{ __('footer.link_text_uae') }}</a></li>
                    <li><a href="#" class="text-decoration-none ">{{ __('footer.link_text_jp') }}</a></li>
                    <li><a href="#" class="text-decoration-none ">{{ __('footer.link_text_eu') }}</a></li>
                    <li><a href="#" class="text-decoration-none ">{{ __('footer.link_text_world') }}</a></li>
                </ul>
            </div>

            <!-- About ESIM -->
            <div class="col-md-2 mb-4 footer-links">
                <h6 class="fw-bold">{!! __('footer.text8') !!}</h6>
                <ul class="list-unstyled">
                    <li>
                        <a href="{{ route('esim-compatible') }}" class="text-decoration-none">
                            {{ __('footer.text16') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('cellular-optimization') }}" class="text-decoration-none">
                            {{ __('footer.text5') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('terms') }}" class="text-decoration-none">
                            {{ __('footer.text3') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('contact') }}" class="text-decoration-none">
                            {{ __('footer.text6') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('fair-use') }}" class="text-decoration-none">
                            {{ __('footer.fup') }}
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Gallery -->
            <div class="col-md-3 mb-4">
                <h6 class="fw-bold">gsm2go travellers gallery</h6>
                <div class="gallery">
                    @for ($i = 1; $i <= 9; $i++)
                        <div>
                            <img src="{{ asset('images/gallery-img' . $i . '.png') }}" class="img-fluid rounded" alt="">
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        <!-- Bottom -->
        <div class="pt-3 border-top footer-bottom d-flex justify-content-between align-items-center">
            <p class="mb-0 small">{{ __('footer.text13') }}</p>
            <a href="#" class="btn btn-success btn-sm rounded-circle">
                <i class="fas fa-chevron-up"></i>
            </a>
        </div>
    </div>
</footer>