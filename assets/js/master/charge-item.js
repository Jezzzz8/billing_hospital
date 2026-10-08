


document.addEventListener("spa:page-loaded", async (event) => {
  if (event.detail.page !== "charge-item") return;
  const baseUrl = document.body.dataset.baseUrl || "";
  const alertBox = document.getElementById("alert");

  
  const modal = document.getElementById("itemModal");
  const form = document.getElementById("itemForm");
  const modalTitle = document.getElementById("itemModalTitle");
  const submitBtn = document.getElementById("itemSubmitBtn");
  const submitLbl = document.getElementById("itemSubmitLabel");
  const openCreate = document.getElementById("openCreateBtn");

  
  const confirmModal = document.getElementById("confirmModal");
  const confirmName = document.getElementById("confirmItemName");
  const confirmDeactivateBtn = document.getElementById("confirmDeactivateBtn");
  const confirmDeactivateLbl = document.getElementById(
    "confirmDeactivateLabel",
  );

  
  const deleteModal = document.getElementById("deleteModal");
  const deleteItemName = document.getElementById("deleteItemName");
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
      document.getElementById("charge_item_id").value = "";
      document.getElementById("is_taxable").checked = false;
      document.getElementById("requires_doctor_order").checked = false;
      modalTitle.textContent = "New Charge Item";
      submitLbl.textContent = "Create Charge Item";
      openModal(modal);
    });
  }

  
  
  
  document.querySelectorAll(".edit-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const i = JSON.parse(btn.dataset.item);

      form.reset();
      clearErrors(form);

      modalTitle.textContent = "Edit Charge Item";
      submitLbl.textContent = "Save Changes";

      document.getElementById("charge_item_id").value = i.charge_item_id;
      document.getElementById("category_id").value = i.category_id;
      document.getElementById("item_code").value = i.item_code;
      document.getElementById("item_name").value = i.item_name;
      document.getElementById("default_price").value = i.default_price;
      document.getElementById("unit_of_measure").value =
        i.unit_of_measure || "";
      document.getElementById("is_taxable").checked =
        parseInt(i.is_taxable, 10) === 1;
      document.getElementById("requires_doctor_order").checked =
        parseInt(i.requires_doctor_order, 10) === 1;

      openModal(modal);
    });
  });

  
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    hideAlert();
    clearErrors(form);

    const id = document.getElementById("charge_item_id").value;
    const isEdit = id !== "";

    const payload = {
      category_id: document.getElementById("category_id").value,
      item_code: document.getElementById("item_code").value.trim(),
      item_name: document.getElementById("item_name").value.trim(),
      default_price: document.getElementById("default_price").value,
      unit_of_measure: document.getElementById("unit_of_measure").value.trim(),
      is_taxable: document.getElementById("is_taxable").checked ? 1 : 0,
      requires_doctor_order: document.getElementById("requires_doctor_order")
        .checked
        ? 1
        : 0,
    };

    const url = isEdit
      ? `${baseUrl}/api/admin.php?resource=charge-items&operation=update&id=${id}`
      : `${baseUrl}/api/admin.php?resource=charge-items&operation=create`;

    submitBtn.disabled = true;
    submitLbl.textContent = isEdit ? "Saving…" : "Creating…";

    try {
      const { data } = await axios.post(url, payload, {
        headers: { "Content-Type": "application/json" },
        withCredentials: true,
      });

      if (data.success) {
        closeModal(modal);
        sessionStorage.setItem("charge_item_flash", data.message || "Saved.");
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
      submitLbl.textContent = isEdit ? "Save Changes" : "Create Charge Item";
    }
  });

  
  
  
  document.querySelectorAll(".deactivate-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      confirmName.textContent = btn.getAttribute("data-item-name");
      confirmDeactivateBtn.dataset.itemId = btn.getAttribute("data-item-id");
      openModal(confirmModal);
    });
  });

  if (confirmDeactivateBtn) {
    confirmDeactivateBtn.addEventListener("click", async () => {
      const id = confirmDeactivateBtn.dataset.itemId;
      if (!id) return;

      confirmDeactivateBtn.disabled = true;
      confirmDeactivateLbl.textContent = "Archiving…";

      try {
        const { data } = await axios.post(
          `${baseUrl}/api/admin.php?resource=charge-items&operation=toggle-active&id=${id}`,
          {},
          {
            headers: { "Content-Type": "application/json" },
            withCredentials: true,
          },
        );

        if (data.success) {
          closeModal(confirmModal);
          sessionStorage.setItem(
            "charge_item_flash",
            data.message || "Item archived.",
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
        confirmDeactivateLbl.textContent = "Archive Item";
      }
    });
  }

  
  
  
  document.querySelectorAll(".reactivate-btn").forEach((btn) => {
    btn.addEventListener("click", async () => {
      const id = btn.getAttribute("data-item-id");
      if (!id) return;

      btn.disabled = true;

      try {
        const { data } = await axios.post(
          `${baseUrl}/api/admin.php?resource=charge-items&operation=toggle-active&id=${id}`,
          {},
          {
            headers: { "Content-Type": "application/json" },
            withCredentials: true,
          },
        );

        if (data.success) {
          sessionStorage.setItem(
            "charge_item_flash",
            data.message || "Item reactivated.",
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
      pendingDeleteId = btn.getAttribute("data-item-id");
      deleteItemName.textContent =
        btn.getAttribute("data-item-name") || "this charge item";
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
          `${baseUrl}/api/admin.php?resource=charge-items&operation=delete&id=${pendingDeleteId}`,
          {},
          {
            headers: { "Content-Type": "application/json" },
            withCredentials: true,
          },
        );

        if (data.success) {
          closeModal(deleteModal);
          sessionStorage.setItem(
            "charge_item_flash",
            data.message || "Item deleted.",
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

  
  
  
  const searchInput = document.getElementById("filterSearch");
  const showArchived = document.getElementById("showArchived");
  const filterClear = document.getElementById("filterClear");
  const filterSummary = document.getElementById("filterSummary");
  const filteredCount = document.getElementById("filteredCount");
  const totalCount = document.getElementById("totalCount");
  const emptyState = document.getElementById("emptyState");

  const rows = Array.from(document.querySelectorAll(".charge-item-row"));
  if (totalCount) totalCount.textContent = rows.length;

  function applyFilters() {
    const q = (searchInput?.value || "").toLowerCase().trim();
    const showInactiveOnly = showArchived?.checked || false;

    let visible = 0;

    rows.forEach((row) => {
      const matchesSearch = q === "" || (row.dataset.search || "").includes(q);

      const isArchived = row.dataset.status === "0";
      const matchesArchiveFilter = showInactiveOnly ? isArchived : !isArchived;

      const show = matchesSearch && matchesArchiveFilter;
      row.classList.toggle("hidden", !show);
      if (show) visible++;
    });

    if (filteredCount) filteredCount.textContent = visible;

    const isFiltering = q !== "" || showInactiveOnly;
    if (filterSummary) filterSummary.classList.toggle("hidden", !isFiltering);
    if (emptyState) emptyState.classList.toggle("hidden", visible > 0);

    if (filterClear) {
      filterClear.classList.toggle("hidden", !isFiltering);
      filterClear.classList.toggle("inline-flex", isFiltering);
    }
  }

  if (searchInput) searchInput.addEventListener("input", applyFilters);
  if (showArchived) showArchived.addEventListener("change", applyFilters);
  if (filterClear) {
    filterClear.addEventListener("click", () => {
      searchInput.value = "";
      showArchived.checked = false;
      applyFilters();
    });
  }

  applyFilters();

  
  
  
  const flash = sessionStorage.getItem("charge_item_flash");
  if (flash) {
    sessionStorage.removeItem("charge_item_flash");
    showAlert(flash, "success");
  }
});
