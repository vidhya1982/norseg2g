document.addEventListener("DOMContentLoaded", () => {

  document.querySelectorAll(".country-wrapper").forEach(wrapper => {

    const input = wrapper.querySelector(".country-input");
    const dropdown = wrapper.querySelector(".country-dropdown");

    // OPEN / CLOSE
    input.addEventListener("click", () => {
      dropdown.classList.toggle("d-none");
    });

    // SELECT COUNTRY
    dropdown.querySelectorAll("li").forEach(item => {
      item.addEventListener("click", () => {
        input.value = item.dataset.name;
        dropdown.classList.add("d-none");

        // Livewire update
        Livewire.dispatch("set-country", {
          name: item.dataset.name,
          code: item.dataset.code
        });
      });
    });

    // CLICK OUTSIDE
    document.addEventListener("click", e => {
      if (!wrapper.contains(e.target)) {
        dropdown.classList.add("d-none");
      }
    });

  });

});
