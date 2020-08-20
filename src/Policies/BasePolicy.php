<?php

namespace Zijinghua\Zvoyager\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use TCG\Voyager\Contracts\User;
use TCG\Voyager\Facades\Voyager;
use Zijinghua\Zbasement\Facades\Zsystem;

class BasePolicy
{
    use HandlesAuthorization;

    protected static $datatypes = [];

    /**
     * Handle all requested permission checks.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return bool
     */
    public function __call($name, $arguments)
    {
        if (count($arguments) < 2) {
            throw new \InvalidArgumentException('not enough arguments');
        }
        /** @var \TCG\Voyager\Contracts\User $user */
        $user = $arguments[0];

        /** @var $model */
        $parameters = $arguments[1];

        return $this->checkPermission($user, $parameters);
    }

    /**
     * Determine if the given model can be restored by the user.
     *
     * @param \TCG\Voyager\Contracts\User $user
     * @param  $model
     *
     * @return bool
     */
//    public function restore(User $user, $model)
//    {
//        // Can this be restored?
//        return $model->deleted_at && $this->checkPermission($user, $model, 'delete');
//    }

    /**
     * Determine if the given model can be deleted by the user.
     *
     * @param \TCG\Voyager\Contracts\User $user
     * @param  $model
     *
     * @return bool
     */
//    public function delete(User $user, $model)
//    {
//        // Has this already been deleted?
//        $soft_delete = $model->deleted_at && in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model));
//
//        return !$soft_delete && $this->checkPermission($user, $model, 'delete');
//    }

    /**
     * Check if user has an associated permission.
     *
     * @param \TCG\Voyager\Contracts\User $user
     * @param object                      $model
     * @param string                      $action
     *
     * @return bool
     */
    protected function checkPermission( $user, $parameters)
    {
        $service=Zsystem::service('authorize');
        return $service->checkPermission($user,$parameters);
//        if (!isset(self::$datatypes[get_class($model)])) {
//            $dataType = Voyager::model('Datatype');
//            self::$datatypes[get_class($model)] = $dataType->where('model_name', get_class($model))->first();
//        }
//
//        $dataType = self::$datatypes[get_class($model)];

//        return ;
    }
}
