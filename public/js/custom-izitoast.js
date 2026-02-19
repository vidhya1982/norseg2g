document.addEventListener("livewire:init", () => {
    Livewire.on("toast", (data) => {
        iziToast[data.type]({
            title: data.title ?? data.type,
            message: data.message,
            position: "topRight",
            timeout: 3000,
        });
    });
});
