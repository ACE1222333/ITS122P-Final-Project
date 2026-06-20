-- Migration: create receipts table
-- Run once after approving the first payment.
-- receipt_number format: RCP-YYYYMMDD-XXXXX (zero-padded order_id)

CREATE TABLE IF NOT EXISTS receipts (
    receipt_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    receipt_number VARCHAR(30)  NOT NULL,
    order_id       INT UNSIGNED NOT NULL,
    generated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (receipt_id),
    UNIQUE KEY uq_receipt_number (receipt_number),
    UNIQUE KEY uq_receipt_order  (order_id),
    CONSTRAINT fk_receipts_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
