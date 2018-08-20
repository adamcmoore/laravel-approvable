<?php
/**
 * This file is part of the Laravel Approvable package.
 *
 * @author     Adam Moore <adam@acmoore.co.uk>
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */

namespace AcMoore\Approvable;


use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;

use AcMoore\Approvable\Models\Version;
use Carbon\Carbon;


trait Approvable
{
    public static $requires_approval = false;


    public static function bootApprovable()
    {
        static::observe(new ApprovableObserver());
    }



    public function versions(): MorphMany
    {
        return $this->morphMany(Models\Version::class, 'approvable');
    }


    public function related_versions(): MorphMany
    {
        return $this->morphMany(Models\Version::class, 'approvable_parent');
    }


    public function draft(): ? Models\Version
    {
    	return $this->versions()->where('status', Models\Version::STATUS_DRAFT)->first();
    }


    public function related_drafts(): ? MorphMany
    {
        return $this->related_versions()->where('status', Models\Version::STATUS_DRAFT);
    }



    public function requiresApproval(): bool
    {
    	return static::$requires_approval;
    }


    public function fieldsRequiringApproval(): array
    {
        if (!property_exists($this, 'approvable') || empty($this->approvable)) { 
            return $this->fillable;
        } else {
            return $this->approvable;
        }
    }

 
    // Only consider changed/dirty data and only include whitelisted fields 
    public function dataRequiringApproval(): array 
    { 
        $values = $this->getDirty(); 

        // If we're creating, then all data should be stored in the version 
        if ($this->exists) {
            $values = array_only($values, $this->fieldsRequiringApproval()); 
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
 
 
    public function approvableParentRelationKey(): ? string 
    { 
        $parent_model = $this->approvableParentModel(); 
        if (!$parent_model) return null; 
 
        foreach ($this->approvableRelations($parent_model) as $relationship_key => $relationship) { 
            if (get_class($relationship->getModel()) === get_class($this)) { 
                return $relationship_key; 
            } 
        } 
         
        return null; 
    } 
 
 
    public function approvableParentId(): ? int 
    { 
        $relation = $this->approvableParentRelation(); 
        if (!$relation) return null; 
  
        $foreign_key = $relation->getForeignKey(); 

        return object_get($this, $foreign_key); 
    } 




    public function createVersion(bool $is_deleting = false): bool
    {
    	// Only take a draft if setup to do so and has data to version
        if (!$this->requiresApproval()) return false;


		$user = Auth::user();
        $values = null;


        if (!$is_deleting) {
    		$values = $this->dataRequiringApproval();

    		// If nothing has changed which we need to draft for, then continue regular save
    		if (empty($values)) return false;
        }


		$new_version = [
            'status'                 => Version::STATUS_DRAFT,
            'status_at'              => Carbon::now(),
            'is_deleting'            => $is_deleting,
            'approvable_type'        => get_class($this),
            'approvable_id'          => $this->id,
            'approvable_parent_type' => get_class($this->approvableParentModel()),
            'approvable_parent_id'   => $this->approvableParentId(),
            'user_type'              => ($user ? get_class($user) : null),
            'user_id'                => ($user ? $user->id : null),
            'values'                 => $values,
        ];


		// If there is an existing draft then merge the values and update		
		$existing_draft = $this->draft();
		if ($existing_draft) {

            // If we are updating, then merge the new values with the existing draft
            if (!$is_deleting) {
                $new_version['values'] = array_merge($existing_draft->values, $new_version['values']);
            }

        	$existing_draft->update($new_version);
            
		} else {
        	Version::create($new_version);
		}

 
        // Unset any changes to drafted fields 
        $drafted_fields = $this->fieldsRequiringApproval(); 
        foreach ($drafted_fields as $field) { 
            if (array_key_exists($field, $this->attributes)) { 
                $this->syncOriginalAttribute($field); 
            } 
        }         


		return true;
    }


}
