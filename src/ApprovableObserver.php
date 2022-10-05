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

		// Do not return anything - doing so will block other observers from running
	}


	public function creating(ApprovableContract $model)
	{
		// Do not create related objects
		$model_handles_approval = (
			$model->timestampFieldForFirstApproved() || $model->timestampFieldForFirstApplied()
		);
		if ($model->approvableParentRelation() && !$model_handles_approval) {
			$version_created = $model->createVersion();
			if ($version_created) return false;
		}

		// Do not return anything - doing so will block other observers from running
	}


	public function created(ApprovableContract $model)
	{
		$model->createVersion(false, true);

		// Do not return anything - doing so will block other observers from running
	}


	public function deleting(ApprovableContract $model)
	{
		$version_created = $model->createVersion(true);

		// Do not delete related objects
		if ($version_created && $model->approvableParentRelation()) {
			return false;
		}

		// Do not return anything - doing so will block other observers from running
	}
}
