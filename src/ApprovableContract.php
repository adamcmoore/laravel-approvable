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
use Illuminate\Database\Eloquent\Relations\MorphOne;


interface ApprovableContract
{

	/**
	 * Get the field to use for marking the model as approved
	 *
	 * @return string|null
	 */
	public function timestampFieldForFirstApproved(): ?string;


    /**
     * Approvable Model versions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function versions(): MorphMany;


    /**
     * Approvable Model version considered as draft
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
   public function draft(): MorphOne;


    /**
     * Approvable Model versions belonging to this model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function related_versions(): MorphMany;


    /**
     * Approvable Model versions considered as drafts belonging to this model
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function related_drafts(): MorphMany;



    /**
     * Is approval enabled, set by static property $requires_approval
     *
     * @return bool
     */
    public function isApprovalEnabled(): bool;


    /**
     * Is there any changed/dirty data to be drafted?
     *
     * @return array
     */
    public function requiresApproval(): bool;


    /**
     * Which fields should be watched for changes & drafted?
     *
     * @return array
     */
    public function fieldsRequiringApproval(): array;


    /**
     * Which changed/dirty data should be drafted?
     *
     * @return array
     */
    public function dataRequiringApproval(): array;


    /**
     * The name of the defined parent relation, from the $approvable_parent property
     *
     * @return string
     */
    public function approvableParent(): ? string;


    /**
     * The parent relation defined as approvableParent()
     *
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    public function approvableParentRelation();


    /**
     * The parent model loaded via approvableParentRelation()
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function approvableParentModel();



    /**
     * The foriegn_id used in the relation
     *
     * @return int
     */
    public function approvableParentId(): ? int;


	/**
	 * Create a draft version, if has new values requiring approval
	 *
	 * @param bool $is_deleting
	 * @return bool - Was a draft created?
	 */
    public function createVersion(bool $is_deleting = false): bool;

}
