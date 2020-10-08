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


    public function creating(ApprovableContract $model)
    {
	    $version_created = $model->createVersion();

	    // Do not create related objects
	    if ($version_created && $model->approvableParentRelation()) {
		    return false;
	    } else {
		    return true;
	    }
    }


    public function created(ApprovableContract $model)
    {
	    $model->createVersion();

	    return true;
    }


    public function deleting(ApprovableContract $model)
    {
	    $version_created = $model->createVersion(true);

	    // Do not delete related objects
	    if ($version_created && $model->approvableParentRelation()) {
		    return false;
	    } else {
		    return true;
	    }
    }
}
