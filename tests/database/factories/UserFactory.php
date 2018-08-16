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
use Faker\Generator as Faker;
use AcMoore\Approvable\Tests\Models\User;

/*
|--------------------------------------------------------------------------
| User Factories
|--------------------------------------------------------------------------
|
*/

$factory->define(User::class, function (Faker $faker) {
    return [
        'is_admin'   => $faker->randomElement([0, 1]),
        'first_name' => $faker->firstName,
        'last_name'  => $faker->lastName,
        'email'      => $faker->unique()->safeEmail,
    ];
});