<?php
namespace AcMoore\Approvable\Tests\Functional;

use Illuminate\Support\Facades\Event;

use AcMoore\Approvable\Tests\ApprovableTestCase;
use AcMoore\Approvable\Tests\Events\AppliedEvent;
use AcMoore\Approvable\Tests\Events\ApprovedEvent;
use AcMoore\Approvable\Tests\Events\NewDraftEvent;
use AcMoore\Approvable\Tests\Events\RejectedEvent;
use AcMoore\Approvable\Tests\Models\Article;
use AcMoore\Approvable\Tests\Models\User;
use AcMoore\Approvable\Models\Version;


class ApprovableTest extends ApprovableTestCase
{

	function testNotCreatingDraft()
	{
		Event::fake([
			NewDraftEvent::class,
		]);

        $article = factory(Article::class)->create();

		$new_title = 'New Title';
		$article->title = $new_title;
		$article->save();

		$article = Article::with(['versions.user'])->find($article->id);

		$this->assertEquals($new_title, $article->title);
		$this->assertEquals(0, count($article->versions));

		// Check event was not fired
		Event::assertNotDispatched(NewDraftEvent::class);
	}


	function testCreatingDraftForExisting()
	{
		Event::fake([
			NewDraftEvent::class,
		]);

        $user = factory(User::class)->create();
        $article = factory(Article::class)->create();


		$this->be($user);


		Article::enableApproval();

		$new_title = 'New Title';
		$article->title = $new_title;
		$article->published_at = new \DateTime();
		$article->save();

		Article::disableApproval();

		$article = Article::with(['versions.user'])->find($article->id);

		$this->assertNotEquals($new_title, $article->title);

		$this->assertEquals(1, count($article->versions));

		$draft = $article->draft;

		$this->assertEquals($new_title, $draft->values['title']);
		$this->assertEquals($user->id, $draft->user->id);

		// Test that only changed content is drafted
		$this->assertArrayNotHasKey('content', $draft->values);

		// Test that only fields marked as approvable are drafted
		$this->assertArrayNotHasKey('published_at', $draft->values);

		// Check event was fired
		Event::assertDispatched(NewDraftEvent::class);
	}


	function testCreatingDraftForNew()
	{
		Event::fake([
			NewDraftEvent::class,
		]);

        $user = factory(User::class)->create();
        $article = factory(Article::class)->make();


		$this->be($user);


		Article::enableApproval();

		$article->save();

		Article::disableApproval();

		$article = Article::with(['versions.user'])->find($article->id);

		$this->assertEquals(1, count($article->versions));

		$draft = $article->draft;

		$this->assertEquals($user->id, $draft->user->id);

		// Test that content is drafted
		$this->assertArrayHasKey('content', $draft->values);

		// Test that article is not approved
		$this->assertNull($article->approved_at);

		// Check event was fired
		Event::assertDispatched(NewDraftEvent::class);
	}


	function testAnonymousDraft()
	{
		Event::fake([
			NewDraftEvent::class,
		]);

        $article = factory(Article::class)->create();

		Article::enableApproval();

		$new_title = 'New Title';
		$article->title = $new_title;
		$article->published_at = new \DateTime();
		$article->save();

		Article::disableApproval();

		$article = Article::with(['versions.user'])->find($article->id);

		$this->assertNotEquals($new_title, $article->title);
		$this->assertEquals(1, count($article->versions));

		$draft = $article->draft;

		$this->assertEquals($new_title, $draft->values['title']);
		$this->assertNull($draft->user);

		// Test that article is not approved
		$this->assertNull($article->approved_at);

		// Check event was fired
		Event::assertDispatched(NewDraftEvent::class);
	}


	function testNotCreatingDraftWhenNoChanges()
	{
		Event::fake([
			NewDraftEvent::class,
		]);

        $article = factory(Article::class)->create();

		Article::enableApproval();

		$published_at = $this->faker->dateTime();

		$article->published_at = $published_at; // Should not require approval
		$article->save();

		Article::disableApproval();

		$article = Article::with(['versions.user'])->find($article->id);

		$this->assertEquals($published_at, $article->published_at);
		$this->assertEquals(0, count($article->versions));

		// Check event was not fired
		Event::assertNotDispatched(NewDraftEvent::class);
	}


