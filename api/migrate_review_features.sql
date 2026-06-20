-- Run this in phpMyAdmin or MySQL CLI against carousell_db

CREATE TABLE IF NOT EXISTS review_images (
    image_id   INT AUTO_INCREMENT PRIMARY KEY,
    review_id  INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    INDEX (review_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS review_replies (
    reply_id   INT AUTO_INCREMENT PRIMARY KEY,
    review_id  INT NOT NULL,
    user_id    INT NOT NULL,
    body       TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (review_id),
    INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
