export async function initRoomPage(event) {
  const baseUrl = document.body.dataset.baseUrl || "";
  const signal = event?.detail?.signal || new AbortController().signal;

  const modal = document.getElementById("roomModal");
  const form = document.getElementById("roomForm");
  const modalTitle = document.getElementById("roomModalTitle");
  const submitBtn = document.getElementById("roomSubmitBtn");
  const submitLbl = document.getElementById("roomSubmitLabel");
  const openCreate = document.getElementById("openCreateBtn");

  const confirmModal = document.getElementById("confirmModal");
  const confirmRoomNumber = document.getElementById("confirmRoomNumber");
  const confirmDeactivateBtn = document.getElementById("confirmDeactivateBtn");
  const confirmDeactivateLbl = document.getElementById("confirmDeactivateLabel");

  const deleteModal = document.getElementById("deleteModal");
  const deleteRoomNumber = document.getElementById("deleteRoomNumber");
  const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
  const confirmDeleteLbl = document.getElementById("confirmDeleteLabel");

  const floorLevelSelect = document.getElementById("floor_level_id");

  const holder = document.getElementById("roomsData");
  let FLOOR_LEVELS = [];

  if (holder) {
    const raw = holder.dataset.floorLevels || holder.getAttribute("data-floor-levels") || "[]";
    try {
      FLOOR_LEVELS = JSON.parse(raw);
    } catch (e) {
      console.error("[room.js] Failed to parse floor levels:", raw, e);
    }
  }

  function populateFloorLevels(buildingId, selectedFloorId = null) {
    if (!floorLevelSelect) return;

    floorLevelSelect.innerHTML = '<option value="">— Select floor level —</option>';

    if (!buildingId) return;

    const target = String(buildingId);
    const matches = FLOOR_LEVELS.filter((fl) => String(fl.building_id) === target);

    if (matches.length === 0) {
      const opt = document.createElement("option");
      opt.value = "";
      opt.disabled = true;
      opt.textContent = "No floor levels for this building";
      floorLevelSelect.appendChild(opt);
      return;
    }

    matches.forEach((fl) => {
      const opt = document.createElement("option");
      opt.value = String(fl.floor_level_id);
      opt.textContent = "Floor " + fl.floor_level_name;
      if (selectedFloorId && String(fl.floor_level_id) === String(selectedFloorId)) {
        opt.selected = true;
      }
      floorLevelSelect.appendChild(opt);
    });
  }

  document.addEventListener("change", (e) => {
    if (e.target && e.target.id === "building_id") {
      populateFloorLevels(e.target.value);
    }
  }, { signal });

  const showAlert = (msg, type = "error") => {
    if (typeof window.showAppAlert === "function") {
      window.showAppAlert(msg, {
        icon: type === "success" ? "success" : "error",
        title: type === "success" ? "Room updated" : "Room action failed",
      });
      return;
    }
    if (window.Swal?.fire) {
      window.Swal.fire({
        icon: type === "success" ? "success" : "error",
        title: type === "success" ? "Room updated" : "Room action failed",
        text: String(msg),
      });
      return;
    }
    console.error(String(msg));
  };
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
    el.addEventListener("click", () => closeModal(modal), { signal });
  });
  document.querySelectorAll("[data-close-confirm]").forEach((el) => {
    el.addEventListener("click", () => closeModal(confirmModal), { signal });
  });
  document.querySelectorAll("[data-close-delete]").forEach((el) => {
    el.addEventListener("click", () => closeModal(deleteModal), { signal });
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeModal(modal);
      closeModal(confirmModal);
      closeModal(deleteModal);
    }
  }, { signal });

  if (openCreate) {
    openCreate.addEventListener("click", () => {
      form.reset();
      clearErrors(form);
      document.getElementById("room_id").value = "";
      populateFloorLevels("");
      modalTitle.textContent = "New Room";
      submitLbl.textContent = "Create Room";
      openModal(modal);
    }, { signal });
  }

  document.querySelectorAll(".edit-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const r = JSON.parse(btn.dataset.room);

      form.reset();
      clearErrors(form);

      modalTitle.textContent = "Edit Room";
      submitLbl.textContent = "Save Changes";

      document.getElementById("room_id").value = r.room_id;
      document.getElementById("room_number").value = r.room_number;
      document.getElementById("room_type_id").value = r.room_type_id;
      document.getElementById("status_id").value = r.status_id;
      document.getElementById("building_id").value = r.building_id || "";

      populateFloorLevels(r.building_id, r.floor_level_id);

      openModal(modal);
    }, { signal });
  });

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    clearErrors(form);

    const id = document.getElementById("room_id").value;
    const isEdit = id !== "";

    const payload = {
      room_type_id: document.getElementById("room_type_id").value,
      status_id: document.getElementById("status_id").value,
      room_number: document.getElementById("room_number").value.trim(),
      floor_level_id: document.getElementById("floor_level_id").value,
    };

    const url = isEdit
      ? `${baseUrl}/api/admin.php?resource=rooms&operation=update&id=${id}`
      : `${baseUrl}/api/admin.php?resource=rooms&operation=create`;

    submitBtn.disabled = true;
    submitLbl.textContent = isEdit ? "Saving…" : "Creating…";

    try {
      const { data } = await axios.post(url, payload, {
        headers: { "Content-Type": "application/json" },
        withCredentials: true,
      });

      if (data.success) {
        closeModal(modal);
        sessionStorage.setItem("room_flash", data.message || "Saved.");
        await window.refreshAdminList();
      } else {
        if (data.errors)
          Object.entries(data.errors).forEach(([f, m]) => setError(form, f, m));
        showAlert(data.message || "Save failed.", "error");
      }
    } catch (err) {
      const res = err.response?.data;
      if (res?.errors)
        Object.entries(res.errors).forEach(([f, m]) => setError(form, f, m));
      showAlert(res?.message || "Save failed.", "error");
    } finally {
      submitBtn.disabled = false;
      submitLbl.textContent = isEdit ? "Save Changes" : "Create Room";
    }
  }, { signal });

  document.querySelectorAll(".deactivate-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      confirmRoomNumber.textContent = btn.dataset.roomNumber || "this room";
      confirmDeactivateBtn.dataset.roomId = btn.dataset.roomId;
      openModal(confirmModal);
    }, { signal });
  });

  if (confirmDeactivateBtn) {
    confirmDeactivateBtn.addEventListener("click", async () => {
      const id = confirmDeactivateBtn.dataset.roomId;
      if (!id) return;

      confirmDeactivateBtn.disabled = true;
      confirmDeactivateLbl.textContent = "Archiving…";

      try {
        const { data } = await axios.post(
          `${baseUrl}/api/admin.php?resource=rooms&operation=toggle-active&id=${id}`,
          {},
          { headers: { "Content-Type": "application/json" }, withCredentials: true }
        );

        if (data.success) {
          closeModal(confirmModal);
          sessionStorage.setItem("room_flash", data.message || "Room archived.");
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
        confirmDeactivateLbl.textContent = "Archive Room";
      }
    }, { signal });
  }

  document.querySelectorAll(".reactivate-btn").forEach((btn) => {
    btn.addEventListener("click", async () => {
      const id = btn.dataset.roomId;
      if (!id) return;

      btn.disabled = true;

      try {
        const { data } = await axios.post(
          `${baseUrl}/api/admin.php?resource=rooms&operation=toggle-active&id=${id}`,
          {},
          { headers: { "Content-Type": "application/json" }, withCredentials: true }
        );

        if (data.success) {
          sessionStorage.setItem("room_flash", data.message || "Room reactivated.");
          await window.refreshAdminList();
        } else {
          showAlert(data.message || "Action failed.", "error");
        }
      } catch (err) {
        showAlert(err.response?.data?.message || "Action failed.", "error");
      } finally {
        btn.disabled = false;
      }
    }, { signal });
  });

  let pendingDeleteId = null;

  document.querySelectorAll(".delete-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      pendingDeleteId = btn.dataset.roomId;
      deleteRoomNumber.textContent = btn.dataset.roomNumber || "this room";
      openModal(deleteModal);
    }, { signal });
  });

  if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener("click", async () => {
      if (!pendingDeleteId) return;

      confirmDeleteBtn.disabled = true;
      confirmDeleteLbl.textContent = "Deleting…";

      try {
        const { data } = await axios.post(
          `${baseUrl}/api/admin.php?resource=rooms&operation=delete&id=${pendingDeleteId}`,
          {},
          { headers: { "Content-Type": "application/json" }, withCredentials: true }
        );

        if (data.success) {
          closeModal(deleteModal);
          sessionStorage.setItem("room_flash", data.message || "Room deleted.");
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
    }, { signal });
  }

  const searchInput = document.getElementById("filterSearch");
  const showArchived = document.getElementById("showArchived");
  const filterClear = document.getElementById("filterClear");
  const filterSummary = document.getElementById("filterSummary");
  const filteredCount = document.getElementById("filteredCount");
  const totalCount = document.getElementById("totalCount");
  const emptyState = document.getElementById("emptyState");

  const rows = Array.from(document.querySelectorAll(".room-row"));
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

  if (searchInput) searchInput.addEventListener("input", applyFilters, { signal });
  if (showArchived) showArchived.addEventListener("change", applyFilters, { signal });
  if (filterClear) {
    filterClear.addEventListener("click", () => {
      searchInput.value = "";
      showArchived.checked = false;
      applyFilters();
    }, { signal });
  }

  applyFilters();

  const flash = sessionStorage.getItem("room_flash");
  if (flash) {
    sessionStorage.removeItem("room_flash");
    showAlert(flash, "success");
  }
}