	function testUpdatingDraft()
	{
		Event::fake([
			NewDraftEvent::class,
		]);

		$article = factory(Article::class)->create();

		Article::enableApproval();


		$new_title = 'New Title 1';
		$article->title = $new_title;
		$article->save();

		$article = Article::with(['versions'])->find($article->id);
		$this->assertEquals(1, count($article->versions));
		$this->assertEquals($new_title, $article->draft->values['title']);


		$new_content = 'New Content';
		$article->content = $new_content;
		$article->save();

		$article = Article::with(['versions'])->find($article->id);
		$this->assertEquals(1, count($article->versions));
		$this->assertEquals($new_title, $article->draft->values['title']);
		$this->assertEquals($new_content, $article->draft->values['content']);


		$new_title = 'New Title 2';
		$article->title = $new_title;
		$article->save();
		$article = Article::with(['versions', 'draft'])->find($article->id);

		$this->assertEquals(1, count($article->versions));
		$this->assertEquals($new_title, $article->draft->values['title']);
		$this->assertEquals($new_content, $article->draft->values['content']);

		Article::disableApproval();

		// Check event was fired
		Event::assertDispatched(NewDraftEvent::class);
	}


	function testApprovingDraft()
	{
		Event::fake([
			ApprovedEvent::class,
		]);

		$article = factory(Article::class)->create();

		Article::enableApproval();

		$article->title = 'New Title';
		$article->save();
		$article->load('draft');

		Article::disableApproval();

		$notes = 'Looks good';
		$article->draft->approve($notes);

		$article = Article::with(['versions', 'draft'])->find($article->id);
		$this->assertEquals(1, count($article->versions));

		$version = $article->versions->first();
		$this->assertEquals(Version::STATUS_APPROVED, $version->status);
		$this->assertEquals($notes, $version->notes);

		// Test that article is now set as approved
		$this->assertNotNull($article->approved_at);

		// Check event was fired
		Event::assertDispatched(ApprovedEvent::class);
	}


	function testRejectingDraft()
	{
		Event::fake([
			RejectedEvent::class,
		]);

		$article = factory(Article::class)->create();

		Article::enableApproval();

		$article->title = 'New Title';
		$article->save();
		$article->load('draft');

		Article::disableApproval();

		$notes = 'No good';
		$article->draft->reject($notes);

		$article = Article::with(['versions'])->find($article->id);
		$this->assertEquals(1, count($article->versions));

		$version = $article->versions->first();
		$this->assertEquals(Version::STATUS_REJECTED, $version->status);
		$this->assertEquals($notes, $version->notes);

		// Check event was fired
		Event::assertDispatched(RejectedEvent::class);
	}


	function testApplyingVersion()
	{
		Event::fake([
			AppliedEvent::class,
		]);

		$article = factory(Article::class)->create();

		Article::enableApproval();

		$new_title = 'New Title';
		$article->title = $new_title;
		$article->save();
		$article->load('draft');

		Article::disableApproval();

		$article->draft->approve();

		$article = Article::with(['versions'])->find($article->id);
		$version = $article->versions->first();

		$version->apply();

		$article = Article::with(['versions'])->find($article->id);
		$version = $article->versions->first();
		$this->assertEquals(Version::STATUS_APPLIED, $version->status);
		$this->assertEquals($new_title, $article->title);

		// Check event was fired
		Event::assertDispatched(AppliedEvent::class);
	}


	function testPreviewingVersion()
	{
		$article = factory(Article::class)->create();

		Article::enableApproval();

		$new_title = uniqid();

		$article->title = $new_title; // Should not require approval
		$article->save();

		Article::disableApproval();

		$article = Article::with(['draft.approvable'])->find($article->id);

		$draft_preview = $article->draft->preview;

		$this->assertInstanceOf(Article::class, $draft_preview);
		$this->assertEquals($new_title, $draft_preview->title);
	}
}