


document.addEventListener("spa:page-loaded", async (event) => {
  if (event.detail.page !== "diagnosis") return;
  const baseUrl = document.body.dataset.baseUrl || "";
  const alertBox = document.getElementById("alert");

  
  const modal = document.getElementById("diagnosisModal");
  const form = document.getElementById("diagnosisForm");
  const modalTitle = document.getElementById("diagnosisModalTitle");
  const submitBtn = document.getElementById("diagnosisSubmitBtn");
  const submitLbl = document.getElementById("diagnosisSubmitLabel");
  const openCreate = document.getElementById("openCreateBtn");

  
  const confirmModal = document.getElementById("confirmModal");
  const confirmName = document.getElementById("confirmDiagnosisName");
  const confirmDeactivateBtn = document.getElementById("confirmDeactivateBtn");
  const confirmDeactivateLbl = document.getElementById(
    "confirmDeactivateLabel",
  );

  
  const deleteModal = document.getElementById("deleteModal");
  const deleteDiagnosisName = document.getElementById("deleteDiagnosisName");
  const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
  const confirmDeleteLbl = document.getElementById("confirmDeleteLabel");

  
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

  
  document.querySelectorAll("[data-close-modal]").forEach((el) => {
    el.addEventListener("click", () => closeModal(modal));
  });
  document.querySelectorAll("[data-close-confirm]").forEach((el) => {
    el.addEventListener("click", () => closeModal(confirmModal));
  });
  document.querySelectorAll("[data-close-delete]").forEach((el) => {
    el.addEventListener("click", () => closeModal(deleteModal));
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeModal(modal);
      closeModal(confirmModal);
      closeModal(deleteModal);
    }
  }, { signal: event.detail.signal });

  
  
  
  if (openCreate) {
    openCreate.addEventListener("click", () => {
      form.reset();
      clearErrors(form);
      document.getElementById("diagnosis_id").value = "";
      modalTitle.textContent = "New Diagnosis";
      submitLbl.textContent = "Create Diagnosis";
      openModal(modal);
    });
  }

  
  
  
  document.querySelectorAll(".edit-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const d = JSON.parse(btn.dataset.diagnosis);

      form.reset();
      clearErrors(form);

      modalTitle.textContent = "Edit Diagnosis";
      submitLbl.textContent = "Save Changes";

      document.getElementById("diagnosis_id").value = d.diagnosis_id;
      document.getElementById("icd_code").value = d.icd_code || "";
      document.getElementById("diagnosis_name").value = d.diagnosis_name;
      document.getElementById("description").value = d.description || "";

      openModal(modal);
    });
  });

  
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    hideAlert();
    clearErrors(form);

    const id = document.getElementById("diagnosis_id").value;
    const isEdit = id !== "";

    const payload = {
      icd_code: document.getElementById("icd_code").value.trim(),
      diagnosis_name: document.getElementById("diagnosis_name").value.trim(),
      description: document.getElementById("description").value.trim(),
    };

    const url = isEdit
      ? `${baseUrl}/api/admin.php?resource=diagnoses&operation=update&id=${id}`
      : `${baseUrl}/api/admin.php?resource=diagnoses&operation=create`;

    submitBtn.disabled = true;
    submitLbl.textContent = isEdit ? "Saving…" : "Creating…";

    try {
      const { data } = await axios.post(url, payload, {
        headers: { "Content-Type": "application/json" },
        withCredentials: true,
      });

      if (data.success) {
        closeModal(modal);
        sessionStorage.setItem("diagnosis_flash", data.message || "Saved.");
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
      submitLbl.textContent = isEdit ? "Save Changes" : "Create Diagnosis";
    }
  });

  
  
  
  document.querySelectorAll(".deactivate-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      confirmName.textContent = btn.getAttribute("data-diagnosis-name");
      confirmDeactivateBtn.dataset.diagnosisId =
        btn.getAttribute("data-diagnosis-id");
      openModal(confirmModal);
    });
  });

  if (confirmDeactivateBtn) {
    confirmDeactivateBtn.addEventListener("click", async () => {
      const id = confirmDeactivateBtn.dataset.diagnosisId;
      if (!id) return;

      confirmDeactivateBtn.disabled = true;
      confirmDeactivateLbl.textContent = "Archiving…";

      try {
        const { data } = await axios.post(
          `${baseUrl}/api/admin.php?resource=diagnoses&operation=toggle-active&id=${id}`,
          {},
          {
            headers: { "Content-Type": "application/json" },
            withCredentials: true,
          },
        );

        if (data.success) {
          closeModal(confirmModal);
          sessionStorage.setItem(
            "diagnosis_flash",
            data.message || "Diagnosis archived.",
          );
          await window.refreshAdminList();
        } else {
          closeModal(confirmModal);
          showAlert(data.message || "Action failed.", "error");
        }
      } catch (err) {
        closeModal(confirmModal);
        showAlert(err.response?.data?.message || "Action failed.", "error");
      } finally {
        confirmDeactivateBtn.disabled = false;
        confirmDeactivateLbl.textContent = "Archive Diagnosis";
      }
    });
  }

  
  
  
  document.querySelectorAll(".reactivate-btn").forEach((btn) => {
    btn.addEventListener("click", async () => {
      const id = btn.getAttribute("data-diagnosis-id");
      if (!id) return;

      btn.disabled = true;

      try {
        const { data } = await axios.post(
          `${baseUrl}/api/admin.php?resource=diagnoses&operation=toggle-active&id=${id}`,
          {},
          {
            headers: { "Content-Type": "application/json" },
            withCredentials: true,
          },
        );

        if (data.success) {
          sessionStorage.setItem(
            "diagnosis_flash",
            data.message || "Diagnosis reactivated.",
          );
          await window.refreshAdminList();
        } else {
          showAlert(data.message || "Action failed.", "error");
        }
      } catch (err) {
        showAlert(err.response?.data?.message || "Action failed.", "error");
      } finally {
        btn.disabled = false;
      }
    });
  });

  
  
  
  let pendingDeleteId = null;

  document.querySelectorAll(".delete-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      pendingDeleteId = btn.getAttribute("data-diagnosis-id");
      deleteDiagnosisName.textContent =
        btn.getAttribute("data-diagnosis-name") || "this diagnosis";
      openModal(deleteModal);
    });
  });

  if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener("click", async () => {
      if (!pendingDeleteId) return;

      confirmDeleteBtn.disabled = true;
      confirmDeleteLbl.textContent = "Deleting…";

      try {
        const { data } = await axios.post(
          `${baseUrl}/api/admin.php?resource=diagnoses&operation=delete&id=${pendingDeleteId}`,
          {},
          {
            headers: { "Content-Type": "application/json" },
            withCredentials: true,
          },
        );

        if (data.success) {
          closeModal(deleteModal);
          sessionStorage.setItem(
            "diagnosis_flash",
            data.message || "Diagnosis deleted.",
          );
          await window.refreshAdminList();
        } else {
          closeModal(deleteModal);
          showAlert(data.message || "Delete failed.", "error");
        }
      } catch (err) {
        closeModal(deleteModal);
        showAlert(err.response?.data?.message || "Delete failed.", "error");
      } finally {
        confirmDeleteBtn.disabled = false;
        confirmDeleteLbl.textContent = "Yes, delete permanently";
        pendingDeleteId = null;
      }
    });
  }

  
  
  
  
  
  
  const flash = sessionStorage.getItem("diagnosis_flash");
  if (flash) {
    sessionStorage.removeItem("diagnosis_flash");
    showAlert(flash, "success");
  }
});
