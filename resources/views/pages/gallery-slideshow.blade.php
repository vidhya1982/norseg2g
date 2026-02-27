<div class="premium-hero">

    <div class="premium-track">

       @foreach($images as $image)
    <div class="premium-slide">
        <div class="slide-image">
            <img src="{{ asset('images/gallery/' . $image) }}">
        </div>

        <div class="premium-content">
            <h1>Explore The World</h1>
            <p>Stay connected wherever you travel</p>
        </div>
    </div>
@endforeach

    </div>

    <button class="premium-nav prev">&#10094;</button>
    <button class="premium-nav next">&#10095;</button>

    <div class="premium-progress">
        <div class="premium-bar"></div>
    </div>

    <div class="premium-counter">
        <span id="currentSlide">01</span> /
        <span id="totalSlide"></span>
    </div>

</div>