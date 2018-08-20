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


interface ApprovableContract
{
    /**
     * Approvable Model versions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function versions(): MorphMany;


    /**
     * The Version set as STATUS_DRAFT, if any
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
   public function draft(): ? Models\Version;



    /**
     * Is there any changed/dirty data to be drafted?
     *
     * @return array
     */
    public function requiresApproval(): bool;


    /**
     * Which changed/dirty data should be drafted?
     *
     * @return array
     */
    public function dataRequiringApproval(): array;


    /**
     * Which fields should be watched for changes & drafted?
     *
     * @return array
     */
    public function fieldsRequiringApproval(): array;


    /**
     * Create a draft version, if has new values requiring approval
     *
     * @return bool - Was a draft created?
     */
    public function createVersion(): bool;
}
