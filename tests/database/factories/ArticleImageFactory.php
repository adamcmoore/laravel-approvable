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
use AcMoore\Approvable\Tests\Models\ArticleImage;

/*
|--------------------------------------------------------------------------
| Article Image Factories
|--------------------------------------------------------------------------
|
*/

$factory->define(ArticleImage::class, function (Faker $faker) {
    return [
		'title'    => $faker->unique()->sentence,
		'file_url' => $faker->slug().'.jpg',
    ];
});
