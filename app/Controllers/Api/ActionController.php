<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\CourtAppealService;
use DomainException;
use PDO;

final class ActionController
{
    public function __construct(private readonly PDO $db) {}
    public function index(Request $request): Response
    {
        $userId = Session::userId();
        $token = (string)$request->input('_csrf', '');
        $sessionToken = (string)($_SESSION['sf_csrf'] ?? '');
        if ($userId > 0) {
            $account = $this->db->prepare('SELECT is_active FROM users WHERE id = ?');
            $account->execute([$userId]);
            if (!(int)$account->fetchColumn()) return Response::json(['ok' => false, 'message' => 'Account is disabled.'], 403);
        }
        if ($userId < 1) return Response::json(['ok' => false, 'message' => 'Bạn cần đăng nhập.'], 401);
        if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) return Response::json(['ok' => false, 'message' => 'Phiên đã hết hạn.'], 419);
        try {
            $action = (string)$request->input('action', '');
            if ($action === 'react') return $this->react($request, $userId);
            if ($action === 'vote_appeal') {
                (new CourtAppealService($this->db))->vote((int)$request->input('appeal_id', 0), $userId, (string)$request->input('vote', ''));
                return Response::json(['ok' => true]);
            }
            return Response::json(['ok' => false, 'message' => 'Thao tác không hợp lệ.'], 422);
        } catch (DomainException $error) {
            return Response::json(['ok' => false, 'message' => $error->getMessage()], 422);
        } catch (\PDOException $error) {
            return Response::json(['ok' => false, 'message' => 'Bạn đã gửi thao tác này rồi.'], 409);
        }
    }
    private function react(Request $request, int $userId): Response
    {
        $workoutId = (int)$request->input('workout_id', 0); $reaction = (string)$request->input('reaction', '');
        if (!in_array($reaction, ['heart', 'muscle', 'fire', 'sad', 'haha'], true)) throw new DomainException('Cảm xúc không hợp lệ.');
        $workout = $this->db->prepare("SELECT id FROM workouts WHERE id = ? AND status = 'valid'"); $workout->execute([$workoutId]);
        if (!$workout->fetchColumn()) throw new DomainException('Không tìm thấy buổi tập hợp lệ.');
        $old = $this->db->prepare('SELECT reaction FROM workout_reactions WHERE workout_id = ? AND user_id = ?'); $old->execute([$workoutId, $userId]);
        if ($old->fetchColumn() === $reaction) {
            $this->db->prepare('DELETE FROM workout_reactions WHERE workout_id = ? AND user_id = ?')->execute([$workoutId, $userId]);
            return Response::json(['ok' => true, 'reaction' => null]);
        }
        $stmt = $this->db->prepare('INSERT INTO workout_reactions (workout_id, user_id, reaction) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE reaction = VALUES(reaction)');
        $stmt->execute([$workoutId, $userId, $reaction]); return Response::json(['ok' => true, 'reaction' => $reaction]);
    }
}
