
document.addEventListener("spa:page-loaded", async (event) => {
  if (event.detail.page !== "billing") return;
  const baseUrl = document.body.dataset.baseUrl || "";
  const alertBox = document.getElementById("alert");

  
  const statementModal = document.getElementById("statementModal");
  const statementForm = document.getElementById("statementForm");
  const statementModalTitle = document.getElementById("statementModalTitle");
  const statementSubmitBtn = document.getElementById("statementSubmitBtn");
  const statementSubmitLbl = document.getElementById("statementSubmitLabel");

  
  const manageModal = document.getElementById("manageModal");
  const manageTitle = document.getElementById("manageTitle");
  const manageSubtitle = document.getElementById("manageSubtitle");
  const chargesBody = document.getElementById("chargesBody");
  const paymentsBody = document.getElementById("paymentsBody");
  const noCharges = document.getElementById("noCharges");
  const noPayments = document.getElementById("noPayments");
  const sumSubtotal = document.getElementById("sumSubtotal");
  const sumTax = document.getElementById("sumTax");
  const sumTotal = document.getElementById("sumTotal");
  const sumBalance = document.getElementById("sumBalance");

  
  const chargeModal = document.getElementById("chargeModal");
  const chargeForm = document.getElementById("chargeForm");
  const saveChargeBtn = document.getElementById("saveChargeBtn");
  const saveChargeLbl = document.getElementById("saveChargeLabel");

  
  const chargeSummary = document.getElementById("chargeSummary");
  const summaryUnitPrice = document.getElementById("summaryUnitPrice");
  const summaryQty = document.getElementById("summaryQty");
  const summaryTaxRow = document.getElementById("summaryTaxRow");
  const summaryTax = document.getElementById("summaryTax");
  const summaryLineTotal = document.getElementById("summaryLineTotal");
  const chargeItemEl = document.getElementById("charge_item_id");
  const chargeQuantityEl = document.getElementById("charge_quantity");
  const chargePriceEl = document.getElementById("charge_price");

  
  const paymentModal = document.getElementById("paymentModal");
  const paymentForm = document.getElementById("paymentForm");
  const savePaymentBtn = document.getElementById("savePaymentBtn");
  const savePaymentLbl = document.getElementById("savePaymentLabel");

  const TAX_RATE = 0.12;

  
  const holder = document.getElementById("billingData");
  let CHARGE_ITEMS = [];
  let PAYMENT_TYPES = [];

  if (holder) {
    try {
      CHARGE_ITEMS = JSON.parse(holder.dataset.chargeItems || "[]");
    } catch (e) {
      console.error(e);
    }
    try {
      PAYMENT_TYPES = JSON.parse(holder.dataset.paymentTypes || "[]");
    } catch (e) {
      console.error(e);
    }
  }

  if (chargeItemEl) {
    CHARGE_ITEMS.forEach((c) => {
      const opt = document.createElement("option");
      opt.value = c.charge_item_id;
      opt.textContent = `[${c.category}] ${c.item_name} (${c.item_code})`;
      opt.dataset.price = c.default_price;
      opt.dataset.taxable = c.is_taxable;
      opt.dataset.unit = c.unit;
      chargeItemEl.appendChild(opt);
    });
  }

  const paymentTypeSelect = document.getElementById("payment_type_id");
  if (paymentTypeSelect) {
    PAYMENT_TYPES.forEach((p) => {
      const opt = document.createElement("option");
      opt.value = p.payment_type_id;
      opt.textContent = p.type_name;
      paymentTypeSelect.appendChild(opt);
    });
  }

  
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

  const fmt = (n) =>
    "₱" +
    (parseFloat(n) || 0).toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });

  const parseNum = (str) => {
    if (!str) return 0;
    return parseFloat(str.replace(/[^0-9.\-]/g, "")) || 0;
  };

  
  document
    .querySelectorAll("[data-close-statement]")
    .forEach((el) =>
      el.addEventListener("click", () => closeModal(statementModal)),
    );
  document.querySelectorAll("[data-close-manage]").forEach((el) =>
    el.addEventListener("click", () => {
      closeModal(manageModal);
      if (currentStatementId) {
        loadStatement(currentStatementId).catch(() => {});
      }
    }),
  );
  document
    .querySelectorAll("[data-close-charge]")
    .forEach((el) =>
      el.addEventListener("click", () => closeModal(chargeModal)),
    );
  document
    .querySelectorAll("[data-close-payment]")
    .forEach((el) =>
      el.addEventListener("click", () => closeModal(paymentModal)),
    );
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeModal(statementModal);
      closeModal(manageModal);
      closeModal(chargeModal);
      closeModal(paymentModal);
    }
  }, { signal: event.detail.signal });

  
  document.querySelectorAll(".edit-btn").forEach((btn) => {
    btn.addEventListener("click", async () => {
      const id = btn.dataset.statementId;
      statementForm.reset();
      clearErrors(statementForm);
      statementModalTitle.textContent = "Edit Statement";
      statementSubmitLbl.textContent = "Save Changes";
      document.getElementById("statement_id").value = id;
      openModal(statementModal);

      try {
        const { data } = await axios.get(
          `${baseUrl}/api/admin.php?resource=billing&operation=details&id=${id}`,
          { withCredentials: true },
        );
        if (!data.success) return;

        const s = data.data;
        document.getElementById("due_date").value = s.due_date || "";
        document.getElementById("insurance_coverage_amount").value =
          s.insurance_coverage_amount || "";
        document.getElementById("philhealth_deduction").value =
          s.philhealth_deduction || "";
        document.getElementById("hmo_discount").value =
          s.hmo_discount || "";
        document.getElementById("government_discount").value =
          s.government_discount || "";
        document.getElementById("notes").value = s.notes || "";
      } catch (err) {
        console.error(err);
      }
    });
  });

  
  
  
  statementForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    hideAlert();
    clearErrors(statementForm);

    const id = document.getElementById("statement_id").value;
    const payload = {
      due_date: document.getElementById("due_date").value,
      insurance_coverage_amount:
        document.getElementById("insurance_coverage_amount").value || 0,
      philhealth_deduction:
        document.getElementById("philhealth_deduction").value || 0,
      hmo_discount:
        document.getElementById("hmo_discount").value || 0,
      government_discount:
        document.getElementById("government_discount").value || 0,
      notes: document.getElementById("notes").value.trim(),
    };

    const url = `${baseUrl}/api/admin.php?resource=billing&operation=update&id=${id}`;

    statementSubmitBtn.disabled = true;
    statementSubmitLbl.textContent = "Saving…";

    try {
      const { data } = await axios.post(url, payload, {
        headers: { "Content-Type": "application/json" },
        withCredentials: true,
      });

      if (data.success) {
        closeModal(statementModal);
        sessionStorage.setItem("billing_flash", data.message || "Saved.");
        await window.refreshAdminList();
      } else {
        if (data.errors)
          Object.entries(data.errors).forEach(([f, m]) =>
            setError(statementForm, f, m),
          );
        showAlert(data.message || "Save failed.", "error");
      }
    } catch (err) {
      const res = err.response?.data;
      if (res?.errors)
        Object.entries(res.errors).forEach(([f, m]) =>
          setError(statementForm, f, m),
        );
      showAlert(res?.message || "Save failed.", "error");
    } finally {
      statementSubmitBtn.disabled = false;
      statementSubmitLbl.textContent = "Save Changes";
    }
  });

  let currentStatementId = null;
  let currentStatement = null;

  document.querySelectorAll(".manage-tab-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      document.querySelectorAll(".manage-tab-btn").forEach((b) => {
        b.className =
          "manage-tab-btn px-4 py-3 text-sm font-medium border-b-2 whitespace-nowrap " +
          (b === btn
            ? "border-blue-600 text-blue-600"
            : "border-transparent text-slate-500 hover:text-slate-700");
      });
      document.querySelectorAll(".manage-tab-panel").forEach((p) => {
        p.classList.toggle("hidden", p.dataset.mpanel !== btn.dataset.mtab);
      });
    });
  });

  document.querySelectorAll(".manage-btn").forEach((btn) => {
    btn.addEventListener("click", async () => {
      const id = btn.dataset.statementId;
      currentStatementId = id;
      openModal(manageModal);
      await loadStatement(id);
    });
  });

  async function loadStatement(id) {
    try {
      const { data } = await axios.get(
        `${baseUrl}/api/admin.php?resource=billing&operation=details&id=${id}`,
        { withCredentials: true },
      );
      if (!data.success) {
        showAlert(data.message || "Could not load statement.", "error");
        return;
      }
      currentStatement = data.data;
      renderStatement(currentStatement);
      patchOuterRow(currentStatement);
    } catch (err) {
      console.error(err);
      showAlert("Could not load statement.", "error");
    }
  }

  window.openAdminStatement = async (id) => {
    currentStatementId = id;
    openModal(manageModal);
    await loadStatement(id);
  };

  function renderStatement(s) {
    manageTitle.textContent = `Statement #${s.statement_id}`;
    manageSubtitle.textContent = `${s.first_name} ${s.last_name} · Admission #${s.admission_id} · ${s.status_name}`;

    sumSubtotal.textContent = fmt(s.subtotal_amount);
    sumTax.textContent = fmt(s.tax_amount);
    sumTotal.textContent = fmt(s.total_amount);
    sumBalance.textContent = fmt(s.balance_amount);

    chargesBody.innerHTML = "";
    if (!s.charges || !s.charges.length) {
      noCharges.classList.remove("hidden");
    } else {
      noCharges.classList.add("hidden");
      s.charges.forEach((c) => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
                    <td class="px-4 py-2.5">
                        <p class="font-medium text-slate-900">${c.item_name}</p>
                        <p class="text-xs text-slate-500 font-mono">${c.item_code}</p>
                    </td>
                    <td class="px-4 py-2.5 text-center text-slate-700">${c.quantity}</td>
                    <td class="px-4 py-2.5 text-right text-slate-700">${fmt(c.actual_price)}</td>
                    <td class="px-4 py-2.5 text-right font-medium text-slate-900">${fmt(c.line_total)}</td>
                    <td class="px-4 py-2.5 text-right">
                        ${c.source_type === "MANUAL" ? `<button type="button" class="remove-charge-btn p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg" data-charge-id="${c.charge_id}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>` : '<span class="text-xs text-slate-400" title="System-generated charge">Locked</span>'}
                    </td>
                `;
        chargesBody.appendChild(tr);
      });
    }

    paymentsBody.innerHTML = "";
    if (!s.payments || !s.payments.length) {
      noPayments.classList.remove("hidden");
    } else {
      noPayments.classList.add("hidden");
      s.payments.forEach((p) => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
                    <td class="px-4 py-2.5 font-medium text-slate-900">${p.type_name}</td>
                    <td class="px-4 py-2.5 text-slate-600 font-mono text-xs">${p.transaction_reference || "—"}</td>
                    <td class="px-4 py-2.5 text-slate-600 text-xs">${p.payment_datetime}</td>
                    <td class="px-4 py-2.5 text-right font-medium text-emerald-700">${fmt(p.amount)}</td>
                    <td class="px-4 py-2.5 text-right">
                        <button type="button" class="remove-payment-btn p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg" data-payment-id="${p.payment_id}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </td>
                `;
        paymentsBody.appendChild(tr);
      });
    }

    chargesBody.querySelectorAll(".remove-charge-btn").forEach((b) => {
      b.addEventListener("click", () =>
        removeCharge(s.statement_id, b.dataset.chargeId),
      );
    });
    paymentsBody.querySelectorAll(".remove-payment-btn").forEach((b) => {
      b.addEventListener("click", () =>
        removePayment(s.statement_id, b.dataset.paymentId),
      );
    });
  }

  function patchOuterRow(s) {
    const row = document.querySelector(
      `.statement-row[data-statement-id="${s.statement_id}"]`,
    );
    if (!row) return;

    row.dataset.status = s.status_name || "";
    row.dataset.search = (
      s.first_name +
      " " +
      s.last_name +
      " #" +
      s.statement_id +
      " " +
      (s.status_name || "")
    ).toLowerCase();

    const tds = row.querySelectorAll("td");
    if (tds.length >= 6) {
      tds[2].textContent = fmt(s.total_amount);
      tds[3].textContent = fmt(s.amount_paid);

      tds[4].textContent = fmt(s.balance_amount);
      tds[4].classList.toggle(
        "text-rose-700",
        parseFloat(s.balance_amount) > 0,
      );
      tds[4].classList.toggle(
        "text-slate-400",
        parseFloat(s.balance_amount) <= 0,
      );

      const color = s.color_code || "#6b7280";
      tds[5].innerHTML = `
                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium"
                      style="background-color: ${color}1A; color: ${color}; border-color: ${color}40;">
                    <span class="w-1.5 h-1.5 rounded-full" style="background-color: ${color}"></span>
                    ${s.status_name}
                </span>
            `;
    }

    refreshStatCards();
    applyFilters();
  }

  function refreshStatCards() {
    let billed = 0,
      paid = 0,
      balance = 0;

    document.querySelectorAll(".statement-row").forEach((r) => {
      const tds = r.querySelectorAll("td");
      if (tds.length < 6) return;
      billed += parseNum(tds[2].textContent);
      paid += parseNum(tds[3].textContent);
      balance += parseNum(tds[4].textContent);
    });

    const statGrid = document.querySelectorAll(
      "main .grid.grid-cols-1.sm\\:grid-cols-4 > div",
    );
    if (statGrid.length >= 4) {
      statGrid[1].querySelector("p:last-child").textContent = fmt(billed);
      statGrid[2].querySelector("p:last-child").textContent = fmt(paid);
      statGrid[3].querySelector("p:last-child").textContent = fmt(balance);
    }
  }

  async function removeCharge(statementId, chargeId) {
    if (!confirm("Remove this charge?")) return;
    try {
      const { data } = await axios.post(
        `${baseUrl}/api/admin.php?resource=billing&operation=remove-charge&id=${statementId}&charge_id=${chargeId}`,
        {},
        {
          headers: { "Content-Type": "application/json" },
          withCredentials: true,
        },
      );
      if (data.success) {
        await loadStatement(statementId);
        sessionStorage.setItem("billing_flash", "Charge removed.");
      } else {
        showAlert(data.message || "Could not remove charge.", "error");
      }
    } catch (err) {
      showAlert(
        err.response?.data?.message || "Could not remove charge.",
        "error",
      );
    }
  }

  async function removePayment(statementId, paymentId) {
    if (!confirm("Remove this payment?")) return;
    try {
      const { data } = await axios.post(
        `${baseUrl}/api/admin.php?resource=billing&operation=remove-payment&id=${statementId}&payment_id=${paymentId}`,
        {},
        {
          headers: { "Content-Type": "application/json" },
          withCredentials: true,
        },
      );
      if (data.success) {
        await loadStatement(statementId);
        sessionStorage.setItem("billing_flash", "Payment removed.");
      } else {
        showAlert(data.message || "Could not remove payment.", "error");
      }
    } catch (err) {
      showAlert(
        err.response?.data?.message || "Could not remove payment.",
        "error",
      );
    }
  }

  
  
  

  
  function refreshChargeSummary() {
    const opt = chargeItemEl.selectedOptions[0];
    const hasItem = !!chargeItemEl.value;

    if (!hasItem) {
      chargeSummary.classList.add("hidden");
      chargePriceEl.value = "";
      return;
    }

    const unitPrice = parseFloat(opt?.dataset.price) || 0;
    const qty = Math.max(1, parseInt(chargeQuantityEl.value, 10) || 1);
    const isTaxable = opt && opt.dataset.taxable === "1";

    const lineBase = unitPrice * qty;
    const taxAmt = isTaxable ? lineBase * TAX_RATE : 0;
    const lineTotal = lineBase + taxAmt;

    
    chargePriceEl.value = lineBase.toFixed(2);

    
    summaryUnitPrice.textContent = fmt(unitPrice);
    summaryQty.textContent = qty;

    if (isTaxable) {
      summaryTaxRow.classList.remove("hidden");
      summaryTaxRow.classList.add("flex");
      summaryTax.textContent = fmt(taxAmt);
    } else {
      summaryTaxRow.classList.add("hidden");
      summaryTaxRow.classList.remove("flex");
    }

    summaryLineTotal.textContent = fmt(lineTotal);
    chargeSummary.classList.remove("hidden");
  }

  
  document.getElementById("addChargeBtn").addEventListener("click", () => {
    chargeForm.reset();
    chargeItemEl.value = "";
    chargeQuantityEl.value = 1;
    chargePriceEl.value = "";
    document.getElementById("charge_notes").value = "";
    chargeSummary.classList.add("hidden");
    openModal(chargeModal);
  });

  
  chargeItemEl.addEventListener("change", refreshChargeSummary);

  
  chargeQuantityEl.addEventListener("input", refreshChargeSummary);
  chargeQuantityEl.addEventListener("change", refreshChargeSummary);

  if (saveChargeBtn) {
    saveChargeBtn.addEventListener("click", async () => {
      const itemId = chargeItemEl.value;
      const quantity = chargeQuantityEl.value;
      const notes = document.getElementById("charge_notes").value;

      if (!itemId) {
        showAlert("Please select a charge item.", "error");
        return;
      }
      if (!quantity || parseInt(quantity, 10) < 1) {
        showAlert("Please enter a valid quantity (1 or more).", "error");
        return;
      }

      
      const opt = chargeItemEl.selectedOptions[0];
      const unitPrice = parseFloat(opt?.dataset.price) || 0;

      saveChargeBtn.disabled = true;
      saveChargeLbl.textContent = "Adding…";

      try {
        const { data } = await axios.post(
          `${baseUrl}/api/admin.php?resource=billing&operation=add-charge&id=${currentStatementId}`,
          { charge_item_id: itemId, quantity, actual_price: unitPrice, notes },
          {
            headers: { "Content-Type": "application/json" },
            withCredentials: true,
          },
        );
        if (data.success) {
          closeModal(chargeModal);
          await loadStatement(currentStatementId);
          sessionStorage.setItem("billing_flash", "Charge added.");
        } else {
          showAlert(data.message || "Could not add charge.", "error");
        }
      } catch (err) {
        showAlert(
          err.response?.data?.message || "Could not add charge.",
          "error",
        );
      } finally {
        saveChargeBtn.disabled = false;
        saveChargeLbl.textContent = "Add Charge";
      }
    });
  }

  
  
  
  document.getElementById("addPaymentBtn").addEventListener("click", () => {
    paymentForm.reset();
    document.getElementById("payment_type_id").value = "";
    document.getElementById("payment_amount").value = "";
    document.getElementById("payment_notes").value = "";
    openModal(paymentModal);
  });

  if (savePaymentBtn) {
    savePaymentBtn.addEventListener("click", async () => {
      const typeId = document.getElementById("payment_type_id").value;
      const amount = document.getElementById("payment_amount").value;
      const notes = document.getElementById("payment_notes").value;

      if (!typeId || !amount) {
        showAlert("Please select a payment type and enter an amount.", "error");
        return;
      }

      savePaymentBtn.disabled = true;
      savePaymentLbl.textContent = "Recording…";

      try {
        const { data } = await axios.post(
          `${baseUrl}/api/admin.php?resource=billing&operation=add-payment&id=${currentStatementId}`,
          {
              payment_type_id: typeId,
              amount,
              notes,
          },
          {
            headers: { "Content-Type": "application/json" },
            withCredentials: true,
          },
        );
        if (data.success) {
          closeModal(paymentModal);
          await loadStatement(currentStatementId);
          sessionStorage.setItem("billing_flash", "Payment recorded.");
        } else {
          showAlert(data.message || "Could not record payment.", "error");
        }
      } catch (err) {
        showAlert(
          err.response?.data?.message || "Could not record payment.",
          "error",
        );
      } finally {
        savePaymentBtn.disabled = false;
        savePaymentLbl.textContent = "Record Payment";
      }
    });
  }

  
  
  
  const searchInput = document.getElementById("filterSearch");
  const statusFilter = document.getElementById("filterStatus");
  const filterClear = document.getElementById("filterClear");
  const filterSummary = document.getElementById("filterSummary");
  const filteredCount = document.getElementById("filteredCount");
  const totalCount = document.getElementById("totalCount");
  const emptyState = document.getElementById("emptyState");

  const rows = Array.from(document.querySelectorAll(".statement-row"));
  if (totalCount) totalCount.textContent = rows.length;

  function applyFilters() {
    const q = (searchInput?.value || "").toLowerCase().trim();
    const status = statusFilter?.value || "";

    let visible = 0;
    rows.forEach((row) => {
      const matchesSearch = q === "" || (row.dataset.search || "").includes(q);
      const matchesStatus = status === "" || row.dataset.status === status;
      const show = matchesSearch && matchesStatus;
      row.classList.toggle("hidden", !show);
      if (show) visible++;
    });

    if (filteredCount) filteredCount.textContent = visible;
    const isFiltering = q !== "" || status !== "";
    if (filterSummary) filterSummary.classList.toggle("hidden", !isFiltering);
    if (emptyState) emptyState.classList.toggle("hidden", visible > 0);
    if (filterClear) {
      filterClear.classList.toggle("hidden", !isFiltering);
      filterClear.classList.toggle("inline-flex", isFiltering);
    }
  }

  if (searchInput) searchInput.addEventListener("input", applyFilters);
  if (statusFilter) statusFilter.addEventListener("change", applyFilters);
  if (filterClear) {
    filterClear.addEventListener("click", () => {
      searchInput.value = "";
      statusFilter.value = "";
      applyFilters();
    });
  }
  applyFilters();

  
  
  
  const flash = sessionStorage.getItem("billing_flash");
  if (flash) {
    sessionStorage.removeItem("billing_flash");
    showAlert(flash, "success");
  }
});
