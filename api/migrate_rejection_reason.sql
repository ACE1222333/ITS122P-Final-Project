-- Migration: add rejection_reason to orders table
-- Run once against carousell_db in phpMyAdmin or via MySQL CLI

ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS rejection_reason VARCHAR(500) NULL DEFAULT NULL;
