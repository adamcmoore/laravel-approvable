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

namespace AcMoore\Approvable\Tests\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use AcMoore\Approvable\ApprovableContract;
use AcMoore\Approvable\Approvable;

class User extends Model implements ApprovableContract, Authenticatable
{
    use \Illuminate\Auth\Authenticatable;
    use Approvable;


    protected $casts = [
        'is_admin' => 'bool',
    ];
}
