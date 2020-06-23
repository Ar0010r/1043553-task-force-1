<?php

namespace frontend\models;

use frontend\models\forms\TasksFilterForm;
use TaskForce\Exception\TaskException;
use Yii;
use yii\db\Query;

class Task extends \BaseTask
{
    public const STATUS_NEW = 0;
    public const STATUS_CANCELLED = 2;
    public const STATUS_PROCESSING = 1;
    public const STATUS_ACCOMPLISHED = 3;
    public const STATUS_FAILED = 4;

    public const ROLE_EMPLOYEE = 0;
    public const ROLE_CUSTOMER = 1;

    private $actionCancel;
    private $actionAccomplish;
    private $actionRespond ;
    private $actionRefuse;

    private $employeeId;
    private $customerId;
    private $deadline;
    private $currentStatus;

    public function __construct(int $employeeId, int $customerId, $deadline)
    {
        try {
            $this->checkDate($deadline);
        } catch (TaskException $e) {
            error_log("Error:" . $e->getMessage());
        }
            $this->employeeId = $employeeId;
            $this->customerId = $customerId;
            $this->deadline = $deadline;
            $this->currentStatus = self::STATUS_NEW;

            $this->actionCancel = new ActionCancel();
            $this->actionAccomplish = new ActionAccomplish();
            $this->actionRespond = new ActionRespond();
            $this->actionRefuse = new ActionRefuse();
    }

    public static function getDataForTasksPage(TasksFilterForm $model): array
    {
        $query = self::noFiltersQuery();
        $filters = Yii::$app->request->post() ?? [];

        if ($model->load($filters)) {
            $query = self::filterThroughAdditionalFields($model, $query);

            $query = self::filterThroughChosenCategories($model, $query);

            $query = self::filterThroughChosenPeriod($model, $query);

            $query = self::filterThroughSearchField($model, $query);
        }

        $data = $query->orderBy(['tasks.created_at' => SORT_DESC])->all();

        return self::addTimeInfo($data);
    }

    private static function noFiltersQuery(): Query
    {
        $query = new Query();
        return $query->select([
            'tasks.id',
            'title',
            'description',
            'budget',
            'tasks.created_at',
            'categories.name as category',
            'categories.image as image',
            'cities.name as city'

        ])
            ->from('tasks')
            ->join('INNER JOIN', 'categories', 'tasks.category_id = categories.id')
            ->join('INNER JOIN', 'cities', 'tasks.city_id = cities.id')
            ->where(['current_status' => Task::STATUS_NEW]);
    }

    private static function filterThroughChosenCategories(TasksFilterForm $model, Query $query): Query
    {
        if ($model->categories) {
            $categories = ['or'];
            foreach ($model->categories as $categoryId) {
                $categories[] = [
                    'tasks.category_id' => intval($categoryId)
                ];
            }
            return $query->andWhere($categories);
        }
        return $query;
    }

    private static function filterThroughAdditionalFields(TasksFilterForm $model, Query $query): Query
    {
        if ($model->additional) {
            foreach ($model->additional as $key => $field) {
                $model->$field = true;
            }
        }

        if ($model->responses) {
            $query->leftJoin('responses', 'responses.task_id = tasks.id');
            $query->andWhere(['or',
                ['responses.task_id' => null],
                ['tasks.id' => null]
            ]);
        }

        if ($model->cities) {
            $query->andWhere(['tasks.address' => null]);
        }

        return $query;
    }

    private static function filterThroughChosenPeriod(TasksFilterForm $model, Query $query): Query
    {
        if ($model->period == 'day') {
            return $query->andWhere(['>', 'tasks.created_at',  strtotime("- 1 day")]);
        } elseif ($model->period == 'week') {
            return $query->andWhere(['>', 'tasks.created_at', strtotime("- 1 week")]);
        } elseif ($model->period == 'month') {
            return $query->andWhere(['>', 'tasks.created_at', strtotime("- 1 month")]);
        }

        return $query;
    }

    private static function filterThroughSearchField(TasksFilterForm $model, Query $query): Query
    {
        if ($model->search) {
            return $query->andWhere(['like', 'tasks.title', $model->search]);
        }

        return $query;
    }

