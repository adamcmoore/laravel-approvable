<?php
namespace AcMoore\Approvable;


class ApprovableObserver
{

    /**
     * Is the model being restored?
     *
     * @var bool
     */
    public static $restoring = false;


    public function updating(ApprovableContract $model)
    {
        // Ignore the updated event when restoring
        if (static::$restoring) return;

        $model->createVersion();

        // Continue the standard update for data which wasn't versioned
        return true;
    }


    public function created(ApprovableContract $model)
    {
        $model->createVersion();

        return true;
    }


    public function deleting(ApprovableContract $model)
    {
    	$model->createVersion(true);

        return true;
    }
}
