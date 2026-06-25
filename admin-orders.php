<?php
session_start();
include('connection.php');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Orders — BuyTheBella Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="admin-styles.css" />
    <style></style>
  </head>
  <body>
    <div class="shell">
      <!-- sidebar injected by admin-layout.js -->
      <main class="main">
        <div class="page-header">
          <div class="page-title">Orders</div>
          <div style="font-size: 0.8rem; color: var(--text-muted)">Approved payments only — manage fulfillment here.</div>
        </div>

        <div class="order-filters">
          <button class="filter-btn active" onclick="filterOrders('all', this)">All</button>
          <button class="filter-btn" onclick="filterOrders('Processing', this)">Processing</button>
          <button class="filter-btn" onclick="filterOrders('Shipping', this)">Shipping</button>
          <button class="filter-btn" onclick="filterOrders('Shipped', this)">Shipped</button>
          <button class="filter-btn" onclick="filterOrders('Completed', this)">Completed</button>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Products</th>
                <th>Total</th>
                <th>Order Status</th>
                <th>Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="orders-tbody">
              <tr class="empty-row">
                <td colspan="7">Loading orders…</td>
              </tr>
            </tbody>
          </table>
        </div>
      </main>
    </div>

    <script src="admin-data.js"></script>
    <script src="admin-layout.js?v=2"></script>
    <script>
      initAdminLayout("orders");
      checkAdminAuth();

      let currentOrderFilter = "all";

      fetchOrders(renderOrdersTable);

      function renderOrdersTable(list) {
        const tbody = document.getElementById("orders-tbody");
        if (!list || !list.length) {
          tbody.innerHTML = '<tr class="empty-row"><td colspan="7">No orders found.</td></tr>';
          return;
        }
        tbody.innerHTML = list
          .map((o) => {
            const names = (o.products || []).map((p) => p.name).join(", ");
            const short = names.length > 34 ? names.substring(0, 34) + "…" : names;
            const date = (o.dateOrdered || "").split(" ")[0];
            return `
      <tr>
        <td><strong style="font-family:'DM Sans',sans-serif;">#${o.id}</strong></td>
        <td>${escHtml(o.user.name)}</td>
        <td title="${escHtml(names)}">${escHtml(short)}</td>
        <td>₱${Number(o.totalAmount).toLocaleString()}</td>
        <td>${orderStatusBadge(o.orderStatus)}</td>
        <td style="white-space:nowrap;">${date}</td>
        <td>
          <button class="btn-view" onclick="location.href='admin-order-detail.php?id=${escAttr(o.id)}&from=orders'">
            View Details
          </button>
        </td>
      </tr>`;
          })
          .join("");
      }

      function filterOrders(status, btn) {
        document.querySelectorAll(".filter-btn").forEach((b) => b.classList.remove("active"));
        btn.classList.add("active");
        currentOrderFilter = status;
        const filtered = status === "all" ? orders : orders.filter((o) => o.orderStatus === status);
        renderOrdersTable(filtered);
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
    </script>
  </body>
</html>
