


document.addEventListener("spa:page-loaded", async (event) => {
  if (event.detail.page !== "admission-status") return;
  const baseUrl = document.body.dataset.baseUrl || "";
  const alertBox = document.getElementById("alert");

  
  const modal = document.getElementById("statusModal");
  const form = document.getElementById("statusForm");
  const modalTitle = document.getElementById("statusModalTitle");
  const submitBtn = document.getElementById("statusSubmitBtn");
  const submitLbl = document.getElementById("statusSubmitLabel");
  const openCreate = document.getElementById("openCreateBtn");

  
  const colorPicker = document.getElementById("color_picker");
  const colorCode = document.getElementById("color_code");

  
  const deleteModal = document.getElementById("deleteModal");
  const deleteStatusName = document.getElementById("deleteStatusName");
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

  
  if (colorPicker && colorCode) {
    colorPicker.addEventListener("input", () => {
      colorCode.value = colorPicker.value;
    });
    colorCode.addEventListener("input", () => {
      if (/^#[0-9A-Fa-f]{6}$/.test(colorCode.value)) {
        colorPicker.value = colorCode.value;
      }
    });
  }

  
  document.querySelectorAll("[data-close-modal]").forEach((el) => {
    el.addEventListener("click", () => closeModal(modal));
  });
  document.querySelectorAll("[data-close-delete]").forEach((el) => {
    el.addEventListener("click", () => closeModal(deleteModal));
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeModal(modal);
      closeModal(deleteModal);
    }
  }, { signal: event.detail.signal });

  
  
  
  if (openCreate) {
    openCreate.addEventListener("click", () => {
      form.reset();
      clearErrors(form);
      document.getElementById("status_id").value = "";
      document.getElementById("color_picker").value = "#3b82f6";
      document.getElementById("color_code").value = "#3b82f6";
      modalTitle.textContent = "New Admission Status";
      submitLbl.textContent = "Create Status";
      openModal(modal);
    });
  }

  
  
  
  document.querySelectorAll(".edit-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const s = JSON.parse(btn.dataset.status);

      form.reset();
      clearErrors(form);

      modalTitle.textContent = "Edit Admission Status";
      submitLbl.textContent = "Save Changes";

      document.getElementById("status_id").value = s.status_id;
      document.getElementById("status_name").value = s.status_name;

      const color = s.color_code || "#3b82f6";
      document.getElementById("color_picker").value = color;
      document.getElementById("color_code").value = color;

      openModal(modal);
    });
  });

  
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    hideAlert();
    clearErrors(form);

    const id = document.getElementById("status_id").value;
    const isEdit = id !== "";

    const payload = {
      status_name: document.getElementById("status_name").value.trim(),
      color_code: document.getElementById("color_code").value.trim(),
    };

    const url = isEdit
      ? `${baseUrl}/api/admin.php?resource=admission-statuses&operation=update&id=${id}`
      : `${baseUrl}/api/admin.php?resource=admission-statuses&operation=create`;

    submitBtn.disabled = true;
    submitLbl.textContent = isEdit ? "Saving…" : "Creating…";

    try {
      const { data } = await axios.post(url, payload, {
        headers: { "Content-Type": "application/json" },
        withCredentials: true,
      });

      if (data.success) {
        closeModal(modal);
        sessionStorage.setItem(
          "admission_status_flash",
          data.message || "Saved.",
        );
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
      submitLbl.textContent = isEdit ? "Save Changes" : "Create Status";
    }
  });

  
  
  
  let pendingDeleteId = null;

  document.querySelectorAll(".delete-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      pendingDeleteId = btn.getAttribute("data-status-id");
      deleteStatusName.textContent =
        btn.getAttribute("data-status-name") || "this status";
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
          `${baseUrl}/api/admin.php?resource=admission-statuses&operation=delete&id=${pendingDeleteId}`,
          {},
          {
            headers: { "Content-Type": "application/json" },
            withCredentials: true,
          },
        );

        if (data.success) {
          closeModal(deleteModal);
          sessionStorage.setItem(
            "admission_status_flash",
            data.message || "Status deleted.",
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
        confirmDeleteLbl.textContent = "Yes, delete";
        pendingDeleteId = null;
      }
    });
  }

  
  
  
  const searchInput = document.getElementById("filterSearch");
  const filterClear = document.getElementById("filterClear");
  const filterSummary = document.getElementById("filterSummary");
  const filteredCount = document.getElementById("filteredCount");
  const totalCount = document.getElementById("totalCount");
  const emptyState = document.getElementById("emptyState");

  const rows = Array.from(document.querySelectorAll(".admission-status-row"));
  if (totalCount) totalCount.textContent = rows.length;

  function applyFilters() {
    const q = (searchInput?.value || "").toLowerCase().trim();
    let visible = 0;

    rows.forEach((row) => {
      const match = q === "" || (row.dataset.search || "").includes(q);
      row.classList.toggle("hidden", !match);
      if (match) visible++;
    });

    if (filteredCount) filteredCount.textContent = visible;

    const isFiltering = q !== "";
    if (filterSummary) filterSummary.classList.toggle("hidden", !isFiltering);
    if (emptyState) emptyState.classList.toggle("hidden", visible > 0);

    if (filterClear) {
      filterClear.classList.toggle("hidden", !isFiltering);
      filterClear.classList.toggle("inline-flex", isFiltering);
    }
  }

  if (searchInput) searchInput.addEventListener("input", applyFilters);
  if (filterClear) {
    filterClear.addEventListener("click", () => {
      searchInput.value = "";
      applyFilters();
    });
  }

  applyFilters();

  
  
  
  const flash = sessionStorage.getItem("admission_status_flash");
  if (flash) {
    sessionStorage.removeItem("admission_status_flash");
    showAlert(flash, "success");
  }
});
