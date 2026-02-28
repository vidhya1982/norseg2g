<div class="mmt-gallery">

    <section class="gallery-hero">
        <div class="gallery-header">
            <h1>Travel Moments Gallery</h1>
            <p class="gallery-subtitle">
                Discover the beauty of destinations experienced by GSM2GO travellers
            </p>
        </div>
    </section>

    <section class="gallery-wrapper">
        <div class="gallery-container">

            <!-- MAIN GRID -->
            <div class="mmt-grid">

                <div class="mmt-big">
                    <img src="{{ asset('images/gallery/' . $images[0]) }}" data-index="0" class="open-lightbox">
                </div>

                <div class="mmt-small-grid">
                    @foreach(array_slice($images, 1, 4) as $index => $img)
                        <div class="mmt-small">
                            <img src="{{ asset('images/gallery/' . $img) }}" data-index="{{ $index + 1 }}"
                                class="open-lightbox">
                        </div>
                    @endforeach

                    @if(count($images) > 5)
                        <div class="mmt-more" data-index="5">
                            +{{ count($images) - 5 }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- NEW 5 IMAGE ROW -->
            <div class="mmt-row-4">
                @foreach(array_slice($images, 4, 4) as $index => $img)
                    <div class="mmt-row-item">
                        <img src="{{ asset('images/gallery/' . $img) }}" data-index="{{ $index + 5 }}"
                            class="open-lightbox">
                    </div>
                @endforeach
            </div>

        </div>
    </section>
    <section class="gallery-description pb-0 pt-5">
        <div class="desc-container p-5">
            <h3>Explore. Connect. Experience.</h3>
            <p> From the charming streets of Europe to vibrant city skylines and serene lakes, our travellers stay
                connected across the globe with GSM2GO eSIM solutions. Every journey becomes smoother when connectivity
                is seamless. </p>
            <p> Whether you're traveling for business or leisure, GSM2GO ensures reliable data and voice coverage in
                multiple countries worldwide. </p>
        </div>
    </section>

</div>

<!-- Lightbox Modal -->
<div id="galleryLightbox" class="lightbox">

    <span class="close-lightbox">&times;</span>

    <div class="lightbox-slider">

        <button class="lightbox-nav prev">&#10094;</button>

        <div class="lightbox-track">
            @foreach($images as $img)
                <div class="lightbox-slide">
                    <img src="{{ asset('images/gallery/' . $img) }}">
                </div>
            @endforeach
        </div>

        <button class="lightbox-nav next">&#10095;</button>

    </div>

</div>