<?php
declare(strict_types=1);

class ProductivityService  {
    /**
     * Calculate the Focus Time Ratio.
     * 
     * @param int $userId
     * @param string $startTime
     * @param string $endTime
     * @return float
     */
    public static function calculateFocusTimeRatio($userId, $startTime, $endTime) {
        $activities = UserActivity::getActivities($userId, $startTime, $endTime);

        $highPriorityTime = 0;
        $totalTime = 0;

        foreach ($activities as $activity) {
            $totalTime += $activity->activity_duration;
            if ($activity->priority_level === 'HIGH') {
                $highPriorityTime += $activity->activity_duration;
            }
        }

        return ($totalTime > 0) ? ($highPriorityTime / $totalTime) * 100 : 0;
    }

    /**
     * Calculate the Context Switching Index.
     * 
     * @param int $userId
     * @param string $startTime
     * @param string $endTime
     * @return int
     */
    public static function calculateContextSwitchingIndex($userId, $startTime, $endTime) {
        $activities = UserActivity::getActivities($userId, $startTime, $endTime);

        $previousType = null;
        $switchCount = 0;

        foreach ($activities as $activity) {
            if ($previousType !== null && $activity->activity_type !== $previousType) {
                $switchCount++;
            }
            $previousType = $activity->activity_type;
        }

        return $switchCount;
    }

    /**
     * Calculate the Break Effectiveness.
     * 
     * @param int $userId
     * @param string $startTime
     * @param string $endTime
     * @return float
     */
    public static function calculateBreakEffectiveness($userId, $startTime, $endTime) {
        $activities = UserActivity::getActivities($userId, $startTime, $endTime);

        $focusDuration = 0;
        $breakDuration = 0;
        $effectiveBreaks = 0;

        foreach ($activities as $activity) {
            if ($activity->activity_type === 'focus_session') {
                $focusDuration += $activity->activity_duration;
            } elseif ($activity->activity_type === 'break') {
                $breakDuration += $activity->activity_duration;
                if ($focusDuration >= 60 && $activity->activity_duration >= 5) {
                    $effectiveBreaks++;
                }
                $focusDuration = 0; // Reset after a break
            }
        }

        return ($effectiveBreaks > 0) ? ($effectiveBreaks / ($breakDuration / 5)) * 100 : 0;
    }

    /**
     * Calculate the Productivity Score.
     * 
     * @param float $focusTimeRatio
     * @param int $contextSwitchingIndex
     * @param float $breakEffectiveness
     * @return float
     */
    public static function calculateProductivityScore($focusTimeRatio, $contextSwitchingIndex, $breakEffectiveness) {
        // Weighted scores:
        $focusWeight = 0.4;
        $switchWeight = 0.3;
        $breakWeight = 0.3;

        // Normalize
        $normalizedSwitchIndex = max(0, 20 - $contextSwitchingIndex) / 20 * 100;

        return (
            ($focusTimeRatio * $focusWeight) +
            ($normalizedSwitchIndex * $switchWeight) +
            ($breakEffectiveness * $breakWeight)
        );
    }

    /**
     * Generate Personalized Recommendations
     * 
     * @param float $focusTimeRatio
     * @param int $contextSwitchingIndex
     * @param float $breakEffectiveness
     * @return array
     */
    public static function generateRecommendations($userId, $startTime, $endTime, float $focusTimeRatio, int $contextSwitchingIndex, float $breakEffectiveness): array {
        $recommendations = [];

        // High-priority conditions
        if ($focusTimeRatio < 50 && count($recommendations) < 3) {
            $recommendations[] = "Your focus time is below 50%. Prioritize fewer tasks tomorrow for better focus.";
        }

        if ($contextSwitchingIndex > 10) {
            $recommendations[] = "You switched contexts frequently. Dedicate specific time blocks for each activity.";
        }

        if ($breakEffectiveness < 60) {
            $recommendations[] = "Your breaks were not effective. Take a 5-10 minute break every hour.";
        }

        // These values must be calculated based on user activities
        $totalLowPriorityTime = self::calculateLowPriorityTime($userId, $startTime, $endTime);
        $longWorkWithoutBreak = self::calculateLongWorkWithoutBreak($userId, $startTime, $endTime);

        if ($totalLowPriorityTime > 120 && count($recommendations) < 3) {
            $recommendations[] = "You spent over 2 hours on low-priority tasks. Reassess your daily priorities.";
        }

        if ($longWorkWithoutBreak > 90 && count($recommendations) < 3) {
            $recommendations[] = "You worked over 90 minutes without a break. Set alarms to remind you to pause.";
        }

        if (count($recommendations) < 3) {
            $recommendations[] = "Create a structured schedule with buffer times to handle unexpected tasks.";
        }

        //  add another check for consistent 
        $consistentLowProductivityDays = self::calculateLowProductivityDays($userId, $startTime, $endTime);
        if ($consistentLowProductivityDays > 3) {
            $recommendations[] = "You’ve had multiple low-productivity days. Review your workload for potential overload.";
        }

        return $recommendations;
    }

