// document.addEventListener("DOMContentLoaded", function () {
//     function toggleSubmitButton() {
//         const submitBtn = document.querySelector(".submit-button");
//         if (!submitBtn) return;

//         // Check if we're on the final step
//         const isLastStep = checkIfLastStep();

//         if (isLastStep) {
//             submitBtn.classList.add("btn-submit-visible");
//         } else {
//             submitBtn.classList.remove("btn-submit-visible");
//         }
//     }

//     function checkIfLastStep() {
//         // Look for the specific IDs you provided
//         const element1 = document.getElementById("data.info_is_true");
//         const element2 = document.getElementById("data.applicants_are_jewish");

//         // Check if either element exists and is visible
//         if (element1 && isElementVisible(element1)) {
//             return true;
//         }

//         if (element2 && isElementVisible(element2)) {
//             return true;
//         }

//         return false;
//     }

//     function isElementVisible(element) {
//         if (!element) return false;

//         const style = window.getComputedStyle(element);
//         const rect = element.getBoundingClientRect();

//         return (
//             style.display !== "none" &&
//             style.visibility !== "hidden" &&
//             style.opacity !== "0" &&
//             !element.classList.contains("invisible") &&
//             !element.classList.contains("hidden") &&
//             rect.width > 0 &&
//             rect.height > 0
//         );
//     }

//     // Initial check
//     toggleSubmitButton();

//     // Watch for any changes in the form
//     const observer = new MutationObserver(function (mutations) {
//         // Debounce the check to avoid excessive calls
//         clearTimeout(observer.timeout);
//         observer.timeout = setTimeout(toggleSubmitButton, 100);
//     });

//     // Observe the entire document for changes
//     observer.observe(document.body, {
//         childList: true,
//         subtree: true,
//         attributes: true,
//         attributeFilter: ["class", "style", "data-step"],
//     });
// });
