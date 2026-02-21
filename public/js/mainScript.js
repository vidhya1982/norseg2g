// function to toggle navbar

(function () {
    const btn = document.getElementById("mobileMenuBtn");
    const sidebar = document.getElementById("mobileSidebar");
    const overlay = document.getElementById("mobileOverlay");
    const closeBtn = document.getElementById("closeSidebar");

    function openSidebar() {
        sidebar.classList.add("open");
        overlay.classList.add("show");
        sidebar.setAttribute("aria-hidden", "false");
        btn.setAttribute("aria-expanded", "true");
        // prevent body scroll
        document.body.style.overflow = "hidden";
    }

    function closeSidebar() {
        sidebar.classList.remove("open");
        overlay.classList.remove("show");
        sidebar.setAttribute("aria-hidden", "true");
        btn.setAttribute("aria-expanded", "false");
        document.body.style.overflow = "";
    }

    btn.addEventListener("click", function (e) {
        const opened = sidebar.classList.contains("open");
        if (opened) closeSidebar();
        else openSidebar();
    });
    closeBtn.addEventListener("click", closeSidebar);
    overlay.addEventListener("click", closeSidebar);

    // close on ESC
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && sidebar.classList.contains("open"))
            closeSidebar();
    });
})();

// slider for testimonials
document.addEventListener("DOMContentLoaded", () => {

    // ===== Elements =====
    const mainImage = document.getElementById("mainImage");
    const testimonialText = document.getElementById("testimonialText");
    const starsContainer = document.getElementById("stars");
    const userImage = document.getElementById("userImage");
    const userName = document.getElementById("userName");
    const userRole = document.getElementById("userRole");

    const desktopThumbs = document.querySelectorAll("#thumbnails .thumb-img");
    const mobileThumbs = document.querySelectorAll("#mobileThumbnails .thumb-img");

    // ===== Build testimonials (ONLY ONCE) =====
    const testimonials = [];

    desktopThumbs.forEach(img => {
        testimonials.push({
            mainImg: img.dataset.main,
            text: img.dataset.text,
            stars: parseInt(img.dataset.stars),
            userImg: img.dataset.userImg,
            name: img.dataset.name,
            role: img.dataset.role,
        });
    });

    let currentIndex = 0;

    // ===== Stars =====
    function renderStars(count) {
        starsContainer.innerHTML = "";
        for (let i = 0; i < count; i++) {
            starsContainer.innerHTML += `<i class="fas fa-star text-warning me-1"></i>`;
        }
    }

    // ===== Update Testimonial =====
    function updateTestimonial(index) {
        if (!testimonials[index]) return;

        currentIndex = index;
        const t = testimonials[index];

        mainImage.src = t.mainImg;
        testimonialText.textContent = t.text;
        userImage.src = t.userImg;
        userName.textContent = t.name;
        userRole.textContent = t.role;
        renderStars(t.stars);

        // Animation reset
        mainImage.classList.remove("fade-in");
        testimonialText.classList.remove("fade-in");
        void mainImage.offsetWidth;
        void testimonialText.offsetWidth;
        mainImage.classList.add("fade-in");
        testimonialText.classList.add("fade-in");

        // Active state sync
        desktopThumbs.forEach((img, i) =>
            img.classList.toggle("active", i === index)
        );
        mobileThumbs.forEach((img, i) =>
            img.classList.toggle("active", i === index)
        );
    }

    // ===== Click handlers =====
    desktopThumbs.forEach((img, i) => {
        img.addEventListener("click", () => updateTestimonial(i));
    });

    mobileThumbs.forEach((img, i) => {
        img.addEventListener("click", () => updateTestimonial(i));
    });

    // ===== Init =====
    updateTestimonial(0);
});

// guide page

function showGuideTab(tabId, btn) {
    document
        .querySelectorAll(".tab-section")
        .forEach((sec) => sec.classList.add("d-none"));
    document.getElementById(tabId).classList.remove("d-none");

    document
        .querySelectorAll(".tab-btn")
        .forEach((b) => b.classList.remove("active"));
    btn.classList.add("active");
}

// country search autocomplete home page 
$(function () {

    const $input = $('#countrySearch');
    const $list  = $('#suggestions');
    const $clear = $('#clearBtn');

    // Show dropdown on focus
    $input.on('focus', function () {
        $list.show();
    });

    // Filtering
    $input.on('input', function () {

        const val = $(this).val().toLowerCase().trim();
        const $items = $list.find('.suggestion-item');

        if (!val) {
            $items.show();
            $list.hide();
            return;
        }

        let hasMatch = false;

        $items.each(function () {

            const text = $(this)
                .find('.suggestion-left span')
                .text()
                .toLowerCase()
                .trim();

            const match = text.includes(val);

            $(this).toggle(match);

            if (match) hasMatch = true;
        });

        $list.toggle(hasMatch);
    });

    // Click on suggestion
    $list.on('click', '.suggestion-item', function () {

        const text = $(this)
            .find('.suggestion-left span')
            .text()
            .trim();

        $input.val(text);
        $list.hide();
    });

    // ✅ CLEAR BUTTON FIX
    $clear.on('click', function (e) {
        e.stopPropagation();
        $input.val('');
        $list.hide();
    });

    // Click outside hide dropdown
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.search-container').length) {
            $list.hide();
        }
    });

});
  





$(document).on('click', '.suggestion-item', function () {
    const selectedCountryId = $(this).data('country');
    let visibleCount = 0;

    $('[data-countries]').each(function () {
        const countryArray = $(this).data('countries').toString().split(',');

        if (countryArray.includes(selectedCountryId.toString())) {
            $(this).show();
            visibleCount++;
        } else {
            $(this).hide();
        }
    });

    $('#noZonesMessage').toggle(visibleCount === 0);

    // Scroll to topPlans section
    $('#topPlans')[0]?.scrollIntoView({ behavior: 'smooth', block: 'start' });
});

$('#countrySearch').on('input', function () {
    const value = $(this).val().trim();

    if (value === '') {
        $('[data-countries]').show();   //  ALL ZONES
        $('#noZonesMessage').hide();    // hide message
    }
});

// FAQ search functionality
$(document).ready(function () {
    
    $('#faqSearch').on('keyup', function () {
    
        let search = $(this).val().toLowerCase().trim();
        let visibleCount = 0;

        $('.faq-item').each(function () {

            let question = $(this).find('.accordion-button').text().toLowerCase();
            
            if (search !== "" && (question.includes(search))) {
                $(this).css('display', 'block');
                visibleCount++;
            } else if (search === "") {
                $(this).css('display', 'block');
                visibleCount++;
            } else {
                $(this).css('display', 'none');
            }
        });

        // No result message
        if (visibleCount === 0) {
            $('#noResult').css('display', 'block');
        } else {
            $('#noResult').css('display', 'none');
        }
    });

});

