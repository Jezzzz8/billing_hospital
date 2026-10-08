

document.addEventListener("DOMContentLoaded", () => {
  console.log("🔵 login.js loaded");

  const form = document.getElementById("loginForm");
  const emailEl = document.getElementById("email");
  const passwordEl = document.getElementById("password");
  const submitBtn = document.getElementById("submitBtn");
  const submitLabel = document.getElementById("submitLabel");
  const spinner = document.getElementById("spinner");
  const alertBox = document.getElementById("alert");
  const togglePass = document.getElementById("togglePassword");

  const baseUrl = document.body.dataset.baseUrl || "";
  const API_URL = baseUrl + "/api/login.php";

  console.log("🔵 baseUrl:", JSON.stringify(baseUrl));
  console.log("🔵 API_URL:", API_URL);

  function showAlert(message, type = "error") {
    const styles = {
      error: "bg-red-50 border-red-200 text-red-700",
      success: "bg-emerald-50 border-emerald-200 text-emerald-700",
    };
    alertBox.className = `mb-5 rounded-lg px-4 py-3 text-sm border ${styles[type] || styles.error}`;
    alertBox.textContent = message;
    alertBox.classList.remove("hidden");
  }

  function hideAlert() {
    alertBox.classList.add("hidden");
    alertBox.textContent = "";
  }

  function setFieldError(field, message) {
    const p = document.querySelector(`[data-error-for="${field}"]`);
    if (!p) return;
    p.textContent = message || "";
    p.classList.toggle("hidden", !message);
  }

  function setLoading(loading) {
    submitBtn.disabled = loading;
    spinner.classList.toggle("hidden", !loading);
    submitLabel.textContent = loading ? "Signing in…" : "Sign in";
  }

  togglePass.addEventListener("click", () => {
    passwordEl.type = passwordEl.type === "password" ? "text" : "password";
  });

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    hideAlert();
    setFieldError("email", "");
    setFieldError("password", "");

    let ok = true;
    if (emailEl.value.trim() === "") {
      setFieldError("email", "Email is required.");
      ok = false;
    }
    if (passwordEl.value === "") {
      setFieldError("password", "Password is required.");
      ok = false;
    }
    if (!ok) return;

    setLoading(true);

    const payload = {
      email: emailEl.value.trim(),
      password: passwordEl.value,
    };

    console.log("🟢 Payload:", payload);

    try {
      const res = await axios.post(API_URL, payload, {
        headers: { "Content-Type": "application/json" },
        withCredentials: true,
      });

      console.log("🟢 Response:", res.status, res.data);

      if (res.data.success) {
        showAlert(
          `Welcome back, ${res.data.user.first_name}! Redirecting…`,
          "success",
        );
        setTimeout(() => {
          window.location.href = res.data.redirect;
        }, 700);
      } else {
        showAlert(res.data.message || "Login failed.", "error");
        setLoading(false);
      }
    } catch (err) {
      const msg =
        err.response?.data?.message ||
        (err.code === "ERR_NETWORK"
          ? "Network error. Is the server running?"
          : "Login failed. Please try again.");
      console.error(
        "❌",
        err.response?.status,
        err.response?.data || err.message,
      );
      showAlert(msg, "error");
      setLoading(false);
    }
  });

  emailEl.addEventListener("input", () => setFieldError("email", ""));
  passwordEl.addEventListener("input", () => setFieldError("password", ""));
});
