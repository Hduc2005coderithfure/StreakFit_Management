<?php
// Presentation-only values; the existing PHP services remain authoritative.
$arenaScheduled = count(array_unique(array_column($plansCurrent ?? [], 'planned_date')));
$arenaDone = max(0, (int)$weeklyCount);
$arenaGoal = max(0, (int)$goal['target_sessions']);
$arenaEffective = max(0, (int)$effectiveTarget);
$arenaRemaining = max(0, $arenaGoal - $arenaDone);
$arenaPercent = $arenaGoal ? min(100, (int)round($arenaDone * 100 / $arenaGoal)) : 0;
$arenaPoints = (int)$user['total_points'];
$arenaRankProgress = $rank['next'] === null ? 100 : max(0, min(100, (int)round(100 * ($arenaPoints - $rank['min']) / ($rank['next'] - $rank['min']))));
$arenaQuote = match (true) {
    $arenaDone > $arenaGoal && $arenaGoal > 0 => 'Bạn đã vượt qua mục tiêu. Hãy tiếp tục tiến bộ và nghỉ ngơi đúng lúc.',
    $arenaGoal > 0 && $arenaDone >= $arenaGoal => 'Lời hứa với chính mình đã được thực hiện. Tuần này thuộc về bạn.',
    $arenaDone > 0 && $arenaRemaining === 1 => 'Chỉ còn một bước nữa. Hãy hoàn thành hành trình tuần này.',
    $arenaDone === 0 => 'Không cần hoàn hảo. Chỉ cần bắt đầu và không bỏ cuộc.',
    default => 'Kỷ luật hôm nay là sức mạnh của ngày mai.',
};
$arenaSession = substr(hash('sha256', session_id()), 0, 24);
?>
