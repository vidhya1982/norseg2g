
document.addEventListener("DOMContentLoaded", function () {

    const track = document.querySelector(".premium-track");
    const slides = document.querySelectorAll(".premium-slide");
    const nextBtn = document.querySelector(".next");
    const prevBtn = document.querySelector(".prev");
    const currentText = document.getElementById("currentSlide");
    const totalText = document.getElementById("totalSlide");
    const progressBar = document.querySelector(".premium-bar");

    let current = 0;
    const total = slides.length;

    totalText.textContent = total < 10 ? "0" + total : total;

    function updateSlider() {

    track.style.transform = `translateX(-${current * 100}%)`;

    slides.forEach((slide, index) => {
        slide.classList.remove("active");

        if (index === current) {
            slide.classList.add("active");
        }
    });

    currentText.textContent =
        current + 1 < 10 ? "0" + (current + 1) : (current + 1);

    progressBar.style.width = ((current + 1) / total) * 100 + "%";
}

    nextBtn.addEventListener("click", function () {
        if (current < total - 1) {
            current++;
            updateSlider();
        }
    });

    prevBtn.addEventListener("click", function () {
        if (current > 0) {
            current--;
            updateSlider();
        }
    });

    updateSlider();
});