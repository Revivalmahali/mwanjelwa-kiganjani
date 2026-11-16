(() => {
    "use strict";

    const passwordFields = document.querySelectorAll('input[type="password"]');

    passwordFields.forEach((input) => {
        const wrapper = document.createElement("div");
        wrapper.className = "password-input-wrap";
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        const toggle = document.createElement("button");
        toggle.type = "button";
        toggle.className = "password-toggle";
        toggle.textContent = "Show";
        toggle.setAttribute("aria-label", "Show password");
        toggle.setAttribute("aria-pressed", "false");
        wrapper.appendChild(toggle);

        toggle.addEventListener("click", () => {
            const showing = input.type === "password";
            input.type = showing ? "text" : "password";
            toggle.textContent = showing ? "Hide" : "Show";
            toggle.setAttribute("aria-label", showing ? "Hide password" : "Show password");
            toggle.setAttribute("aria-pressed", showing ? "true" : "false");
            input.focus();
        });
    });
})();
