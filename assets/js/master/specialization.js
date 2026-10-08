


document.addEventListener("spa:page-loaded", async (event) => {
  if (event.detail.page !== "specialization") return;
  const baseUrl = document.body.dataset.baseUrl || "";
  const alertBox = document.getElementById("alert");

  
  const modal = document.getElementById("specializationModal");
  const form = document.getElementById("specializationForm");
  const modalTitle = document.getElementById("specializationModalTitle");
  const submitBtn = document.getElementById("specializationSubmitBtn");
  const submitLbl = document.getElementById("specializationSubmitLabel");
  const openCreate = document.getElementById("openCreateBtn");

  
  const deleteModal = document.getElementById("deleteModal");
  const deleteSpecializationName = document.getElementById(
    "deleteSpecializationName",
  );
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
      document.getElementById("specialization_id").value = "";
      modalTitle.textContent = "New Specialization";
      submitLbl.textContent = "Create Specialization";
      openModal(modal);
    });
  }

  
  
  
  document.querySelectorAll(".edit-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const s = JSON.parse(btn.dataset.specialization);

      form.reset();
      clearErrors(form);

      modalTitle.textContent = "Edit Specialization";
      submitLbl.textContent = "Save Changes";

      document.getElementById("specialization_id").value = s.specialization_id;
      document.getElementById("specialization_name").value =
        s.specialization_name;

      openModal(modal);
    });
  });

  
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    hideAlert();
    clearErrors(form);

    const id = document.getElementById("specialization_id").value;
    const isEdit = id !== "";

    const payload = {
      specialization_name: document
        .getElementById("specialization_name")
        .value.trim(),
    };

    const url = isEdit
      ? `${baseUrl}/api/admin.php?resource=specializations&operation=update&id=${id}`
      : `${baseUrl}/api/admin.php?resource=specializations&operation=create`;

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
          "specialization_flash",
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
      submitLbl.textContent = isEdit ? "Save Changes" : "Create Specialization";
    }
  });

  
  
  
  let pendingDeleteId = null;

  document.querySelectorAll(".delete-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      pendingDeleteId = btn.getAttribute("data-specialization-id");
      deleteSpecializationName.textContent =
        btn.getAttribute("data-specialization-name") || "this specialization";
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
          `${baseUrl}/api/admin.php?resource=specializations&operation=delete&id=${pendingDeleteId}`,
          {},
          {
            headers: { "Content-Type": "application/json" },
            withCredentials: true,
          },
        );

        if (data.success) {
          closeModal(deleteModal);
          sessionStorage.setItem(
            "specialization_flash",
            data.message || "Specialization deleted.",
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

  const rows = Array.from(document.querySelectorAll(".specialization-row"));
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

  
  
  
  const flash = sessionStorage.getItem("specialization_flash");
  if (flash) {
    sessionStorage.removeItem("specialization_flash");
    showAlert(flash, "success");
  }
});
