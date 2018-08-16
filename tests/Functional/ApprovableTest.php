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
use AcMoore\Approvable\Tests\Models\User;
use AcMoore\Approvable\Models\Version;

class ApprovableTest extends ApprovableTestCase
{

	function testNotCreatingDraft()
	{
        $article = factory(Article::class)->create();

		$new_title = 'New Title';
		$article->title = $new_title;
		$article->save();

		$article = Article::with(['versions.user'])->find($article->id);

		$this->assertEquals($new_title, $article->title);
		$this->assertEquals(0, count($article->versions));
	}


	function testCreatingDraft()
	{
        $user = factory(User::class)->create();
        $article = factory(Article::class)->create();


		$this->be($user);


		Article::$requires_approval = true;

		$new_title = 'New Title';
		$article->title = $new_title;
		$article->published_at = new \DateTime();
		$article->save();

		$article = Article::with(['versions.user'])->find($article->id);

		$this->assertNotEquals($new_title, $article->title);

		$this->assertEquals(1, count($article->versions));

		$draft = $article->draft();

		$this->assertEquals($new_title, $draft->values['title']);
		$this->assertEquals($user->id, $draft->user->id);

		// Test that only changed content is drafted
		$this->assertArrayNotHasKey('content', $draft->values);

		// Test that only fields marked as approvable are drafted
		$this->assertArrayNotHasKey('published_at', $draft->values);
	}


	function testAnonymousDraft()
	{
        $article = factory(Article::class)->create();

		Article::$requires_approval = true;

		$new_title = 'New Title';
		$article->title = $new_title;
		$article->published_at = new \DateTime();
		$article->save();

		$article = Article::with(['versions.user'])->find($article->id);

		$this->assertNotEquals($new_title, $article->title);
		$this->assertEquals(1, count($article->versions));

		$draft = $article->draft();

		$this->assertEquals($new_title, $draft->values['title']);
		$this->assertNull($draft->user);
	}


	function testNotCreatingDraftWhenNoChanges()
	{
        $article = factory(Article::class)->create();

		Article::$requires_approval = true;

		$published_at = $this->faker->dateTime();

		$article->published_at = $published_at; // Should not require approval
		$article->save();

		$article = Article::with(['versions.user'])->find($article->id);

		$this->assertEquals($published_at, $article->published_at);
		$this->assertEquals(0, count($article->versions));
	}


	function testUpdatingDraft()
	{
        $article = factory(Article::class)->create();

		Article::$requires_approval = true;


		$new_title = 'New Title 1';
		$article->title = $new_title;
		$article->save();

		$article = Article::with(['versions'])->find($article->id);
		$this->assertEquals(1, count($article->versions));
		$this->assertEquals($new_title, $article->draft()->values['title']);


		$new_content = 'New Content';
		$article->content = $new_content;
		$article->save();

		$article = Article::with(['versions'])->find($article->id);
		$this->assertEquals(1, count($article->versions));
		$this->assertEquals($new_title, $article->draft()->values['title']);
		$this->assertEquals($new_content, $article->draft()->values['content']);


		$new_title = 'New Title 2';
		$article->title = $new_title;
		$article->save();

		$article = Article::with(['versions'])->find($article->id);
		$this->assertEquals(1, count($article->versions));
		$this->assertEquals($new_title, $article->draft()->values['title']);
		$this->assertEquals($new_content, $article->draft()->values['content']);
	}


	function testApprovingDraft()
	{
		$article = factory(Article::class)->create();

		Article::$requires_approval = true;

		$article->title = 'New Title';
		$article->save();
		
		Article::$requires_approval = false;

		$notes = 'Looks good';
		$article->draft()->approve($notes);

		$article = Article::with(['versions'])->find($article->id);
		$this->assertEquals(1, count($article->versions));

		$version = $article->versions->first();
		$this->assertEquals(Version::STATUS_APPROVED, $version->status);
		$this->assertEquals($notes, $version->notes);
	}


	function testRejectingDraft()
	{
		$article = factory(Article::class)->create();

		Article::$requires_approval = true;

		$article->title = 'New Title';
		$article->save();

		Article::$requires_approval = false;

		$notes = 'No good';
		$article->draft()->reject($notes);

		$article = Article::with(['versions'])->find($article->id);
		$this->assertEquals(1, count($article->versions));

		$version = $article->versions->first();
		$this->assertEquals(Version::STATUS_REJECTED, $version->status);
		$this->assertEquals($notes, $version->notes);
	}


	function testApplyingVersion()
	{
		$article = factory(Article::class)->create();

		Article::$requires_approval = true;

		$new_title = 'New Title';
		$article->title = $new_title;
		$article->save();

		Article::$requires_approval = false;

		$article->draft()->approve();

		$article = Article::with(['versions'])->find($article->id);
		$version = $article->versions->first();

		$version->apply();

		$article = Article::with(['versions'])->find($article->id);
		$version = $article->versions->first();
		$this->assertEquals(Version::STATUS_APPLIED, $version->status);
		$this->assertEquals($new_title, $article->title);
	}
}