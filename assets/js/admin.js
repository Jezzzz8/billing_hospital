import { adminRpc } from "./master/admin-rpc.js";

function escapeHtml(value) {
  return String(value ?? "").replace(/[&<>"']/g, (character) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;",
  })[character]);
}

function valueAtPath(value, path) {
  return path.split(".").reduce((current, key) => current?.[key], value);
}

async function refreshDashboard() {
  const targets = document.querySelectorAll("[data-dashboard-value], [data-dashboard-currency]");
  if (!targets.length) return;
  try {
    const result = await adminRpc("dashboard", "summary");
    if (!result.success || !result.data) return;

    document.querySelectorAll("[data-dashboard-value]").forEach((element) => {
      const value = valueAtPath(result.data, element.dataset.dashboardValue);
      if (value !== undefined && value !== null) element.textContent = value;
    });
    document.querySelectorAll("[data-dashboard-currency]").forEach((element) => {
      const value = valueAtPath(result.data, element.dataset.dashboardCurrency);
      if (value !== undefined && value !== null) {
        element.textContent = `₱${Number(value).toLocaleString(undefined, {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        })}`;
      }
    });
    const recentBody = document.getElementById("recentStatementsBody");
    if (recentBody) {
      const statements = result.data.recent_statements || [];
      recentBody.innerHTML = statements.length ? statements.map((statement) => `<tr class="hover:bg-slate-50">
        <td class="px-6 py-3"><p class="font-medium text-slate-900">#${Number(statement.statement_id)}</p><p class="text-xs text-slate-500">${escapeHtml(statement.statement_date)}</p></td>
        <td class="px-6 py-3"><p class="font-medium text-slate-900">${escapeHtml(`${statement.first_name} ${statement.last_name}`)}</p><p class="text-xs text-slate-500">Admission #${Number(statement.admission_id)}</p></td>
        <td class="px-6 py-3 text-right font-medium text-slate-900 whitespace-nowrap">₱${Number(statement.total_amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
        <td class="px-6 py-3 text-right text-emerald-700 whitespace-nowrap">₱${Number(statement.amount_paid || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
        <td class="px-6 py-3 text-right font-semibold whitespace-nowrap ${Number(statement.balance_amount) > 0 ? "text-rose-700" : "text-slate-400"}">₱${Number(statement.balance_amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
        <td class="px-6 py-3"><span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium">${escapeHtml(statement.status_name)}</span></td>
      </tr>`).join("") : '<tr><td colspan="6" class="px-6 py-8 text-center text-slate-400">No statements yet.</td></tr>';
    }
  } catch (error) {
    console.warn("Dashboard data refresh failed; keeping server-rendered values.", error);
  }
}

async function refreshReports() {
  const targets = document.querySelectorAll("[data-report-value], [data-report-currency]");
  if (!targets.length) return;
  try {
    const response = await adminRpc("reports", "summary");
    const totals = response.data?.hospital_totals;
    if (!totals) return;
    document.querySelectorAll("[data-report-value]").forEach((element) => {
      const value = totals[element.dataset.reportValue];
      if (value !== undefined) element.textContent = value;
    });
    document.querySelectorAll("[data-report-currency]").forEach((element) => {
      const value = totals[element.dataset.reportCurrency];
      if (value !== undefined) element.textContent = `₱${Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    });
  } catch (error) {
    console.warn("Report data refresh failed.", error);
  }
}

document.addEventListener("spa:page-loaded", event => {
  if (event.detail.page === "dashboard") refreshDashboard();
  else if (event.detail.page === "reports") refreshReports();
});