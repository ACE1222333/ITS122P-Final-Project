<?php
session_start();
include('connection.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order History — ByTheBel</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="shop-styles.css">
</head>
<body>

<!-- Nav, cart, auth, overlays injected by shop-layout.js -->

<div class="orders-page">
  <div class="orders-inner" style="max-width:820px;">

    <div class="orders-page-title">Order History</div>
    <div class="orders-page-sub">Your past and completed transactions.</div>

    <!-- Tab navigation between My Orders and Order History -->
    <div class="orders-tabs">
      <a class="orders-tab" href="shop-my-orders.php">My Orders</a>
      <a class="orders-tab active" href="shop-order-history.php">Order History</a>
    </div>

    <!-- Order list -->
    <div id="history-list">
      <div class="orders-empty">
        <div class="orders-empty-icon"></div>
        <div style="font-size:0.84rem;color:var(--text-muted);">Loading order history…</div>
      </div>
    </div>

  </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="shop-shared.js?v=2"></script>
<script src="shop-layout.js"></script>
<script>
initShopLayout('order-history');

/* Redirect to shop if not logged in */
if (!currentUser) {
  showToast('Please log in to view your order history.');
  setTimeout(() => { window.location.href = 'shop.php'; }, 1000);
}

async function renderOrderHistory() {
  const listEl = document.getElementById('history-list');
  if (!currentUser) return;

  try {
    const allOrders = await loadMyOrders();
    const history = allOrders.filter(o => FINAL_ORDER_STATUSES.includes(o.orderStatus));

    if (!history.length) {
      listEl.innerHTML = `
        <div class="orders-empty">
          <div class="orders-empty-icon"></div>
          <div class="orders-empty-title">No Order History Yet</div>
          <div class="orders-empty-sub">
            Completed and rejected orders will appear here.<br>
            Active orders can be tracked under <a href="shop-my-orders.php" style="color:var(--accent);">My Orders</a>.
          </div>
          <a href="shop-products.php" class="btn-primary" style="display:inline-block;">Start Shopping</a>
        </div>`;
      return;
    }

    listEl.innerHTML = history.map(renderHistoryCard).join('');
  } catch(err) {
    listEl.innerHTML = `
      <div class="orders-empty">
        <div class="orders-empty-icon" style="font-size:2rem;"></div>
        <div class="orders-empty-title">Could not load order history</div>
        <div class="orders-empty-sub">${err.message || 'A server error occurred.'}</div>
        <button class="btn-primary" style="margin-top:1rem;" onclick="renderOrderHistory()">Try Again</button>
      </div>`;
  }
}

renderOrderHistory();
</script>
</body>
</html>
