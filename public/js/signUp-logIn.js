// Elements
const togglePasswordShowList = document.querySelectorAll(
    ".toggle-password-show"
);
const togglePasswordHideList = document.querySelectorAll(
    ".toggle-password-hide"
);

// Event listeners

togglePasswordShowList.forEach(function (toggle) {
    toggle.addEventListener("click", function () {
        togglePasswordVisibility(this, "text");
    });
});

togglePasswordHideList.forEach(function (toggle) {
    toggle.addEventListener("click", function () {
        togglePasswordVisibility(this, "password");
    });
});

function togglePasswordVisibility(toggle, type) {
    const passwordInput = toggle.parentNode.querySelector("input");
    passwordInput.setAttribute("type", type);

    if (type === "text") {
        toggle.classList.toggle("hidden");
        toggle.nextElementSibling.classList.toggle("hidden");
    } else {
        toggle.classList.toggle("hidden");
        toggle.previousElementSibling.classList.toggle("hidden");
    }
}
