
document.addEventListener("spa:page-loaded", async (event) => {
  if (event.detail.page !== "room-type") return;
  const baseUrl = document.body.dataset.baseUrl || "";
  const alertBox = document.getElementById("alert");

  const modal = document.getElementById("roomTypeModal");
  const form = document.getElementById("roomTypeForm");
  const modalTitle = document.getElementById("roomTypeModalTitle");
  const submitBtn = document.getElementById("roomTypeSubmitBtn");
  const submitLbl = document.getElementById("roomTypeSubmitLabel");
  const openCreate = document.getElementById("openCreateBtn");

  const deleteModal = document.getElementById("deleteModal");
  const deleteRoomTypeName = document.getElementById("deleteRoomTypeName");
  const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
  const confirmDeleteLbl = document.getElementById("confirmDeleteLabel");

  const showAlert = (msg, type = "error") => {
    if (!alertBox) return;
    const styles = {
      error: "bg-red-50 border-red-200 text-red-700",
      success: "bg-emerald-50 border-emerald-200 text-emerald-700",
    };
    alertBox.className = `mb-5 rounded-lg px-4 py-3 text-sm border ${styles[type] || styles.error}`;
    alertBox.textContent = msg;
    alertBox.classList.remove("hidden");
    alertBox.scrollIntoView({ behavior: "smooth", block: "nearest" });
  };
  const hideAlert = () => alertBox?.classList.add("hidden");

  const clearErrors = (scope) => {
    if (!scope) return;
    scope.querySelectorAll("[data-error-for]").forEach((p) => {
      p.textContent = "";
      p.classList.add("hidden");
    });
  };
  const setError = (scope, field, msg) => {
    if (!scope) return;
    const p = scope.querySelector(`[data-error-for="${field}"]`);
    if (!p) return;
    p.textContent = msg || "";
    p.classList.toggle("hidden", !msg);
  };

  const openModal = (el) => el?.classList.remove("hidden");
  const closeModal = (el) => el?.classList.add("hidden");

  document
    .querySelectorAll("[data-close-modal]")
    .forEach((el) => el.addEventListener("click", () => closeModal(modal)));
  document
    .querySelectorAll("[data-close-delete]")
    .forEach((el) =>
      el.addEventListener("click", () => closeModal(deleteModal)),
    );
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeModal(modal);
      closeModal(deleteModal);
    }
  }, { signal: event.detail.signal });

  if (openCreate) {
    openCreate.addEventListener("click", () => {
      form?.reset();
      clearErrors(form);
      const idField = document.getElementById("room_type_id");
      if (idField) idField.value = "";
      if (modalTitle) modalTitle.textContent = "New Room Type";
      if (submitLbl) submitLbl.textContent = "Create Room Type";
      openModal(modal);
    });
  }

  document.querySelectorAll(".edit-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const rt = JSON.parse(btn.dataset.roomType);

      form?.reset();
      clearErrors(form);

      if (modalTitle) modalTitle.textContent = "Edit Room Type";
      if (submitLbl) submitLbl.textContent = "Save Changes";

      const idField = document.getElementById("room_type_id");
      const nameField = document.getElementById("room_type_name");
      const descField = document.getElementById("description");
      const rateField = document.getElementById("rate_per_day");
      const capField = document.getElementById("capacity");

      if (idField) idField.value = rt.room_type_id;
      if (nameField) nameField.value = rt.room_type_name;
      if (descField) descField.value = rt.description || "";
      if (rateField) rateField.value = rt.rate_per_day;
      if (capField) capField.value = rt.capacity;

      openModal(modal);
    });
  });

  form?.addEventListener("submit", async (e) => {
    e.preventDefault();
    hideAlert();
    clearErrors(form);

    const idField = document.getElementById("room_type_id");
    const nameField = document.getElementById("room_type_name");
    const descField = document.getElementById("description");
    const rateField = document.getElementById("rate_per_day");
    const capField = document.getElementById("capacity");

    const id = idField?.value || "";
    const isEdit = id !== "";

    const payload = {
      room_type_name: nameField?.value.trim() || "",
      description: descField?.value.trim() || "",
      rate_per_day: rateField?.value || "",
      capacity: capField?.value || "",
    };

    const url = isEdit
      ? `${baseUrl}/api/admin.php?resource=room-types&operation=update&id=${id}`
      : `${baseUrl}/api/admin.php?resource=room-types&operation=create`;

    if (submitBtn) submitBtn.disabled = true;
    if (submitLbl) submitLbl.textContent = isEdit ? "Saving…" : "Creating…";

    try {
      const { data } = await axios.post(url, payload, {
        headers: { "Content-Type": "application/json" },
        withCredentials: true,
      });

      if (data.success) {
        closeModal(modal);
        sessionStorage.setItem("room_type_flash", data.message || "Saved.");
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
      if (submitBtn) submitBtn.disabled = false;
      if (submitLbl) submitLbl.textContent = isEdit ? "Save Changes" : "Create Room Type";
    }
  });

  let pendingDeleteId = null;

  document.querySelectorAll(".delete-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      pendingDeleteId = btn.getAttribute("data-room-type-id");
      if (deleteRoomTypeName) {
        deleteRoomTypeName.textContent =
          btn.getAttribute("data-room-type-name") || "this room type";
      }
      openModal(deleteModal);
    });
  });

  if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener("click", async () => {
      if (!pendingDeleteId) return;

      confirmDeleteBtn.disabled = true;
      if (confirmDeleteLbl) confirmDeleteLbl.textContent = "Deleting…";

      try {
        const { data } = await axios.post(
          `${baseUrl}/api/admin.php?resource=room-types&operation=delete&id=${pendingDeleteId}`,
          {},
          {
            headers: { "Content-Type": "application/json" },
            withCredentials: true,
          },
        );

        if (data.success) {
          closeModal(deleteModal);
          sessionStorage.setItem(
            "room_type_flash",
            data.message || "Room type deleted.",
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
        if (confirmDeleteLbl) confirmDeleteLbl.textContent = "Yes, delete";
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

  const rows = Array.from(document.querySelectorAll(".room-type-row"));
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

  const flash = sessionStorage.getItem("room_type_flash");
  if (flash) {
    sessionStorage.removeItem("room_type_flash");
    showAlert(flash, "success");
  }
});