-- ════════════════════════════════════════════════════════════════
-- Run this in phpMyAdmin → carousell_db → SQL tab
-- Adds 'reserved' product status and reservation expiry to orders
-- ════════════════════════════════════════════════════════════════

-- 1. Add 'reserved' to products.status ENUM
ALTER TABLE products
  MODIFY COLUMN status ENUM('available','reserved','sold') NOT NULL DEFAULT 'available';

-- 2. Add reservation expiry column to orders
ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS reservation_expires_at DATETIME NULL AFTER date_ordered;

-- 3. Add 'Rejected' and 'Cancelled' to orders.status ENUM
ALTER TABLE orders
  MODIFY COLUMN status ENUM(
    'Pending Payment',
    'Pending Verification',
    'Processing',
    'Shipping',
    'Shipped',
    'Completed',
    'Rejected',
    'Cancelled'
  ) NOT NULL DEFAULT 'Pending Payment';