    private static function addTimeInfo(array $data): array
    {
        foreach ($data as &$task) {
            $task['created_at'] = TimeOperations::timePassed($task['created_at']);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tasks';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [
                [
                    'user_customer_id',
                    'user_employee_id',
                    'created_at',
                    'title',
                    'description',
                    'category_id',
                    'city_id',
                    'budget'
                ],
                'required'
            ],
            [
                [
                    'user_customer_id',
                    'user_employee_id',
                    'created_at',
                    'category_id',
                    'city_id',
                    'budget',
                    'current_status'
                ],
                'integer'
            ],
            [['description'], 'string'],
            [['deadline'], 'safe'],
            [['title', 'lat', 'lon', 'address'], 'string', 'max' => 50],
            [
                ['category_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Categories::className(),
                'targetAttribute' => ['category_id' => 'id']
            ],
            [
                ['user_customer_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Users::className(),
                'targetAttribute' => ['user_customer_id' => 'id']
            ],
            [
                ['user_employee_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Users::className(),
                'targetAttribute' => ['user_employee_id' => 'id']
            ],
            [
                ['city_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Cities::className(),
                'targetAttribute' => ['city_id' => 'id']
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_customer_id' => 'User Customer ID',
            'user_employee_id' => 'User Employee ID',
            'created_at' => 'Created At',
            'title' => 'Title',
            'description' => 'Description',
            'category_id' => 'Category ID',
            'city_id' => 'City ID',
            'lat' => 'Lat',
            'lon' => 'Lon',
            'address' => 'Address',
            'budget' => 'Budget',
            'deadline' => 'Deadline',
            'current_status' => 'Current Status',
        ];
    }

    /**
     * Gets query for [[Correspondences]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCorrespondences()
    {
        return $this->hasMany(Correspondence::className(), ['task_id' => 'id']);
    }

    /**
     * Gets query for [[Responses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getResponses()
    {
        return $this->hasMany(Responses::className(), ['task_id' => 'id']);
    }

    /**
     * Gets query for [[Category]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Categories::className(), ['id' => 'category_id']);
    }

    /**
     * Gets query for [[UserCustomer]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserCustomer()
    {
        return $this->hasOne(Users::className(), ['id' => 'user_customer_id']);
    }

    /**
     * Gets query for [[UserEmployee]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserEmployee()
    {
        return $this->hasOne(Users::className(), ['id' => 'user_employee_id']);
    }

    /**
     * Gets query for [[City]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCity()
    {
        return $this->hasOne(Cities::className(), ['id' => 'city_id']);
    }

    /**
     * Gets query for [[TasksFiles]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTasksFiles()
    {
        return $this->hasMany(TasksFiles::className(), ['task_id' => 'id']);
    }



    public function getAction(string $role): ?AbstractAction
    {
        try {
            Task::checkRole($role);
        } catch (TaskException $e) {
            error_log("Error:" . $e->getMessage());
        }
        $actions = [
            self::STATUS_NEW => [$this->actionCancel, $this->actionRespond],
            self::STATUS_PROCESSING => [$this->actionAccomplish, $this->actionRefuse]
        ];

        if ($actions[$this->currentStatus]) {
            foreach ($actions[$this->currentStatus] as $key => $action) {
                if ($action->checkRights($role)) {
                    return $action;
                }
            }
        }
        return null;
    }

    public function getStatuses(): ?string
    {
        $statuses = [
            self::STATUS_NEW => ["Отменен" => self::STATUS_CANCELLED, "В работе"  => self::STATUS_PROCESSING],
            self::STATUS_PROCESSING => ["Выполнено"  => self::STATUS_ACCOMPLISHED, "Провалено" => self::STATUS_FAILED]
        ];
            return $statuses[$this->currentStatus] ?? null;
    }

    public function predictStatus(?AbstractAction $action): ?array
    {
        $statuses = [
           "actionCancel" => [self::STATUS_CANCELLED => "Отменен"],
           "actionRespond" => [self::STATUS_PROCESSING => "В работе"],
            "actionAccomplish" => [self::STATUS_ACCOMPLISHED => "Выполнено"],
            "actionRefuse" => [self::STATUS_FAILED => "Провалено"]
        ];

        if ($action) {
            return $statuses[$action->getActionCode()] ?? null;
        }

        return null;
    }

    public function getCurrentStatus(): string
    {
        return $this->currentStatus;
    }

    public function setCurrentStatus(string $role): void
    {
        try {
            Task::checkRole($role);
        } catch (TaskException $e) {
            error_log("Cant change status. Error: " . $e->getMessage());
        }
            $action = $this->getAction($role);

            $status = $this->predictStatus($action) ?? [self::STATUS_NEW => "Новый"];

            $this->currentStatus = array_key_first($status);
    }

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    private function checkDate($date): bool
    {
        $dateArray = explode('.', $date);

        if (!$dateArray) {
            throw new TaskException("Please enter date in format dd.mm.yyyy");
        }

        $checkDate = checkdate($dateArray[1], $dateArray[0], $dateArray[2]);

        if (!$checkDate) {
            throw new TaskException("Please enter valid date");
        }

        return $checkDate;
    }

    public static function checkRole($role)
    {
        if ($role === self::ROLE_EMPLOYEE or $role === self::ROLE_CUSTOMER) {
            return true;
        }

        throw new TaskException("please use Task::ROLE_ constant");
    }
}
