<?php
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
