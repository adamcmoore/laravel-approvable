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
		'is_created'  => 'boolean',
		'is_deleting' => 'boolean',
	];

	protected $appends = [
		'preview',
		'diff',
	];

	const STATUS_DRAFT    = 'draft';    // User's draft ready for approval
	const STATUS_APPROVED = 'approved'; // Version approved, ready for applying to database
	const STATUS_REJECTED = 'rejected'; // Version rejected, requires amends
	const STATUS_APPLIED  = 'applied';  // Applied to database
	const STATUS_DROPPED  = 'dropped';  // Draft ignored and deleted


	public function approvable(): MorphTo
	{
		return $this->morphTo()->withTrashed();
	}


	public function approvable_parent(): MorphTo
	{
		return $this->morphTo()->withTrashed();
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
		if (!$result) return false;


		if ($this->approvable) {
			// Optionally set the timestamp when the first draft was approved
			$approved_field = $this->approvable->timestampFieldForFirstApproved();
			if (!is_null($approved_field)) {
				$this->approvable[$approved_field] = Carbon::now();
				$this->approvable->save();
			}

			$this->approvable->fireModelEvent('approved', false);
		}

		return $this;
	}


	public function reject(string $notes = null)
	{
		$this->status    = self::STATUS_REJECTED;
		$this->status_at = Carbon::now();
		if (!is_null($notes)) {
			$this->notes = $notes;
		}

		$result = $this->save();
		if (!$result) return false;


		if ($this->approvable) {
			$this->approvable->fireModelEvent('rejected', false);
		}

		return $this;
	}


	public function drop(string $notes = null)
	{
		$this->status    = self::STATUS_DROPPED;
		$this->status_at = Carbon::now();
		if (!is_null($notes)) {
			$this->notes = $notes;
		}

		$result = $this->save();
		if (!$result) return false;


		if ($this->approvable) {
			$this->approvable->fireModelEvent('dropped', false);
		}

		return $this;
	}


	public function apply(string $notes = null)
	{
		$values = $this->values ?? [];
		$approvable = $this->approvable;

		// Optionally set the timestamp when the first draft was approved
		$approved_field = (new $this->approvable_type())->timestampFieldForFirstApproved();


		// Deleting
		if ($this->is_deleting) {
			$approvable->delete();

			// Creating
		} elseif (is_null($this->approvable_id)) {
			if (!empty($values)) {
				$approvable = new $this->approvable_type;
				$approvable->fill($values);
				if (!is_null($approved_field)) {
					$approvable->$approved_field = Carbon::now();
				}
				$approvable->save();
			}

			// Updating
		} else {
			if (!empty($values)) {
				$approvable->fill($values);
				if (!is_null($approved_field)) {
					$approvable->$approved_field = Carbon::now();
				}
				$approvable->save();
			}
		}


		$this->status    = self::STATUS_APPLIED;
		$this->status_at = Carbon::now();
		if (!is_null($notes)) {
			$this->notes = $notes;
		}


		$result = $this->save();
		if (!$result) return false;



		$approvable->fireModelEvent('applied', false);

		return $this;
	}


	public function getPreviewAttribute(): ?Model
	{
		if (!$this->relationLoaded('approvable')) return null;

		$approvable = $this->approvable->replicate();
		$approvable->fill($this->values);

		// Reload any loaded relations
		foreach ($approvable->relations as $key => $data) {
			$approvable->unsetRelation($key);
			$approvable->load($key);
		}

		return $approvable;
	}

	public function getDiffAttribute(): ?object
	{
		if (!$this->relationLoaded('approvable')) return null;


		$new = new $this->approvable_type;
		if (!$this->is_deleting) {
			$new->fill($this->values);
		}


		if ($this->is_created) {
			$old = new $this->approvable_type;;
		} else {
			$old = $this->approvable;
		}


		// Get all possible fields
		$fields = array_keys(array_merge($new->toArray(), $old->toArray()));

		// Build a diff object
		$diff = [];
		foreach ($fields as $field) {
			$old_val = object_get($old, $field);
			$new_val = object_get($new, $field);

			// If the calue hasn't been drafted, and we're not deleting,
			// then ignore the field
			if (!$this->is_deleting && !array_key_exists($field, $this->values)) {
				continue;
			}

			if ($old_val !== $new_val) {
				$diff[$field] = (object) [
					'old' => $old_val,
					'new' => $new_val,
				];
			}
		}


		return (object) $diff;
	}
}

