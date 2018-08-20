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
        $article = factory(Article::class)->create(); 
 
        ArticleImage::$requires_approval = true; 
 
        $images = factory(ArticleImage::class, 4)->create([ 
            'article_id' => $article->id 
        ]); 
         
        ArticleImage::$requires_approval = false; 
 

 		// Test related items were not saved
        $article->load('images'); 
        $this->assertEquals(0, count($article->images)); 
 

 		// Test versions were created correctly
        $image_versions = Article::with('relation_versions')->first()->relation_versions;

        $this->assertEquals(count($images), count($image_versions)); 

        foreach ($image_versions as $i => $image_version) {
            $image = $images->where('title', $image_version->values['title'])->first(); 
 
            $this->assertEquals($image->title, $image_version->values['title']);  
            // Non-approvable data should still be drafted when creating 
            $this->assertEquals($image->file_url, $image_version->values['file_url']); 
        }


        // Test querying Articles which have Versions
        $article_with_versions = Article::has('relation_versions')->get();
        $this->assertEquals(1, count($article_with_versions));

        Version::whereNotNull('id')->delete();
        $article_without_versions = Article::has('relation_versions')->get();
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


        ArticleImage::$requires_approval = true; 
        $image->save();        
        ArticleImage::$requires_approval = false; 


        // Test loading changes via parent
        $image_versions = Article::with('relation_versions')->first()->relation_versions;
        $image = ArticleImage::find($image->id);

        $this->assertEquals(1, count($image_versions)); 
        $image_version = $image_versions->first();
 
        $this->assertNotEquals($new_title, $image->title);
        $this->assertEquals($new_file_url, $image->file_url); // Should be saved

        $this->assertEquals($new_title, $image_version->values['title']);
        $this->assertArrayNotHasKey('file_url', $image_version->values); // Should not be versioned


        // Test querying Articles which have Versions
        $article_with_versions = Article::has('relation_versions')->get();
        $this->assertEquals(1, count($article_with_versions));

        Version::whereNotNull('id')->delete();
        $article_without_versions = Article::has('relation_versions')->get();
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

        ArticleImage::$requires_approval = true;
        foreach ($delete_images as $delete_image) {
        	$delete_image->delete();
        }
        ArticleImage::$requires_approval = false; 


        // Test loading changes via parent
        $image_versions = Article::with('relation_versions')->first()->relation_versions;
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
        $article_with_versions = Article::has('relation_versions')->get();
        $this->assertEquals(1, count($article_with_versions));

        Version::whereNotNull('id')->delete();
        $article_without_versions = Article::has('relation_versions')->get();
        $this->assertEquals(0, count($article_without_versions));
    } 


}