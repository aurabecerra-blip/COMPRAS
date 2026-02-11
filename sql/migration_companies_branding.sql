CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    nit VARCHAR(50) NOT NULL,
    logo_path VARCHAR(255) NOT NULL,
    primary_color VARCHAR(7) NOT NULL DEFAULT '#1E3A8A',
    secondary_color VARCHAR(7) NOT NULL DEFAULT '#F8C8D8',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

INSERT INTO companies (name, nit, logo_path, primary_color, secondary_color, created_at, updated_at)
SELECT
    COALESCE(NULLIF((SELECT value FROM settings WHERE `key` = 'company_name' LIMIT 1), ''), 'AOS'),
    COALESCE(NULLIF((SELECT value FROM settings WHERE `key` = 'company_nit' LIMIT 1), ''), '900.635.119-8'),
    COALESCE(NULLIF((SELECT value FROM settings WHERE `key` = 'brand_logo_path' LIMIT 1), ''), 'assets/logo_aos.png'),
    COALESCE(NULLIF((SELECT value FROM settings WHERE `key` = 'brand_primary_color' LIMIT 1), ''), '#1E3A8A'),
    COALESCE(NULLIF((SELECT value FROM settings WHERE `key` = 'brand_accent_color' LIMIT 1), ''), '#F8C8D8'),
    NOW(),
    NOW()
WHERE NOT EXISTS (SELECT 1 FROM companies);

INSERT INTO settings (`key`, value)
SELECT 'company_nit', '900.635.119-8'
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE `key` = 'company_nit');

INSERT INTO settings (`key`, value)
SELECT 'active_company_id', (SELECT CAST(id AS CHAR) FROM companies ORDER BY id ASC LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE `key` = 'active_company_id');
