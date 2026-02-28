document.addEventListener("DOMContentLoaded", function () {

    const lightbox = document.getElementById("galleryLightbox");
    const slides = document.querySelectorAll(".lightbox-slide");
    const openEls = document.querySelectorAll(".open-lightbox");
    const closeBtn = document.querySelector(".close-lightbox");
    const nextBtn = document.querySelector(".lightbox-nav.next");
    const prevBtn = document.querySelector(".lightbox-nav.prev");
    const track = document.querySelector(".lightbox-track");

    let current = 0;

    function updateGallery() {

        track.style.transform = `translateX(-${current * 100}%)`;

        slides.forEach((slide, index) => {
            slide.classList.remove("active");
            if (index === current) {
                slide.classList.add("active");
            }
        });
    }

    openEls.forEach(el => {
        el.addEventListener("click", function () {
            current = parseInt(this.getAttribute("data-index"));
            lightbox.style.display = "flex";
            updateGallery();
        });
    });

    nextBtn.addEventListener("click", () => {
        if (current < slides.length - 1) {
            current++;
            updateGallery();
        }
    });

    prevBtn.addEventListener("click", () => {
        if (current > 0) {
            current--;
            updateGallery();
        }
    });

    closeBtn.addEventListener("click", () => {
        lightbox.style.display = "none";
    });

    document.addEventListener("keydown", (e) => {
        if (e.key === "ArrowRight") nextBtn.click();
        if (e.key === "ArrowLeft") prevBtn.click();
        if (e.key === "Escape") closeBtn.click();
    });

});