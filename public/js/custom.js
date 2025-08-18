document.addEventListener("DOMContentLoaded", function () {
    function toggleSubmitButton() {
        const submitBtn = document.querySelector(".submit-button");
        if (submitBtn) {
            submitBtn.style.display = window.location.href.includes(
                "declaration"
            )
                ? "block"
                : "none";
        }
    }

    // Initial check
    toggleSubmitButton();

    // Listen for URL changes (for step navigation)
    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.type === "childList") {
                toggleSubmitButton();
            }
        });
    });

    // Observe the main content area for changes
    const targetNode = document.querySelector("main");
    if (targetNode) {
        observer.observe(targetNode, { childList: true, subtree: true });
    }
});
