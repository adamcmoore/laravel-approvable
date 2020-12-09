<?php
use Faker\Generator as Faker;
use AcMoore\Approvable\Tests\Models\Article;

$factory->define(Article::class, function (Faker $faker) {
    return [
        'title'        => $faker->unique()->sentence,
        'content'      => $faker->unique()->paragraph(6),
        'published_at' => null,
    ];
});
