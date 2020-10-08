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

namespace AcMoore\Approvable\Tests;

use AcMoore\Approvable\Tests\Models\Article;
use AcMoore\Approvable\Tests\Models\ArticleImage;
use AcMoore\Approvable\Models\Version;

class ApprovableRelationsTest extends ApprovableTestCase
{

	function testCreatingNewRelations()
    {
    	$create_num = 4;
        $article = factory(Article::class)->create();

        ArticleImage::enableApproval();

        $create_images = factory(ArticleImage::class, $create_num)->create([
            'article_id' => $article->id
        ]);

        ArticleImage::disableApproval();


 		// Test related items were saved
        $article->load('images');
        $this->assertEquals(0, count($article->images));


 		// Test versions were created correctly
        $image_versions = Article::with('related_drafts')->first()->related_drafts;

        $this->assertEquals($create_num, count($image_versions));

        foreach ($image_versions as $i => $image_version) {
            $image = $create_images->where('title', $image_version->values['title'])->first();

            $this->assertEquals($image->title, $image_version->values['title']);
            // Non-approvable data should still be drafted when creating
            $this->assertEquals($image->file_url, $image_version->values['file_url']);
        }


        // Test querying Articles which have Drafts
        $article_with_drafts = Article::has('related_drafts')->get();
        $this->assertEquals(1, count($article_with_drafts));


        // Test applying draft
        foreach ($image_versions as $i => $image_version) {
        	$image_version->apply();
        }


 		// Test related items were created
        $article->load('images');
        $this->assertEquals($create_num, count($article->images));

        foreach ($article->images as $i => $image) {
            $create_image = $create_images->where('title', $image->title)->first();

            $this->assertEquals($image->title, $create_image->title);
            $this->assertEquals($image->file_url, $create_image->file_url);
        }


        // Test querying Articles which have no Drafts
        Version::whereNotNull('id')->delete();
        $article_without_versions = Article::has('related_drafts')->get();
        $this->assertEquals(0, count($article_without_versions));
    }


	function testUpdatingRelations()
    {
        $article = factory(Article::class)->create();
        $images = factory(ArticleImage::class, 4)->create([
            'article_id' => $article->id
        ]);


 		// Test related items were created
        $article->load('images');
        $this->assertEquals(4, count($article->images));


 		// Test updating related item
 		$new_title = 'New Image Title';
 		$new_file_url = 'lol-catz.jpg';
 		$image = $article->images->first();
 		$image->title = $new_title;
 		$image->file_url = $new_file_url;


        ArticleImage::enableApproval();
        $image->save();
        ArticleImage::disableApproval();


        // Test loading changes via parent
        $image_versions = Article::with('related_drafts')->first()->related_drafts;
        $image = ArticleImage::find($image->id);

        $this->assertEquals(1, count($image_versions));
        $image_version = $image_versions->first();

        $this->assertNotEquals($new_title, $image->title);
        $this->assertEquals($new_file_url, $image->file_url); // Should be saved

        $this->assertEquals($new_title, $image_version->values['title']);
        $this->assertArrayNotHasKey('file_url', $image_version->values); // Should not be versioned


        // Test querying Articles which have Versions
        $article_with_drafts = Article::has('related_drafts')->get();
        $this->assertEquals(1, count($article_with_drafts));


        // Test applying draft
        $image_version->apply();


 		// Test related items were updated
        $article->load('images');
        $image = ArticleImage::find($image->id);
        $this->assertEquals($new_title, $image->title);
        $this->assertEquals($new_file_url, $image->file_url);


        // Test querying Articles which have no Drafts
        Version::whereNotNull('id')->delete();
        $article_without_versions = Article::has('related_drafts')->get();
        $this->assertEquals(0, count($article_without_versions));
    }


	function testDeletingRelations()
    {
 		$create_num = 7;
 		$delete_num = 3;

        $article = factory(Article::class)->create();
        $images = factory(ArticleImage::class, $create_num)->create([
            'article_id' => $article->id
        ]);

 		// Test related items were created
        $article->load('images');
        $this->assertEquals($create_num, count($article->images));


 		// Test deleting related item
 		$delete_images = $article->images->take($delete_num);

        ArticleImage::enableApproval();
        foreach ($delete_images as $delete_image) {
        	$delete_image->delete();
        }
        ArticleImage::disableApproval();


        // Test loading changes via parent
        $image_versions = Article::with('related_drafts')->first()->related_drafts;
        $this->assertEquals($delete_num, count($image_versions));


        // Test deleted images
        foreach ($image_versions as $image_version) {
        	$this->assertTrue($image_version->is_deleting);
        	$this->assertNull($image_version->values);

        	$delete_image = $delete_images->where('id', $image_version->approvable_id);
        	$this->assertNotNull($delete_image);
        }


        // Test no images were deleted
        $article->load('images');
        $this->assertEquals($create_num, count($article->images));


        // Test querying Articles which have Versions
        $article_with_drafts = Article::has('related_drafts')->get();
        $this->assertEquals(1, count($article_with_drafts));


        // Test applying draft
        foreach ($image_versions as $image_version) {
        	$image_version->apply();
        }


 		// Test related items were deleted
        $article->load('images');
        $this->assertEquals($create_num - $delete_num, count($article->images));


        // Test querying Articles which have no Drafts
        Version::whereNotNull('id')->delete();
        $article_without_versions = Article::has('related_drafts')->get();
        $this->assertEquals(0, count($article_without_versions));
    }


	function testMixedOperationsOnRelations()
    {
        $create_title = 'Create Me';
        $update_title = 'Update Me';
        $delete_title = 'Delete Me';

    	$article = factory(Article::class)->create();

        $image_to_update = factory(ArticleImage::class)->create([
            'article_id' => $article->id
        ]);
        $image_to_delete = factory(ArticleImage::class)->create([
            'article_id' => $article->id,
            'title'		 => $delete_title,
        ]);


        ArticleImage::enableApproval();

        $image_to_update->title = $update_title;
        $image_to_update->save();

        $image_to_delete->delete();

        $image_to_create = factory(ArticleImage::class)->create([
            'article_id' => $article->id,
            'title'		 => $create_title,
        ]);

        ArticleImage::disableApproval();


        // Test nothing changed
        $article->load('images');
        $image_to_update_after = ArticleImage::find($image_to_update->id);
        $image_to_delete_after = ArticleImage::find($image_to_delete->id);

        $this->assertEquals(2, count($article->images));
        $this->assertNotEquals($image_to_update->title, $image_to_update_after->title);
        $this->assertEquals($image_to_delete->title, $image_to_delete_after->title);


        // Test all 3 versions created
        $article_with_drafts = Article::has('related_drafts')->get();
        $this->assertEquals(1, count($article_with_drafts));

        $article_with_drafts = Article::find($article->id)->with('related_drafts')->first();
        $this->assertEquals(3, count($article_with_drafts->related_drafts));


        // Test applying versions
        foreach ($article_with_drafts->related_drafts as $draft) {
        	$draft->apply();
        }

        $article_without_drafts = Article::has('related_drafts')->get();
        $this->assertEquals(0, count($article_without_drafts));

        $article->load('images');
        $this->assertEquals(2, count($article->images));

        $updated_image = $article->images->where('title', $update_title)->first();
        $this->assertNotNull($updated_image);

        $deleted_image = $article->images->where('title', $delete_title)->first();
        $this->assertNull($deleted_image);

        $created_image = $article->images->where('title', $create_title)->first();
        $this->assertNotNull($created_image);
    }

}