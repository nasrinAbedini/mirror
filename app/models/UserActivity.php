<?php 
use Phalcon\Mvc\Model;

class UserActivity extends Model
{

    public function initialize()
    {
        $this->setSource('user_activities');
    }

    public $id;
    public $activity_type;
    public $activity_duration;
    public $start_time;
    public $end_time;
    public $priority_level;
    public $description;
    public $user_id;
    public $created_at;

    public function validation()
    {
        $this->validate(
            new \Phalcon\Validation\Validator\Uniqueness([
                'field' => ['user_id', 'start_time'],
                'message' => 'This activity already exists for the user at the given start time.'
            ])
        );

        return $this->validationHasFailed() != true;
    }

    public static function getActivities($userId, $startTime, $endTime, $filters = [], $orderBy = null)
    {
        $conditions = 'user_id = :user_id: AND start_time >= :start_time: AND end_time <= :end_time:';
        $bind = [
            'user_id' => $userId,
            'start_time' => $startTime,
            'end_time' => $endTime
        ];
    
       
        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                $conditions .= " AND $key = :$key:";
                $bind[$key] = $value;
            }
        }
    
        $options = [
            'conditions' => $conditions,
            'bind' => $bind
        ];
    
        if (!empty($orderBy)) {
            $options['order'] = $orderBy;
        }
    
        return self::find($options);
    }
    
}