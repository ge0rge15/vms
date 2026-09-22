// ==========================================
// PASSWORD SHOW/HIDE TOGGLE
// ==========================================
const eye = document.querySelector(".eye");
const password = document.querySelector("input[type='password']");

if (eye && password) {
    eye.addEventListener("click", () => {
        if (password.type === "password") {
            password.type = "text";
            eye.classList.remove("fa-eye");
            eye.classList.add("fa-eye-slash");
        } else {
            password.type = "password";
            eye.classList.remove("fa-eye-slash");
            eye.classList.add("fa-eye");
        }
    });
}