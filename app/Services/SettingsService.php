<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

final class SettingsService
{
    private const DEFAULTS = [
        'minimum_workout_seconds' => 1800,
        'checkout_timeout_hours' => 12,
        'monthly_contribution' => 20000,
        'session_points' => 40,
        'miss_penalty' => 25,
        'extra_session_points' => 20,
        'streak_weekly_bonus' => 5,
        'streak_bonus_cap_weeks' => 6,
        'max_extra_sessions' => 2,
        'appeal_monthly_limit' => 2,
        'appeal_minimum_votes' => 3,
    ];

    public function __construct(private readonly PDO $db) {}

    public function all(): array
    {
        $settings = self::DEFAULTS;
        try {
            foreach ($this->db->query('SELECT setting_key, setting_value FROM system_settings')->fetchAll() as $row) {
                if (array_key_exists($row['setting_key'], $settings) && ctype_digit((string)$row['setting_value'])) {
                    $settings[$row['setting_key']] = (int)$row['setting_value'];
                }
            }
        } catch (\PDOException) {
            // During initial schema setup the defaults keep the application usable.
        }
        return $settings;
    }

    public function save(array $values, int $adminId): void
    {
        $ranges = [
            'minimum_workout_seconds' => [600, 14400],
            'checkout_timeout_hours' => [1, 72],
            'monthly_contribution' => [0, 10000000],
            'session_points' => [0, 1000],
            'miss_penalty' => [0, 1000],
            'extra_session_points' => [0, 1000],
            'streak_weekly_bonus' => [0, 1000],
            'streak_bonus_cap_weeks' => [1, 52],
            'max_extra_sessions' => [0, 7],
            'appeal_monthly_limit' => [1, 20],
            'appeal_minimum_votes' => [1, 100],
        ];
        $validated = [];
        foreach ($ranges as $key => [$min, $max]) {
            $value = filter_var($values[$key] ?? null, FILTER_VALIDATE_INT);
            if ($value === false || $value < $min || $value > $max) {
                throw new \DomainException('Giá trị cấu hình ' . $key . ' nằm ngoài giới hạn cho phép.');
            }
            $validated[$key] = $value;
        }
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('INSERT INTO system_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)');
            foreach ($validated as $key => $value) $stmt->execute([$key, (string)$value, $adminId]);
            (new AuditLogService($this->db))->record($adminId, 'settings_updated', 'system_settings', null, $validated);
            $this->db->commit();
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }
}
