

document.addEventListener("spa:page-loaded", async (event) => {
  if (event.detail.page !== "doctors") return;
  const baseUrl = document.body.dataset.baseUrl || "";
  const alertBox = document.getElementById("alert");

  
  const modal = document.getElementById("doctorModal");
  const form = document.getElementById("doctorForm");
  const modalTitle = document.getElementById("doctorModalTitle");
  const submitBtn = document.getElementById("doctorSubmitBtn");
  const submitLbl = document.getElementById("doctorSubmitLabel");
  const openCreate = document.getElementById("openCreateBtn");

  
  const pwModal = document.getElementById("pwModal");
  const pwForm = document.getElementById("pwForm");
  const pwDoctorName = document.getElementById("pwDoctorName");
  const pwSubmitBtn = document.getElementById("pwSubmitBtn");
  const pwSubmitLbl = document.getElementById("pwSubmitLabel");

  
  
  const getSpecCheckboxes = () => document.querySelectorAll(".spec-checkbox");
  const specCount = document.getElementById("specCount");
  const primaryWrapper = document.getElementById("primaryWrapper");
  const primarySelect = document.getElementById("primary_specialization_id");
  const passwordFieldWrap = document.getElementById("passwordFieldWrapper");

  
  const showAlert = (msg, type = "error") => {
    const styles = {
      error: "bg-red-50 border-red-200 text-red-700",
      success: "bg-emerald-50 border-emerald-200 text-emerald-700",
    };
    alertBox.className = `mb-5 rounded-lg px-4 py-3 text-sm border ${styles[type] || styles.error}`;
    alertBox.textContent = msg;
    alertBox.classList.remove("hidden");
    alertBox.scrollIntoView({ behavior: "smooth", block: "nearest" });
  };
  const hideAlert = () => alertBox.classList.add("hidden");

  const clearErrors = (scope) => {
    scope.querySelectorAll("[data-error-for]").forEach((p) => {
      p.textContent = "";
      p.classList.add("hidden");
    });
  };
  const setError = (scope, field, msg) => {
    const p = scope.querySelector(`[data-error-for="${field}"]`);
    if (!p) return;
    p.textContent = msg || "";
    p.classList.toggle("hidden", !msg);
  };

  const openModal = (el) => el.classList.remove("hidden");
  const closeModal = (el) => el.classList.add("hidden");

  
  function refreshSpecializationUI() {
    const specCheckboxes = getSpecCheckboxes();
    const checked = Array.from(specCheckboxes).filter((cb) => cb.checked);

    
    if (specCount) specCount.textContent = `${checked.length} selected`;

    
    specCheckboxes.forEach((cb) => {
      const wrapper = cb.closest(".spec-checkbox-wrapper");
      if (!wrapper) return;
      if (cb.checked) {
        wrapper.classList.add("bg-blue-50", "border-blue-300");
        wrapper.classList.remove("border-slate-200");
      } else {
        wrapper.classList.remove("bg-blue-50", "border-blue-300");
        wrapper.classList.add("border-slate-200");
      }
    });

    
    const previousValue = primarySelect?.value || "";
    if (primarySelect) {
      primarySelect.innerHTML =
        '<option value="">— None marked as primary —</option>';
      checked.forEach((cb) => {
        const label =
          cb.closest("label")?.querySelector("span")?.textContent?.trim() || "";
        const opt = document.createElement("option");
        opt.value = cb.value;
        opt.textContent = label;
        if (cb.value === previousValue) opt.selected = true;
        primarySelect.appendChild(opt);
      });
    }

    
    if (primaryWrapper)
      primaryWrapper.classList.toggle("hidden", checked.length < 2);
  }

  document.getElementById("specializationOptions").addEventListener(
    "change",
    (event) => {
      if (event.target.matches(".spec-checkbox")) refreshSpecializationUI();
    },
    { signal: event.detail.signal },
  );

  
  document.querySelectorAll("[data-close-modal]").forEach((el) => {
    el.addEventListener("click", () => closeModal(modal));
  });
  document.querySelectorAll("[data-close-pw]").forEach((el) => {
    el.addEventListener("click", () => closeModal(pwModal));
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeModal(modal);
      closeModal(pwModal);
    }
  }, { signal: event.detail.signal });

  
  
  
  if (openCreate) {
    openCreate.addEventListener("click", () => {
      form.reset();
      clearErrors(form);
      document.getElementById("doctor_id").value = "";
      getSpecCheckboxes().forEach((cb) => (cb.checked = false));
      modalTitle.textContent = "New Doctor";
      submitLbl.textContent = "Create Doctor";
      passwordFieldWrap.classList.remove("hidden");
      document.getElementById("password").required = true;
      refreshSpecializationUI();
      openModal(modal);
    });
  }

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    hideAlert();
    clearErrors(form);

    const id = document.getElementById("doctor_id").value;
    const isEdit = id !== "";

    const specializations = Array.from(getSpecCheckboxes())
      .filter((cb) => cb.checked)
      .map((cb) => parseInt(cb.value, 10));

    const payload = {
      first_name: document.getElementById("first_name").value.trim(),
      last_name: document.getElementById("last_name").value.trim(),
      username: document.getElementById("username").value.trim(),
      email: document.getElementById("email").value.trim(),
      contact_number: document.getElementById("contact_number").value.trim(),
      license_number: document.getElementById("license_number").value.trim(),
      consultation_fee: document.getElementById("consultation_fee").value,
      specializations: specializations,
      primary_specialization_id: primarySelect?.value
        ? parseInt(primarySelect.value, 10)
        : 0,
    };
    if (!isEdit) payload.password = document.getElementById("password").value;

    const url = isEdit
      ? `${baseUrl}/api/admin.php?resource=doctors&operation=update&id=${id}`
      : `${baseUrl}/api/admin.php?resource=doctors&operation=create`;

    submitBtn.disabled = true;
    submitLbl.textContent = isEdit ? "Saving…" : "Creating…";

    try {
      const { data } = await axios.post(url, payload, {
        headers: { "Content-Type": "application/json" },
        withCredentials: true,
      });

      if (data.success) {
        closeModal(modal);
        sessionStorage.setItem("doctor_flash", data.message || "Saved.");
        await window.refreshAdminList();
      } else {
        if (data.errors) {
          Object.entries(data.errors).forEach(([f, m]) => setError(form, f, m));
        }
        showAlert(data.message || "Save failed.", "error");
      }
    } catch (err) {
      const res = err.response?.data;
      if (res?.errors) {
        Object.entries(res.errors).forEach(([f, m]) => setError(form, f, m));
      }
      showAlert(res?.message || "Save failed.", "error");
    } finally {
      submitBtn.disabled = false;
      submitLbl.textContent = isEdit ? "Save Changes" : "Create Doctor";
    }
  });

  pwForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    hideAlert();
    clearErrors(pwForm);

    const doctorId = pwForm.dataset.doctorId;
    const password = document.getElementById("pw_password").value;
    const confirm = document.getElementById("pw_password_confirm").value;

    pwSubmitBtn.disabled = true;
    pwSubmitLbl.textContent = "Updating…";

    try {
      const { data } = await axios.post(
        `${baseUrl}/api/admin.php?resource=doctors&operation=change-password&id=${doctorId}`,
        { password, password_confirm: confirm },
        {
          headers: { "Content-Type": "application/json" },
          withCredentials: true,
        },
      );

      if (data.success) {
        closeModal(pwModal);
        sessionStorage.setItem(
          "doctor_flash",
          data.message || "Password updated.",
        );
        await window.refreshAdminList();
      } else {
        if (data.errors) {
          Object.entries(data.errors).forEach(([f, m]) =>
            setError(pwForm, f, m),
          );
        }
        showAlert(data.message || "Update failed.", "error");
      }
    } catch (err) {
      const res = err.response?.data;
      if (res?.errors) {
        Object.entries(res.errors).forEach(([f, m]) => setError(pwForm, f, m));
      }
      showAlert(res?.message || "Update failed.", "error");
    } finally {
      pwSubmitBtn.disabled = false;
      pwSubmitLbl.textContent = "Update Password";
    }
  });

  refreshSpecializationUI();

  
  
  
  const flash = sessionStorage.getItem("doctor_flash");
  if (flash) {
    sessionStorage.removeItem("doctor_flash");
    showAlert(flash, "success");
  }
});
