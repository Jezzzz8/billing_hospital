document.addEventListener("spa:page-loaded", (event) => {
  if (event.detail.page !== "patients") return;
  const baseUrl = document.body.dataset.baseUrl || "";
  const apiUrl = `${baseUrl}/api/patients.php`;
  const alertBox = document.getElementById("alert");
  const modal = document.getElementById("patientModal");
  const form = document.getElementById("patientForm");
  const modalTitle = document.getElementById("patientModalTitle");
  const submitBtn = document.getElementById("patientSubmitBtn");
  const submitLabel = document.getElementById("patientSubmitLabel");
  const rows = document.getElementById("patientRows");
  const emptyState = document.getElementById("emptyState");
  const confirmModal = document.getElementById("confirmModal");
  const confirmPatientName = document.getElementById("confirmPatientName");
  const confirmButton = document.getElementById("confirmDeactivateBtn");
  const confirmLabel = document.getElementById("confirmDeactivateLabel");
  const createAdmission = document.getElementById("create_admission");
  const tabButtons = document.querySelectorAll(".tab-btn");
  const tabPanels = document.querySelectorAll(".tab-panel");
  const doctorsFields = document.getElementById("doctorsFields");
  const diagnosesFields = document.getElementById("diagnosesFields");
  const roomFields = document.getElementById("roomFields");
  const admissionFields = document.getElementById("admissionFields");
  const doctorsList = document.getElementById("doctorsList");
  const diagnosesList = document.getElementById("diagnosesList");

  let patients = [];
  let doctors = [];
  let diagnoses = [];
  let pendingArchiveId = null;
  let currentTab = "info";
  const tabOrder = ["info", "admission", "room", "doctors", "diagnoses"];

  async function getOperation(operation, parameters = {}) {
    const params = { operation };
    if (Object.keys(parameters).length) {
      params.json = JSON.stringify(parameters);
    }
    const response = await axios.get(apiUrl, { params, withCredentials: true });
    return response.data;
  }

  async function postOperation(operation, parameters = {}) {
    const body = new FormData();
    body.append("operation", operation);
    body.append("json", JSON.stringify(parameters));
    const response = await axios.post(apiUrl, body, { withCredentials: true });
    return response.data;
  }

  function showAlert(message, type = "error") {
    const styles = {
      error: "bg-red-50 border-red-200 text-red-700",
      success: "bg-emerald-50 border-emerald-200 text-emerald-700",
    };
    alertBox.className = `mb-5 rounded-lg px-4 py-3 text-sm border ${styles[type] || styles.error}`;
    alertBox.textContent = message;
    alertBox.classList.remove("hidden");
    alertBox.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }

  function hideAlert() {
    alertBox.classList.add("hidden");
  }

  function showFieldErrors(errors = {}) {
    form.querySelectorAll("[data-error-for]").forEach((element) => {
      element.textContent = "";
      element.classList.add("hidden");
    });
    Object.entries(errors).forEach(([field, message]) => {
      const element = form.querySelector(`[data-error-for="${field}"]`);
      if (!element) return;
      element.textContent = message;
      element.classList.remove("hidden");
    });
  }

  function escapeHtml(value) {
    const element = document.createElement("span");
    element.textContent = value == null ? "" : String(value);
    return element.innerHTML;
  }

  function formatDate(value) {
    if (!value) return "—";
    const date = new Date(`${String(value).slice(0, 10)}T00:00:00`);
    return Number.isNaN(date.getTime())
      ? escapeHtml(value)
      : date.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" });
  }

  function renderPatients() {
    const query = (document.getElementById("filterSearch").value || "").trim().toLowerCase();
    const archivedOnly = document.getElementById("showArchived").checked;
    const activeCount = patients.filter((patient) => Number(patient.is_active) === 1).length;
    const admittedCount = patients.filter((patient) => Boolean(patient.admission_id)).length;

    document.getElementById("totalPatients").textContent = patients.length;
    document.getElementById("activePatients").textContent = activeCount;
    document.getElementById("admittedPatients").textContent = admittedCount;
    document.getElementById("archivedPatients").textContent = patients.length - activeCount;

    const visiblePatients = patients.filter((patient) => {
      const search = [
        patient.first_name,
        patient.last_name,
        patient.email,
        patient.room_number,
      ].join(" ").toLowerCase();
      const matchesSearch = !query || search.includes(query);
      const matchesStatus = archivedOnly
        ? Number(patient.is_active) === 0
        : Number(patient.is_active) === 1;
      return matchesSearch && matchesStatus;
    });

    rows.innerHTML = visiblePatients.map((patient) => {
      const id = Number(patient.patient_id);
      const active = Number(patient.is_active) === 1;
      const name = `${patient.first_name || ""} ${patient.last_name || ""}`.trim();
      const initials = `${(patient.first_name || "").slice(0, 1)}${(patient.last_name || "").slice(0, 1)}`;
      const admissionColor = /^#[0-9a-f]{6}$/i.test(patient.admission_status_color || "")
        ? patient.admission_status_color
        : "#6b7280";
      const admission = patient.admission_id
        ? `<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium border" style="background-color:${admissionColor}1A;color:${admissionColor};border-color:${admissionColor}40"><span class="w-1.5 h-1.5 rounded-full" style="background-color:${admissionColor}"></span>${escapeHtml(patient.admission_status_name || "—")}</span><p class="text-xs text-slate-500 mt-1">${formatDate(patient.admission_datetime)}</p>`
        : '<span class="text-xs text-slate-400 italic">Not admitted</span>';
      const room = patient.room_number
        ? `<p class="font-medium text-slate-900">${escapeHtml(patient.room_number)}${Number(patient.room_assignment_is_active) === 1 ? "" : ' <span class="ml-1 text-xs font-normal text-slate-400">(past)</span>'}</p><p class="text-xs text-slate-500">${escapeHtml(patient.room_type_name || "")}</p>`
        : '<span class="text-xs text-slate-400">—</span>';
      const state = active
        ? '<span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 border border-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Active</span>'
        : '<span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600"><span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>Archived</span>';
      const action = active
        ? '<button type="button" data-action="archive" class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100">Archive</button>'
        : '<button type="button" data-action="reactivate" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100">Reactivate</button>';

      return `<tr class="hover:bg-slate-50 patient-row">
        <td class="px-6 py-4"><div class="flex items-center gap-3"><div class="w-9 h-9 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-semibold text-xs shrink-0">${escapeHtml(initials)}</div><div class="min-w-0"><p class="font-semibold text-slate-900 truncate">${escapeHtml(name)}</p><p class="text-xs text-slate-500">${escapeHtml(patient.gender_name || "")} · ${escapeHtml(patient.birth_date || "—")}</p></div></div></td>
        <td class="px-6 py-4"><p class="text-slate-700 text-xs truncate">${escapeHtml(patient.email || "—")}</p><p class="text-xs text-slate-500">${escapeHtml(patient.contact_number || "")}</p></td>
        <td class="px-6 py-4">${admission}</td>
        <td class="px-6 py-4">${room}</td>
        <td class="px-6 py-4">${state}</td>
        <td class="px-6 py-4 text-right whitespace-nowrap"><div class="inline-flex items-center gap-1.5"><button type="button" data-action="edit" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Edit</button>${action}<input type="hidden" data-patient-name value="${escapeHtml(name)}"><input type="hidden" data-patient-id value="${id}"></div></td>
      </tr>`;
    }).join("");

    document.getElementById("totalCount").textContent = patients.length;
    document.getElementById("filteredCount").textContent = visiblePatients.length;
    const filtering = Boolean(query) || archivedOnly;
    document.getElementById("filterSummary").classList.toggle("hidden", !filtering);
    const clearButton = document.getElementById("filterClear");
    clearButton.classList.toggle("hidden", !filtering);
    clearButton.classList.toggle("inline-flex", filtering);
    emptyState.classList.toggle("hidden", visiblePatients.length > 0);
  }

  function fillSelect(selectId, items, valueKey, labelFn, placeholder) {
    const select = document.getElementById(selectId);
    select.replaceChildren(new Option(placeholder, ""));
    items.forEach((item) => select.add(new Option(labelFn(item), item[valueKey])));
  }

  function renderOptions(data) {
    doctors = (data.doctors || []).map((doctor) => ({
      ...doctor,
      label: `Dr. ${doctor.first_name} ${doctor.last_name}`,
    }));
    diagnoses = data.diagnoses || [];

    fillSelect("gender_id", data.genders || [], "gender_id", (item) => item.gender_name, "— Select gender —");
    fillSelect("admission_status_id", data.admission_statuses || [], "status_id", (item) => item.status_name, "— Select status —");
    document.getElementById("admission_status_id").value =
      (data.admission_statuses || []).some((item) => Number(item.status_id) === 1) ? "1" : "";

    const roomSelect = document.getElementById("room_id");
    roomSelect.replaceChildren(new Option("— No room assigned —", ""));
    (data.rooms || []).forEach((room) => {
      const label = `${room.room_number} — ${room.room_type_name}${room.building_name ? ` · ${room.building_name}` : ""}${room.status_name === "Occupied" ? " (Occupied)" : ""}`;
      const option = new Option(label, room.room_id);
      option.dataset.occupiedBy = room.occupied_by_admission_id || "0";
      roomSelect.add(option);
    });
  }

  async function loadPatients() {
    const result = await getOperation("list");
    if (!result.success || !Array.isArray(result.data)) {
      throw new Error(result.message || "Could not load patients.");
    }
    patients = result.data;
    renderPatients();
  }

  function openModal(element) {
    element.classList.remove("hidden");
  }

  function closeModal(element) {
    element.classList.add("hidden");
  }

  function setActiveTab(tab) {
    currentTab = tab;
    tabButtons.forEach((button) => {
      const active = button.dataset.tab === tab;
      button.classList.toggle("border-blue-600", active);
      button.classList.toggle("text-blue-600", active);
      button.classList.toggle("border-transparent", !active);
      button.classList.toggle("text-slate-500", !active);
    });
    tabPanels.forEach((panel) => {
      panel.classList.toggle("hidden", panel.dataset.panel !== tab);
    });
    const index = tabOrder.indexOf(tab);
    document.getElementById("prevTab").classList.toggle("hidden", index <= 0);
    document.getElementById("nextTab").classList.toggle("hidden", index >= tabOrder.length - 1);
    submitBtn.classList.toggle("hidden", index < tabOrder.length - 1);
  }

  function toggleAdmissionFields() {
    const enabled = createAdmission.checked;
    [admissionFields, roomFields, doctorsFields, diagnosesFields].forEach((element) => {
      element.classList.toggle("opacity-50", !enabled);
      element.classList.toggle("pointer-events-none", !enabled);
    });
  }

  function removeInjectedRoomOptions() {
    document.querySelectorAll('#room_id option[data-injected="1"]').forEach((option) => option.remove());
  }

  function applyRoomOccupancy(admissionId) {
    const editingId = String(admissionId || 0);
    Array.from(document.getElementById("room_id").options).forEach((option) => {
      if (!option.value || option.dataset.injected === "1") {
        option.disabled = false;
        return;
      }
      const occupiedBy = String(option.dataset.occupiedBy || 0);
      option.disabled = occupiedBy !== "0" && occupiedBy !== "" && occupiedBy !== editingId;
    });
  }

  function selectCurrentRoom(patient) {
    const roomSelect = document.getElementById("room_id");
    const assignment = patient.room_assignment;
    applyRoomOccupancy(patient.admission_id);
    if (!assignment || !assignment.room_id) {
      roomSelect.value = "";
      return;
    }

    const roomId = String(assignment.room_id);
    let option = Array.from(roomSelect.options).find((item) => item.value === roomId);
    if (!option) {
      option = new Option(
        `${assignment.room_number || "Room"} — ${assignment.room_type_name || ""}${Number(assignment.is_active) === 1 ? "" : " (past)"}`,
        roomId,
      );
      option.dataset.injected = "1";
      roomSelect.add(option);
    }
    option.disabled = false;
    roomSelect.value = roomId;
  }

  function addDoctorRow(selectedId = "", selectedRole = "Attending") {
    const row = document.createElement("div");
    row.className = "flex items-center gap-2 doctor-row";
    const select = document.createElement("select");
    select.className = "doctor-select flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white";
    select.add(new Option("— Select doctor —", ""));
    doctors.forEach((doctor) => {
      select.add(new Option(`${doctor.label} (₱${Number(doctor.consultation_fee).toFixed(2)})`, doctor.doctor_id));
    });
    select.value = String(selectedId);

    const role = document.createElement("select");
    role.className = "doctor-role rounded-lg border border-slate-300 px-2 py-2 text-sm bg-white";
    ["Attending", "Consulting", "Referring"].forEach((value) => role.add(new Option(value, value)));
    role.value = selectedRole;

    const remove = document.createElement("button");
    remove.type = "button";
    remove.className = "remove-row-btn text-rose-500 hover:text-rose-700 p-1";
    remove.textContent = "Remove";
    remove.addEventListener("click", () => row.remove());

    row.append(select, role, remove);
    doctorsList.appendChild(row);
  }

  function addDiagnosisRow(selectedId = "", selectedType = "Primary") {
    const row = document.createElement("div");
    row.className = "flex items-center gap-2 diagnosis-row";
    const select = document.createElement("select");
    select.className = "diagnosis-select flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white";
    select.add(new Option("— Select diagnosis —", ""));
    diagnoses.forEach((diagnosis) => {
      const label = `${diagnosis.icd_code ? `${diagnosis.icd_code} — ` : ""}${diagnosis.diagnosis_name}`;
      select.add(new Option(label, diagnosis.diagnosis_id));
    });
    select.value = String(selectedId);

    const type = document.createElement("select");
    type.className = "diagnosis-type rounded-lg border border-slate-300 px-2 py-2 text-sm bg-white";
    ["Primary", "Secondary"].forEach((value) => type.add(new Option(value, value)));
    type.value = selectedType;

    const remove = document.createElement("button");
    remove.type = "button";
    remove.className = "remove-row-btn text-rose-500 hover:text-rose-700 p-1";
    remove.textContent = "Remove";
    remove.addEventListener("click", () => row.remove());

    row.append(select, type, remove);
    diagnosesList.appendChild(row);
  }

  function resetPatientForm() {
    form.reset();
    showFieldErrors();
    removeInjectedRoomOptions();
    document.getElementById("patient_id").value = "";
    document.getElementById("admission_id").value = "";
    doctorsList.replaceChildren();
    diagnosesList.replaceChildren();
    createAdmission.disabled = false;
    document.getElementById("admission_status_id").value = "1";
    applyRoomOccupancy(0);
    toggleAdmissionFields();
    setActiveTab("info");
  }

  async function editPatient(id) {
    resetPatientForm();
    modalTitle.textContent = "Edit Patient";
    submitLabel.textContent = "Save Changes";
    document.getElementById("patient_id").value = id;
    openModal(modal);
    try {
      const result = await getOperation("details", { patient_id: id });
      if (!result.success) throw new Error(result.message || "Could not load patient details.");
      const patient = result.data;
      [
        "first_name", "last_name", "gender_id", "birth_date", "email",
        "contact_number", "address", "emergency_contact",
        "emergency_contact_number", "medical_history", "admission_id",
        "admission_status_id", "admission_type", "chief_complaint", "admission_notes",
      ].forEach((field) => {
        const input = document.getElementById(field);
        if (input) input.value = patient[field] || "";
      });
      document.getElementById("admission_status_id").value = patient.admission_status_id || "1";
      createAdmission.checked = Boolean(patient.admission_id);
      selectCurrentRoom(patient);
      (patient.doctors || []).forEach((doctor) => addDoctorRow(doctor.doctor_id, doctor.doctor_role));
      (patient.diagnoses || []).forEach((diagnosis) => addDiagnosisRow(diagnosis.diagnosis_id, diagnosis.diagnosis_type));
      toggleAdmissionFields();
    } catch (error) {
      closeModal(modal);
      showAlert(error.response?.data?.message || error.message || "Could not load patient details.");
    }
  }

  function collectFormData() {
    const doctorsPayload = Array.from(form.querySelectorAll(".doctor-row")).flatMap((row) => {
      const doctorId = row.querySelector(".doctor-select").value;
      return doctorId
        ? [{ doctor_id: Number(doctorId), doctor_role: row.querySelector(".doctor-role").value }]
        : [];
    });
    const diagnosesPayload = Array.from(form.querySelectorAll(".diagnosis-row")).flatMap((row) => {
      const diagnosisId = row.querySelector(".diagnosis-select").value;
      return diagnosisId
        ? [{ diagnosis_id: Number(diagnosisId), diagnosis_type: row.querySelector(".diagnosis-type").value }]
        : [];
    });

    const data = Object.fromEntries([
      "gender_id", "first_name", "last_name", "birth_date", "email",
      "contact_number", "address", "emergency_contact",
      "emergency_contact_number", "medical_history", "admission_status_id",
      "admission_type", "chief_complaint", "admission_notes", "room_id",
    ].map((field) => [field, document.getElementById(field)?.value.trim() || ""]));
    data.create_admission = createAdmission.checked ? 1 : 0;
    data.doctors = doctorsPayload;
    data.diagnoses = diagnosesPayload;
    const patientId = document.getElementById("patient_id").value;
    if (patientId) {
      data.patient_id = Number(patientId);
      data.admission_id = document.getElementById("admission_id").value || 0;
    }
    return data;
  }

  async function savePatient(event) {
    event.preventDefault();
    hideAlert();
    showFieldErrors();
    const patientId = document.getElementById("patient_id").value;
    const operation = patientId ? "update" : "create";
    submitBtn.disabled = true;
    submitLabel.textContent = patientId ? "Saving…" : "Creating…";

    try {
      const result = await postOperation(operation, collectFormData());
      if (!result.success) {
        showFieldErrors(result.errors || {});
        showAlert(result.message || "Could not save patient.");
        return;
      }
      closeModal(modal);
      await loadPatients();
      showAlert(result.message || "Patient saved successfully.", "success");
    } catch (error) {
      const response = error.response?.data;
      showFieldErrors(response?.errors || {});
      showAlert(response?.message || "Could not save patient.");
    } finally {
      submitBtn.disabled = false;
      submitLabel.textContent = patientId ? "Save Changes" : "Save Patient";
    }
  }

  async function changePatientState(id) {
    try {
      const result = await postOperation("toggle-active", { patient_id: Number(id) });
      if (!result.success) throw new Error(result.message || "Could not update patient status.");
      await loadPatients();
      closeModal(confirmModal);
      showAlert(result.message || "Patient status updated.", "success");
    } catch (error) {
      showAlert(error.response?.data?.message || error.message || "Could not update patient status.");
    }
  }

  document.getElementById("openCreateBtn").addEventListener("click", () => {
    resetPatientForm();
    modalTitle.textContent = "New Patient";
    submitLabel.textContent = "Save Patient";
    openModal(modal);
  });
  form.addEventListener("submit", savePatient);
  createAdmission.addEventListener("change", toggleAdmissionFields);
  tabButtons.forEach((button) => button.addEventListener("click", () => setActiveTab(button.dataset.tab)));
  document.getElementById("prevTab").addEventListener("click", () => {
    const index = tabOrder.indexOf(currentTab);
    if (index > 0) setActiveTab(tabOrder[index - 1]);
  });
  document.getElementById("nextTab").addEventListener("click", () => {
    const index = tabOrder.indexOf(currentTab);
    if (index < tabOrder.length - 1) setActiveTab(tabOrder[index + 1]);
  });
  document.getElementById("addDoctorRow").addEventListener("click", () => addDoctorRow());
  document.getElementById("addDiagnosisRow").addEventListener("click", () => addDiagnosisRow());
  document.querySelectorAll("[data-close-modal]").forEach((button) => {
    button.addEventListener("click", () => {
      closeModal(modal);
      closeModal(confirmModal);
    });
  });
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeModal(modal);
      closeModal(confirmModal);
    }
  }, { signal: event.detail.signal });
  rows.addEventListener("click", async (event) => {
    const button = event.target.closest("button[data-action]");
    if (!button) return;
    const row = button.closest("tr");
    const id = row.querySelector("[data-patient-id]").value;
    const name = row.querySelector("[data-patient-name]").value;

    if (button.dataset.action === "edit") {
      await editPatient(id);
    } else if (button.dataset.action === "archive") {
      pendingArchiveId = id;
      confirmPatientName.textContent = name;
      openModal(confirmModal);
    } else if (button.dataset.action === "reactivate") {
      await changePatientState(id);
    }
  });
  confirmButton.addEventListener("click", async () => {
    if (!pendingArchiveId) return;
    confirmButton.disabled = true;
    confirmLabel.textContent = "Archiving…";
    await changePatientState(pendingArchiveId);
    pendingArchiveId = null;
    confirmButton.disabled = false;
    confirmLabel.textContent = "Archive Patient";
  });

  document.getElementById("filterSearch").addEventListener("input", renderPatients);
  document.getElementById("showArchived").addEventListener("change", renderPatients);
  document.getElementById("filterClear").addEventListener("click", () => {
    document.getElementById("filterSearch").value = "";
    document.getElementById("showArchived").checked = false;
    renderPatients();
  });

  async function initialize() {
    try {
      const [options, patientResult] = await Promise.all([
        getOperation("options"),
        getOperation("list"),
      ]);
      if (!options.success) throw new Error(options.message || "Could not load patient options.");
      if (!patientResult.success || !Array.isArray(patientResult.data)) {
        throw new Error(patientResult.message || "Could not load patients.");
      }
      renderOptions(options.data);
      patients = patientResult.data;
      renderPatients();
      setActiveTab("info");
      toggleAdmissionFields();
    } catch (error) {
      showAlert(error.response?.data?.message || error.message || "Could not load patient records.");
    }
  }

  initialize();
});
