<?php
namespace AcMoore\Approvable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

use Carbon\Carbon;


class Version extends Model
{
	protected $table = 'approvable_versions';

	protected $guarded = [];

	protected $casts = [
		'values'      => 'json',
		'status_at'   => 'datetime',
		'is_creating' => 'boolean',
		'is_deleting' => 'boolean',
	];

	protected $appends = [
		'preview'
	];

	const STATUS_DRAFT    = 'draft';    // User's draft ready for approval
	const STATUS_APPROVED = 'approved'; // Version approved, ready for applying to database
	const STATUS_REJECTED = 'rejected'; // Version rejected, requires amends
	const STATUS_APPLIED  = 'applied';  // Applied to database
	const STATUS_DROPPED  = 'dropped';  // Draft ignored and deleted


	public function approvable(): MorphTo
	{
		return $this->morphTo();
	}


	public function approvable_parent(): MorphTo
	{
		return $this->morphTo();
	}


	public function user(): MorphTo
	{
		return $this->morphTo();
	}


	public function approve(string $notes = null)
	{
		$this->status    = self::STATUS_APPROVED;
		$this->status_at = Carbon::now();
		if (!is_null($notes)) {
			$this->notes = $notes;
		}

		$result = $this->save();

		if ($result) {

			// Optionally set the timestamp when the first draft was approved
			$approved_field = $this->approvable->timestampFieldForFirstApproved();
			if (!is_null($approved_field)) {
				$this->approvable[$approved_field] = Carbon::now();
				$this->approvable->save();
			}

			$this->approvable->fireModelEvent('approved', false);


			return $this;
		} else {
			return false;
		}
	}


	public function reject(string $notes = null)
	{
		$this->status    = self::STATUS_REJECTED;
		$this->status_at = Carbon::now();
		if (!is_null($notes)) {
			$this->notes = $notes;
		}

		$result = $this->save();

		if ($result) {
			$this->approvable->fireModelEvent('rejected', false);

			return $this;
		} else {
			return false;
		}
	}


	public function drop(string $notes = null)
	{
		$this->status    = self::STATUS_DROPPED;
		$this->status_at = Carbon::now();
		if (!is_null($notes)) {
			$this->notes = $notes;
		}

		$result = $this->save();

		if ($result) {
			$this->approvable->fireModelEvent('dropped', false);

			return $this;
		} else {
			return false;
		}
	}


	public function apply(string $notes = null)
	{
		$values = $this->values ?? [];
		$approvable = $this->approvable;


		// Optionally set the timestamp when the first draft was approved
		$approved_field = (new $this->approvable_type())->timestampFieldForFirstApproved();
		if (!is_null($approved_field)) {
			$values[$approved_field] = Carbon::now();
		}



		// Deleting
		if ($this->is_deleting) {
			$approvable->delete();

		// Creating
		} elseif (is_null($this->approvable_id)) {
			if (!empty($values)) {
				$approvable = new $this->approvable_type;
				$approvable->fill($values);
				$approvable->save();
			}

		// Updating
		} else {
			if (!empty($values)) {
				$approvable->fill($values);
				$approvable->save();
			}
		}


		$this->status    = self::STATUS_APPLIED;
		$this->status_at = Carbon::now();
		if (!is_null($notes)) {
			$this->notes = $notes;
		}


		$result = $this->save();


		if ($result) {
			$approvable->fireModelEvent('applied', false);

			return $this;
		} else {
			return false;
		}

	}


	public function getPreviewAttribute(): ?Model
	{
		if (!$this->relationLoaded('approvable')) return null;

		$this->approvable->fill($this->values);

		// Reload any loaded relations
		foreach ($this->approvable->relations as $key => $data) {
			$this->approvable->unsetRelation($key);
			$this->approvable->load($key);
		}

		return $this->approvable;
	}
}

