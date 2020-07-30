<?php

namespace frontend\components\traits;

use frontend\components\UserComponent;
use frontend\models\pivot\UsersCategories;
use frontend\models\Task;
use frontend\models\User;
use frontend\models\UserPhoto;
use frontend\models\UserReview;
use yii\web\NotFoundHttpException;

trait QueriesTrait
{
    /**
     * @param int $id
     * @return User
     * @throws NotFoundHttpException
     */
    private static function findUserWithPhotosAndCategories(int $id): User
    {
        $user = User::find()
            ->where(['users.id' => $id])
            ->joinWith('userPhotos')
            ->joinWith('categories')
            ->one();
        if (!$user) {
            throw new NotFoundHttpException("Не найден пользователь с ID: " . $id);
        }

        return $user;
    }

    /**
     * @param int $userId
     * @return User
     */
    private static function findUser(int $userId): User
    {
        return User::find()
            ->where(['users.id' => $userId])
            ->one();
    }

    /**
     * @param int $employee_id
     * @param int $customer_id
     * @param int $created_at
     * @return string
     *
     * Функция ищет задание относящееся к отклику, и если оно есть возващает его название
     */
    private static function findTaskTitleRelatedToReview(int $employee_id, int $customer_id, int $created_at): string
    {
        $task = Task::find()
            ->select('title')
            ->andwhere(['tasks.user_employee_id' => $employee_id])
            ->andwhere(['tasks.user_customer_id' => $customer_id])
            ->andwhere(['<', 'tasks.created_at', $created_at])
            ->orderBy(['tasks.created_at' => SORT_DESC])
            ->one();

        if (is_null($task)) {
            return UserComponent::NO_TASK_FOUND_MESSAGE;
        } else {
            return strval($task['title']);
        }
    }

    /**
     * @param int $userId
     * @return array
     */
    private static function findUsersReview(int $userId): array
    {
        return UserReview::find()
            ->where(['user_employee_id' => $userId])
            ->all();
    }

    /**
     * @param int $userId
     * @return string
     */
    private static function findUsersPhoto(int $userId): string
    {
        $photo = UserPhoto::find()->select(['photo'])->where(['user_id' => $userId])->one();
        return $photo['photo'] ?? UserComponent::DEFAULT_USER_PHOTO;
    }

    /**
     * @param int $userId
     * @return array
     */
    private static function findUsersWithCategories(int $userId): array
    {
        return UsersCategories::find()
            ->select(['categories.name as name'])
            ->joinWith('categories')
            ->where(['user_id' => $userId])
            ->all();
    }

    /**
     * @param int $userId
     * @return int
     */
    private static function countAverageUsersRate(int $userId): int
    {
        return UserReview::find()
                ->select(['vote'])
                ->where(['user_employee_id' => $userId])
                ->average('vote') ?? 0;
    }

    /**
     * @param int $userId
     * @return int
     */
    private static function countAccomplishedTasks(int $userId): int
    {
        return Task::find()
                ->where(['user_employee_id' => $userId])
                ->where(['current_status' => Task::STATUS_ACCOMPLISHED_CODE])
                ->count() ?? 0;
    }

    /**
     * @param int $userId
     * @return int
     */
    private static function countUsersReviews(int $userId): int
    {
        return UserReview::find()
                ->where(['user_employee_id' => $userId])
                ->count() ?? 0;
    }

    /**
     * @param int $id
     * @return Task
     * @throws NotFoundHttpException
     *
     */
    private static function getTaskWithResponsesCategoriesFiles(int $id): Task
    {
        $task = Task::find()
            ->select(['*',
                'tasks.id as id',
                'categories.name as category',
                'categories.image as image',
            ])
            ->joinWith('responses')
            ->joinWith('category')
            ->joinWith('tasksFiles')
            ->where(['tasks.id' => $id])
            ->one();

        if (!$task) {
            throw new NotFoundHttpException("Не найдено задание с ID: " . $id);
        }

        return $task;
    }
}
