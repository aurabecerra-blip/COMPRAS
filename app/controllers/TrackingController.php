<?php
class TrackingController
{
    public function __construct(
        private PurchaseRequestRepository $repo,
        private SettingsRepository $settings,
        private Flash $flash
    ) {
    }

    public function show(): void
    {
        $code = trim($_GET['code'] ?? '');
        $pr = $code !== '' ? $this->repo->findByTracking($code) : null;
        include __DIR__ . '/../views/public/track.php';
    }
}
