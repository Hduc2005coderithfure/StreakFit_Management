<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\GeminiCoachService;
use DateTimeImmutable;
use DateTimeZone;

final class CoachController
{
    public function ask(Request $request): Response
    {
        $userId = Session::userId();
        if ($userId < 1) return Response::json(['message' => 'Bạn cần đăng nhập.'], 401);
        $db = Database::connection();
        $account = $db->prepare('SELECT current_streak, is_active FROM users WHERE id = ?');
        $account->execute([$userId]);
        $user = $account->fetch();
        if (!$user || !(int)$user['is_active']) return Response::json(['message' => 'Tài khoản không khả dụng.'], 403);
        $submittedToken = $request->input('_csrf', '');
        $token = is_string($submittedToken) ? $submittedToken : '';
        if ($token === '' || empty($_SESSION['sf_csrf']) || !hash_equals((string)$_SESSION['sf_csrf'], $token)) {
            return Response::json(['message' => 'Phiên đã hết hạn. Hãy tải lại trang.'], 419);
        }
        $submittedQuestion = $request->input('question', '');
        if (!is_string($submittedQuestion)) return Response::json(['message' => 'Câu hỏi không hợp lệ.'], 422);
        $question = trim($submittedQuestion);
        if (mb_strlen($question) < 3 || mb_strlen($question) > 300) {
            return Response::json(['message' => 'Câu hỏi cần từ 3 đến 300 ký tự.'], 422);
        }
        $today = (new DateTimeImmutable('now', new DateTimeZone(sf_config()['timezone'])))->format('Y-m-d');
        $usage = $_SESSION['coach_usage'] ?? [];
        if (($usage['date'] ?? '') !== $today) $usage = ['date' => $today, 'count' => 0, 'last' => 0];
        if ((int)$usage['count'] >= 20 || time() - (int)$usage['last'] < 5) {
            return Response::json(['message' => 'Bạn gửi câu hỏi quá nhanh hoặc đã hết 20 lượt hôm nay.'], 429);
        }
        $usage['count']++;
        $usage['last'] = time();
        $_SESSION['coach_usage'] = $usage;

        $week = sf_week_start();
        $goal = $db->prepare('SELECT target_sessions FROM weekly_goals WHERE user_id = ? AND week_start = ?');
        $goal->execute([$userId, $week]);
        $target = (int)($goal->fetchColumn() ?: 3);
        $sessions = $db->prepare("SELECT COUNT(DISTINCT workout_date) FROM workouts WHERE user_id = ? AND week_start = ? AND status = 'valid'");
        $sessions->execute([$userId, $week]);
        $done = (int)$sessions->fetchColumn();
        $effective = sf_member_week_target($db, $userId, $week, $target);
        $progress = [
            'weekly_goal' => $target,
            'effective_goal' => $effective,
            'completed_sessions' => $done,
            'days_left_including_today' => 8 - (int)(new DateTimeImmutable('now', new DateTimeZone(sf_config()['timezone'])))->format('N'),
            'current_week_streak' => (int)$user['current_streak'],
        ];
        $answer = $this->localReply($question, $progress);
        $mode = 'local';
        $config = app_config();
        if ($config['gemini_api_key'] !== '') {
            try {
                $answer = (new GeminiCoachService($config['gemini_api_key'], $config['gemini_model']))->reply($question, $progress);
                $mode = 'gemini';
            } catch (\Throwable $error) {
                error_log('StreakFit coach Gemini unavailable: ' . $error->getMessage());
            }
        }
        return Response::json(['answer' => $answer, 'mode' => $mode]);
    }

    private function localReply(string $question, array $progress): string
    {
        $normalized = mb_strtolower($question, 'UTF-8');
        if (preg_match('/đau|chấn thương|triệu chứng|ốm|mệt bất thường/u', $normalized)) {
            return 'Nếu bạn đang đau, chấn thương hoặc mệt bất thường, hãy nghỉ tập. Nếu triệu chứng kéo dài hoặc nặng, hãy trao đổi với nhân viên y tế.';
        }
        if (str_contains($normalized, 'bmi') || str_contains($normalized, 'cân nặng')) {
            return 'BMI chỉ là một chỉ số tham khảo. Bạn có thể xem số đo ở Hồ sơ sức khỏe; việc chọn bài tập cần xét thêm sức khỏe và khả năng thực tế của bạn.';
        }
        $remaining = max(0, ($progress['effective_goal'] ?: $progress['weekly_goal']) - $progress['completed_sessions']);
        $summary = 'Tuần này bạn đã hoàn thành ' . $progress['completed_sessions'] . '/' . $progress['weekly_goal'] . ' buổi';
        if ($progress['effective_goal'] && $progress['effective_goal'] !== $progress['weekly_goal']) {
            $summary .= ' (mục tiêu tính điểm cho tuần bắt đầu tập: ' . $progress['effective_goal'] . ' buổi)';
        }
        $summary .= ', chuỗi hiện tại ' . $progress['current_week_streak'] . ' tuần. ';
        if ($remaining === 0) return $summary . 'Bạn đã đạt mục tiêu tính điểm; hãy nghỉ ngơi và duy trì nhịp phù hợp.';
        if ($remaining > $progress['days_left_including_today']) return $summary . 'Tuần còn ' . $progress['days_left_including_today'] . ' ngày, không đủ ngày để tập ' . $remaining . ' buổi nếu mỗi ngày một buổi. Hãy chọn lịch thực tế và tránh tập quá sức.';
        return $summary . 'Còn ' . $remaining . ' buổi trong ' . $progress['days_left_including_today'] . ' ngày của tuần. Hãy chọn ngày phù hợp và dành thời gian phục hồi giữa các buổi.';
    }
}