    /**
     * Calculate low priority time
     * 
     * @param int $userId
     * @param string $startTime
     * @param string $endTime
     * @return int
     */
    public static function calculateLowPriorityTime($userId, $startTime, $endTime) {
        $activities = UserActivity::getActivities($userId, $startTime, $endTime);
        $lowPriorityTime = 0;

        foreach ($activities as $activity) {
            if ($activity->priority_level === 'LOW') {
                $lowPriorityTime += $activity->activity_duration;
            }
        }

        return $lowPriorityTime;
    }

    /**
     * Calculate long work without break time
     * 
     * @param int $userId
     * @param string $startTime
     * @param string $endTime
     * @return int
     */
    public static function calculateLongWorkWithoutBreak($userId, $startTime, $endTime) {
        $activities = UserActivity::getActivities($userId, $startTime, $endTime);
        $longWorkTime = 0;
        $lastBreakTime = 0;

        foreach ($activities as $activity) {
            if ($activity->activity_type === 'focus_session') {
                $longWorkTime += $activity->activity_duration;
            } elseif ($activity->activity_type === 'break') {
                if ($longWorkTime > 90) {
                    $lastBreakTime += $longWorkTime;
                }
                $longWorkTime = 0;
            }
        }

        return $lastBreakTime;
    }

    /**
     * Calculate consistent low productivity days
     * 
     * @param int $userId
     * @param string $startTime
     * @param string $endTime
     * @return int
     */
    public static function calculateLowProductivityDays($userId, $startTime, $endTime) {

    
        $activities = UserActivity::getActivities($userId, $startTime, $endTime);
    
        $lowProductivityDays = 0;
        $dayActivities = [];
        $threshold = 50; 
    
        foreach ($activities as $activity) {
            $activityDate = date('Y-m-d', strtotime($activity->start_time));
            if (!isset($dayActivities[$activityDate])) {
                $dayActivities[$activityDate] = [];
            }
            $dayActivities[$activityDate][] = $activity;
        }
    
        // Calculate the productivity score
        foreach ($dayActivities as $day => $activities) {
            $focusTimeRatio = self::calculateFocusTimeRatio($userId, $startTime, $endTime);
            $contextSwitchingIndex = self::calculateContextSwitchingIndex($userId, $startTime, $endTime);
            $breakEffectiveness = self::calculateBreakEffectiveness($userId, $startTime, $endTime);
            
            $productivityScore = self::calculateProductivityScore($focusTimeRatio, $contextSwitchingIndex, $breakEffectiveness);
            
            // If the productivity score for the day is below the threshold, considered a low productivity day
            if ($productivityScore < $threshold) {
                $lowProductivityDays++;
            }
        }
    
        return $lowProductivityDays;
    }

    /**
     * Get Productivity Insights
     * 
     * @param int $userId
     * @param string $startTime
     * @param string $endTime
     * @return array
     */
    public static function getProductivityInsights($userId, $startTime, $endTime) {
        $focusTimeRatio = self::calculateFocusTimeRatio($userId, $startTime, $endTime);
        $contextSwitchingIndex = self::calculateContextSwitchingIndex($userId, $startTime, $endTime);
        $breakEffectiveness = self::calculateBreakEffectiveness($userId, $startTime, $endTime);
        $productivityScore = self::calculateProductivityScore($focusTimeRatio, $contextSwitchingIndex, $breakEffectiveness);
        $recommendations = self::generateRecommendations($userId, $startTime, $endTime, $focusTimeRatio, $contextSwitchingIndex, $breakEffectiveness);

        return [
            'focusTimeRatio' => $focusTimeRatio,
            'contextSwitchingIndex' => $contextSwitchingIndex,
            'breakEffectiveness' => $breakEffectiveness,
            'productivityScore' => $productivityScore,
            'recommendations' => $recommendations,
        ];
    }
}