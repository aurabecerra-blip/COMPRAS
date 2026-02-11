<?php
class CompanyRepository
{
    public function __construct(private Database $db, private SettingsRepository $settings)
    {
    }

    public function all(): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        return $this->db->pdo()->query('SELECT * FROM companies ORDER BY name ASC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        $stmt = $this->db->pdo()->prepare('SELECT * FROM companies WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function upsert(array $data): int
    {
        if (!$this->tableExists()) {
            return 0;
        }

        $payload = [
            'name' => trim((string)($data['name'] ?? '')),
            'nit' => trim((string)($data['nit'] ?? '')),
            'logo_path' => trim((string)($data['logo_path'] ?? '')),
            'primary_color' => $this->normalizeColor((string)($data['primary_color'] ?? '')),
            'secondary_color' => $this->normalizeColor((string)($data['secondary_color'] ?? '')),
        ];

        if ($payload['name'] === '') {
            $payload['name'] = 'AOS';
        }
        if ($payload['nit'] === '') {
            $payload['nit'] = '900.635.119-8';
        }
        if ($payload['logo_path'] === '') {
            $payload['logo_path'] = 'assets/logo_aos.png';
        }
        if ($payload['primary_color'] === '') {
            $payload['primary_color'] = '#1E3A8A';
        }
        if ($payload['secondary_color'] === '') {
            $payload['secondary_color'] = '#F8C8D8';
        }

        $id = (int)($data['id'] ?? 0);
        if ($id > 0) {
            $stmt = $this->db->pdo()->prepare('UPDATE companies SET name = ?, nit = ?, logo_path = ?, primary_color = ?, secondary_color = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$payload['name'], $payload['nit'], $payload['logo_path'], $payload['primary_color'], $payload['secondary_color'], $id]);
            return $id;
        }

        $stmt = $this->db->pdo()->prepare('INSERT INTO companies (name, nit, logo_path, primary_color, secondary_color, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$payload['name'], $payload['nit'], $payload['logo_path'], $payload['primary_color'], $payload['secondary_color']]);
        return (int)$this->db->pdo()->lastInsertId();
    }

    public function active(): array
    {
        if (!$this->tableExists()) {
            return $this->fallbackFromSettings();
        }

        $this->ensureDefaultCompany();

        $activeId = (int)$this->settings->get('active_company_id', '0');
        if ($activeId > 0) {
            $company = $this->find($activeId);
            if ($company) {
                return $company;
            }
        }

        $row = $this->db->pdo()->query('SELECT * FROM companies ORDER BY id ASC LIMIT 1')->fetch();
        if (!$row) {
            return $this->fallbackFromSettings();
        }

        $this->settings->set('active_company_id', (string)$row['id']);
        return $row;
    }

    public function setActive(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $company = $this->find($id);
        if (!$company) {
            return false;
        }

        $this->settings->set('active_company_id', (string)$id);
        return true;
    }

    private function ensureDefaultCompany(): void
    {
        $count = (int)$this->db->pdo()->query('SELECT COUNT(*) AS total FROM companies')->fetch()['total'];
        if ($count > 0) {
            return;
        }

        $this->upsert($this->fallbackFromSettings());
    }

    private function fallbackFromSettings(): array
    {
        return [
            'id' => 0,
            'name' => trim((string)$this->settings->get('company_name', 'AOS')) ?: 'AOS',
            'nit' => trim((string)$this->settings->get('company_nit', '900.635.119-8')) ?: '900.635.119-8',
            'logo_path' => trim((string)$this->settings->get('brand_logo_path', 'assets/logo_aos.png')) ?: 'assets/logo_aos.png',
            'primary_color' => $this->normalizeColor((string)$this->settings->get('brand_primary_color', '#1E3A8A')) ?: '#1E3A8A',
            'secondary_color' => $this->normalizeColor((string)$this->settings->get('brand_accent_color', '#F8C8D8')) ?: '#F8C8D8',
        ];
    }

    private function normalizeColor(string $value): string
    {
        $value = strtoupper(trim($value));
        if (preg_match('/^#[0-9A-F]{6}$/', $value) === 1) {
            return $value;
        }
        return '';
    }

    private function tableExists(): bool
    {
        try {
            $this->db->pdo()->query('SELECT 1 FROM companies LIMIT 1');
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
