<div class="container py-5">
    <div class="row align-items-center justify-content-center">

        <!-- Desktop Thumbnails -->
        <div class="col-lg-2 d-none d-lg-block text-center">
            <div class="d-flex flex-column align-items-center gap-3" id="thumbnails">

                <img class="thumb-img active"
                     src="{{ asset('images/client1.png') }}"
                     data-main="{{ asset('images/client1.png') }}"
                     data-text="Lorem ipsum dolor sit amet, consectetur adipiscing elit."
                     data-stars="5"
                     data-user-img="{{ asset('images/client1Profile.png') }}"
                     data-name="Jenny Wilson"
                     data-role="Tourist">

                <img class="thumb-img"
                     src="{{ asset('images/client2.png') }}"
                     data-main="{{ asset('images/client2.png') }}"
                     data-text="Mike had a lifetime adventure and highly recommends it!"
                     data-stars="4"
                     data-user-img="{{ asset('images/client2.png') }}"
                     data-name="Mike Tyson"
                     data-role="Explorer">

                <img class="thumb-img"
                     src="{{ asset('images/client3.png') }}"
                     data-main="{{ asset('images/client3.png') }}"
                     data-text="Sophia enjoyed every moment of her mountain trek."
                     data-stars="5"
                     data-user-img="{{ asset('images/client3.png') }}"
                     data-name="Sophia Moore"
                     data-role="Mountaineer">

            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-10">
            <div class="row align-items-center">

                <div class="col-md-5 text-center slider-main-img">
                    <img id="mainImage" class="img-fluid rounded fade-in" alt="">
                </div>

                <div class="col-md-7 mt-4 mt-md-0 text-center text-md-start">
                    <i class="fas fa-quote-left text-primary fs-2 mb-3 d-block"></i>
                    <p class="text-muted mb-4" id="testimonialText"></p>
                    <div class="mb-3" id="stars"></div>

                    <div class="d-flex align-items-center justify-content-center justify-content-md-start">
                        <img id="userImage" class="me-3 profile-img" width="50" height="50">
                        <div>
                            <h6 class="mb-0 fw-bold" id="userName"></h6>
                            <small class="text-primary" id="userRole"></small>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Mobile Thumbnails -->
        <div class="d-block d-lg-none mt-5">
            <div class="d-flex justify-content-center gap-3" id="mobileThumbnails">

                <img class="thumb-img active"
                     src="{{ asset('images/client1.png') }}"
                     data-main="{{ asset('images/client1.png') }}"
                     data-text="Lorem ipsum dolor sit amet, consectetur adipiscing elit."
                     data-stars="5"
                     data-user-img="{{ asset('images/client1Profile.png') }}"
                     data-name="Jenny Wilson"
                     data-role="Tourist">

                <img class="thumb-img"
                     src="{{ asset('images/client2.png') }}"
                     data-main="{{ asset('images/client2.png') }}"
                     data-text="Mike had a lifetime adventure and highly recommends it!"
                     data-stars="4"
                     data-user-img="{{ asset('images/client2.png') }}"
                     data-name="Mike Tyson"
                     data-role="Explorer">

                <img class="thumb-img"
                     src="{{ asset('images/client3.png') }}"
                     data-main="{{ asset('images/client3.png') }}"
                     data-text="Sophia enjoyed every moment of her mountain trek."
                     data-stars="5"
                     data-user-img="{{ asset('images/client3.png') }}"
                     data-name="Sophia Moore"
                     data-role="Mountaineer">

            </div>
        </div>

    </div>
</div>
