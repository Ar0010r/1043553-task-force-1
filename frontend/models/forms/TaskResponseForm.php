<?php

namespace frontend\models\forms;

use frontend\models\Response;
use Yii;
use yii\base\Model;

class TaskResponseForm extends Model
{
    public $price;
    public $comment;

    public function attributeLabels()
    {
        return [
            'price' => 'Ваша цена',
            'comment' => 'Комментарий'
        ];
    }

    public function rules()
    {
        return [
            [['price', 'comment'], 'safe'],
            [['price', 'comment'], 'required'],
            [['price'], 'integer', 'min' => 1],
            [['comment'], 'string']
        ];
    }

    public function save(int $taskId)
    {
        $response = new Response();

        $response->task_id = $taskId;
        $response->user_employee_id = Yii::$app->user->getId();
        $response->your_price = $this->price;
        $response->comment = $this->comment;
        $response->created_at = time();

        return $response->save(false);
    }
}
