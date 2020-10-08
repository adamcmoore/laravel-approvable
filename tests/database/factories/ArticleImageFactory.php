<?php
use Faker\Generator as Faker;
use AcMoore\Approvable\Tests\Models\ArticleImage;

$factory->define(ArticleImage::class, function (Faker $faker) {
    return [
		'title'    => $faker->unique()->sentence,
		'file_url' => $faker->slug().'.jpg',
    ];
});
