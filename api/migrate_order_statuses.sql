-- Migration: expand orders.status ENUM to include all statuses used by the app.
-- The old ENUM only had 5 values; MySQL silently coerced unknown values to ''
-- which made every new order invisible to users.
-- Run once against carousell_db.

ALTER TABLE orders
  MODIFY COLUMN status ENUM(
    'Payment Verification',
    'Payment Accepted',
    'Payment Rejected',
    'Processing',
    'Shipping',
    'Shipped',
    'Completed',
    'Cancelled',
    'Pending Payment',
    'Pending Verification',
    'Rejected'
  ) NOT NULL DEFAULT 'Payment Verification';

-- Repair orders whose status was silently coerced to '' by the old ENUM.
-- These are orders that were submitted after the code was updated but before
-- this migration ran — they should be in Payment Verification state.
UPDATE orders SET status = 'Payment Verification' WHERE status = '';

-- Legacy orders stored as 'Pending Payment' or 'Pending Verification' are fine —
-- the frontend already lists them under ACTIVE_ORDER_STATUSES.
