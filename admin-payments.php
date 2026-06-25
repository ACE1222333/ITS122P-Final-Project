<?php
session_start();
include('connection.php');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Payments — BuyTheBella Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="admin-styles.css" />
    <style></style>
  </head>
  <body>
    <div class="shell">
      <main class="main">
        <div class="page-header">
          <div class="page-title">Payments</div>
        </div>

        <div class="order-filters">
          <button class="filter-btn active" onclick="filterPayments('all', this)">All</button>
          <button class="filter-btn" onclick="filterPayments('Pending Verification', this)">Pending</button>
          <button class="filter-btn" onclick="filterPayments('Approved', this)">Approved</button>
          <button class="filter-btn" onclick="filterPayments('Rejected', this)">Rejected</button>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Products</th>
                <th>Amount</th>
                <th>Reference #</th>
                <th>Payment Status</th>
                <th>Date</th>
                <th>Waiting</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="payments-tbody">
              <tr class="empty-row">
                <td colspan="8">Loading payments…</td>
              </tr>
            </tbody>
          </table>
        </div>
      </main>
    </div>

    <script src="admin-data.js"></script>
    <script src="admin-layout.js?v=2"></script>
    <script>
      initAdminLayout("payments");
      checkAdminAuth();

      let currentPaymentFilter = "all";

      fetchPayments(renderPaymentsTable);

      function renderPaymentsTable(list) {
        const tbody = document.getElementById("payments-tbody");
        if (!list || !list.length) {
          tbody.innerHTML = '<tr class="empty-row"><td colspan="8">No payments found.</td></tr>';
          return;
        }
        tbody.innerHTML = list
          .map((p) => {
            const names = (p.products || []).map((x) => x.name).join(", ");
            const short = names.length > 34 ? names.substring(0, 34) + "…" : names;
            const date = (p.dateOrdered || "").split(" ")[0];
            const ref = p.payment.referenceNumber || "—";
            return `<tr>
      <td><strong style="font-family:'DM Sans',sans-serif;">#${p.id}</strong></td>
      <td>${escHtml(p.user.name)}</td>
      <td title="${escHtml(names)}">${escHtml(short)}</td>
      <td>₱${Number(p.totalAmount).toLocaleString()}</td>
      <td class="mono" style="font-size:0.78rem;">${escHtml(ref)}</td>
      <td>${paymentStatusBadge(p.payment.status)}</td>
      <td style="white-space:nowrap;">${date}</td>
      <td style="white-space:nowrap;">${p.payment.status === "Pending Verification" ? paymentAge(p.dateOrdered) : "—"}</td>
      <td><button class="btn-view" onclick="location.href='admin-order-detail.php?id=${escAttr(p.id)}&from=payments'">Review</button></td>
    </tr>`;
          })
          .join("");
      }

      function filterPayments(status, btn) {
        document.querySelectorAll(".filter-btn").forEach((b) => b.classList.remove("active"));
        btn.classList.add("active");
        currentPaymentFilter = status;
        const filtered = status === "all" ? payments : payments.filter((p) => p.payment.status === status);
        renderPaymentsTable(filtered);
      }

      function escHtml(s) {
        return String(s ?? "")
          .replace(/&/g, "&amp;")
          .replace(/</g, "&lt;")
          .replace(/>/g, "&gt;")
          .replace(/"/g, "&quot;");
      }
      function escAttr(s) {
        return String(s ?? "").replace(/'/g, "&#39;");
      }

      function paymentAge(dateOrdered) {
        if (!dateOrdered) return "—";
        const ms = Date.now() - new Date(dateOrdered).getTime();
        const hrs = Math.floor(ms / 3600000);
        if (hrs < 1) return '<span style="color:var(--text-muted);font-size:0.75rem;">Just now</span>';
        if (hrs < 24) return `<span style="color:var(--text-muted);font-size:0.75rem;">${hrs}h ago</span>`;
        const days = Math.floor(hrs / 24);
        const color = days >= 2 ? "var(--red,#dc2626)" : "var(--yellow,#d97706)";
        return `<span style="color:${color};font-weight:600;font-size:0.75rem;">${days}d ago</span>`;
      }
    </script>
  </body>
</html>
