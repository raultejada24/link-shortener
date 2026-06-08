CREATE TABLE IF NOT EXISTS shortened_urls (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    original_url VARCHAR(2048) NOT NULL,
    short_code CHAR(7) NOT NULL,
    click_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_short_code (short_code),
    INDEX index_original_url (original_url(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
