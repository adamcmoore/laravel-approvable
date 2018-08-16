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


    public function draft(): ? Models\Version
    {
    	return $this->versions()->where('status', Models\Version::STATUS_DRAFT)->first();
    }


    public function requiresApproval(): bool
    {
    	return static::$requires_approval;
    }


    public function fieldsRequiringApproval(): array
    {
    	if (is_null($this->approvable) || empty($this->approvable)) {
    		return $this->fillable;
    	} else {
    		return $this->approvable;
    	}
    }


    public function createDraft(): bool
    {
    	// Only take a draft if setup to do so
        if (!$this->requiresApproval()) return false;


		$user   = Auth::user();
		$values = $this->getDirty();
		$values = array_only($values, $this->fieldsRequiringApproval());


		// If nothing has changed which we need to draft for, then continue regular save
		if (empty($values)) return false;

		$new_version = [
        	'status'		  => Version::STATUS_DRAFT,
        	'status_at'		  => Carbon::now(),
        	'approvable_type' => get_class($this),
        	'approvable_id'   => $this->id,
        	'user_type'		  => ($user ? get_class($user) : null),
        	'user_id'		  => ($user ? $user->id : null),
        	'values'		  => $values,      	
        ];


		// If there is an existing draft then merge the values and update		
		$draft = $this->draft();
		if ($draft) {
			$new_version['values'] = array_merge(
				$draft->values, 
				$new_version['values']
			);
        	$draft->update($new_version);

		} else {
        	Version::create($new_version);
		}


		return true;
    }

}
