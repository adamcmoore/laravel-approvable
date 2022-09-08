<?php
namespace AcMoore\Approvable;


use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;

use AcMoore\Approvable\Models\Version;
use Carbon\Carbon;


trait Approvable
{
	public static $requires_approval = false;


	public static function bootApprovable()
	{
		static::observe(new ApprovableObserver());

		app('events')->listen('eloquent.booted: '.static::class, function($model){
			$model->addObservableEvents([
				'new_draft',
				'approved',
				'rejected',
				'dropped',
				'applied',
			]);
		});
	}


	public function timestampFieldForFirstApproved(): ?string
	{
		if (!property_exists($this, 'timestamp_field_for_first_approved')) {
			return null;
		}

		return $this->timestamp_field_for_first_approved;
	}


	public function versions(): MorphMany
	{
		return $this->morphMany(Models\Version::class, 'approvable');
	}


	public function draft(): MorphOne
	{
		return $this->morphOne(Models\Version::class, 'approvable')->whereIn('status', [
			Models\Version::STATUS_DRAFT,
			Models\Version::STATUS_APPROVED,
			Models\Version::STATUS_REJECTED,
		]);
	}


	public function new_draft(): MorphOne
	{
		return $this->morphOne(Models\Version::class, 'approvable')->whereIn('status', [
			Models\Version::STATUS_DRAFT,
		]);
	}


	public function rejected_draft(): MorphOne
	{
		return $this->morphOne(Models\Version::class, 'approvable')->whereIn('status', [
			Models\Version::STATUS_REJECTED,
		]);
	}


	public function approved_draft(): MorphOne
	{
		return $this->morphOne(Models\Version::class, 'approvable')->whereIn('status', [
			Models\Version::STATUS_APPROVED,
		]);
	}


	public function related_versions(): MorphMany
	{
		return $this->morphMany(Models\Version::class, 'approvable_parent');
	}


	public function related_drafts(): MorphMany
	{
		return $this->morphMany(Models\Version::class, 'approvable_parent')->whereIn('status', [
			Models\Version::STATUS_DRAFT,
			Models\Version::STATUS_APPROVED,
			Models\Version::STATUS_REJECTED,
		]);
	}


	public function isApprovalEnabled(): bool
	{
		return static::$requires_approval;
	}


	public static function enableApproval(): void
	{
		static::$requires_approval = true;
	}


	public static function disableApproval(): void
	{
		static::$requires_approval = false;
	}


	public function requiresApproval(): bool
	{
        return count($this->dataRequiringApproval()) > 0;
	}


	public function fieldsRequiringApproval(): array
	{
		if (!property_exists($this, 'approvable') || empty($this->approvable)) {
			return $this->fillable;
		} else {
			return $this->approvable;
		}
	}


	public function dataRequiringApproval(): array
	{
		$values = $this->getDirty();
		// If we're creating, then all data should be stored in the version
		if ($this->exists) {
			$values = Arr::only($values, $this->fieldsRequiringApproval());
		}

		return $values;
	}


	public function approvableParent(): ? string
	{
		if (!property_exists($this, 'approvable_parent')) return null;

		return $this->approvable_parent;
	}


	public function approvableParentRelation()
	{
		$relation = $this->approvableParent();
		if (!$relation) return null;

		return call_user_func([$this, $relation]);
	}


	public function approvableParentModel()
	{
		$relation = $this->approvableParentRelation();
		if (!$relation) return null;

		return $relation->getModel();
	}


	public function approvableParentClass()
	{
		$model = $this->approvableParentModel();

		if (!$model) return null;
		return  $model->getMorphClass();
	}


	public function approvableParentId(): ? int
	{
		$relation = $this->approvableParentRelation();
		if (!$relation) return null;

		$foreign_key = $relation->getForeignKeyName();

		if (get_class($relation) === HasOneThrough::class) {
			$through = $this->{$this->approvableParent()};
			return object_get($through, $foreign_key);
		} else {
			return object_get($this, $foreign_key);
		}

	}


	public function createVersion(bool $is_deleting = false, bool $is_created = false): bool
	{
		$existing_draft = $this->draft;

    	// Only take a draft if setup to do so
	    if (!$this->isApprovalEnabled()) return false;

		$user = Auth::user();
		$values = null;

		if (!$is_deleting) {
			// If nothing has changed which we need to draft for, then continue regular save
			if (!$this->requiresApproval()) {
				return false;
			}

			$values = $this->dataRequiringApproval();
		}


		$new_version = [
			'status'                 => Version::STATUS_DRAFT,
			'status_at'              => Carbon::now(),
			'is_deleting'            => $is_deleting,
			'is_created'             => $is_created,
			'approvable_type'        => $this->getMorphClass(),
			'approvable_id'          => $this->getKey(),
			'approvable_parent_type' => $this->approvableParentClass(),
			'approvable_parent_id'   => $this->approvableParentId(),
			'user_type'              => ($user ? $user->getMorphClass() : null),
			'user_id'                => ($user ? $user->getKey() : null),
			'values'                 => $values,
		];


		// If there is an existing draft then merge the values and update
		$existing_draft = $this->draft;
		if ($existing_draft) {

			// If we are updating, then merge the new values with the existing draft
			if (!$is_deleting) {
				$new_version['values'] = array_merge(
					$existing_draft->values ?? [],
					$new_version['values']
				);
			}

			$existing_draft->update($new_version);

		} else {
			Version::create($new_version);
		}


		// If the parent doesn't already have a draft, create one
		// Now supporting recursion 🎉
		$create_draft_for_parent = function($child) use ($user, &$create_draft_for_parent) {
			$parent = $child->{$child->approvableParent()};
			if (!$parent) return;

			$parent->load('draft');
			if (object_get($parent, 'draft')) return;

			Version::create([
				'status'          		 => Version::STATUS_DRAFT,
				'status_at'       		 => Carbon::now(),
				'approvable_type'        => $parent->getMorphClass(),
				'approvable_id'          => $parent->getKey(),
				'approvable_parent_type' => $parent->approvableParentClass(),
				'approvable_parent_id'   => $parent->approvableParentId(),
				'user_type'       		 => ($user ? $user->getMorphClass() : null),
				'user_id'         		 => ($user ? $user->id : null),
			]);

			$create_draft_for_parent($parent);
		};
		$create_draft_for_parent($this);


		// Unset any changes to drafted fields - only when updating or saving a related version
		if ($this->exists) {
			$drafted_fields = $this->fieldsRequiringApproval();
			foreach ($drafted_fields as $field) {
				if (array_key_exists($field, $this->attributes)) {
					$this->syncOriginalAttribute($field);
				}
			}
		}


		// Fire the event to broadcast that a new draft has been created
		$this->fireModelEvent('new_draft', false);


		return true;
	}
}
