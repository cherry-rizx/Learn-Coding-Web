<?php

use App\Services\AiTutorService;
use App\Services\ProgressService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Game;

/**
 * Aturan main murni (tanpa database): bintang, hearts, pembatas kode AI.
 *
 * @internal
 */
final class GameRulesTest extends CIUnitTestCase
{
    private ProgressService $progress;
    private Game $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config                     = new Game();
        $this->config->heartRefillSeconds = 300;
        $this->progress                   = new ProgressService($this->config);
    }

    public function testQuizStarsThreshold(): void
    {
        $this->assertSame(3, $this->progress->quizStars(10));
        $this->assertSame(3, $this->progress->quizStars(9));
        $this->assertSame(2, $this->progress->quizStars(8));
        $this->assertSame(2, $this->progress->quizStars(7));
        $this->assertSame(1, $this->progress->quizStars(6));
        $this->assertSame(1, $this->progress->quizStars(0));
    }

    public function testBossStarsBySubmitNumber(): void
    {
        $this->assertSame(3, $this->progress->bossStars(1));
        $this->assertSame(2, $this->progress->bossStars(2));
        $this->assertSame(2, $this->progress->bossStars(3));
        $this->assertFalse($this->progress->bossCycleFailed(3));
        $this->assertTrue($this->progress->bossCycleFailed(4));
    }

    public function testHeartsRefillKeepsLeftoverTime(): void
    {
        $now = strtotime('2026-10-06 10:00:00');

        // 2 hearts, lewat 7 menit: +1 heart, sisa 2 menit tetap terhitung
        $state = $this->progress->currentHearts(2, '2026-10-06 09:53:00', $now);
        $this->assertSame(3, $state['hearts']);
        $this->assertSame(strtotime('2026-10-06 09:58:00'), $state['since']);
        $this->assertSame(180, $this->progress->secondsToNextHeart(2, '2026-10-06 09:53:00', $now));
    }

    public function testHeartsNeverExceedMax(): void
    {
        $now   = strtotime('2026-10-06 10:00:00');
        $state = $this->progress->currentHearts(1, '2026-10-05 10:00:00', $now);

        $this->assertSame(5, $state['hearts']);
        $this->assertSame(0, $this->progress->secondsToNextHeart(1, '2026-10-05 10:00:00', $now));
    }

    public function testSpendHeartsByLevelType(): void
    {
        $full = ['hearts_left' => 5, 'hearts_updated_at' => null];

        $this->assertSame(4, $this->progress->spendHearts($full, 'quiz')['hearts_left']);
        $this->assertSame(3, $this->progress->spendHearts($full, 'mini_boss')['hearts_left']);
        $this->assertSame(2, $this->progress->spendHearts($full, 'final_boss')['hearts_left']);
        $this->assertSame(0, $this->progress->spendHearts(['hearts_left' => 1, 'hearts_updated_at' => null], 'final_boss')['hearts_left']);
    }

    public function testAiManualHintRemovesAllCode(): void
    {
        $ai   = new AiTutorService($this->config);
        $text = "Cek bagian ini:\n```html\n<h1>x</h1>\n```\nselesai";

        $this->assertStringNotContainsString('<h1>', $ai->limitCode($text, 0));
        $this->assertStringContainsString('[potongan kode disembunyikan]', $ai->limitCode($text, 0));
    }

    public function testAiAutoHintKeepsShortCodeOnly(): void
    {
        $ai    = new AiTutorService($this->config);
        $short = "```js\nlet a = 1;\n```";
        $long  = "```js\n" . str_repeat("let a = 1;\n", 60) . '```';

        $this->assertSame($short, $ai->limitCode($short, 400));
        $this->assertSame('[potongan kode disembunyikan]', $ai->limitCode($long, 400));
    }

    public function testAiFallbackWhenServerUnreachable(): void
    {
        $this->config->aiBaseUrl = 'http://127.0.0.1:1/v1'; // port tertutup
        $this->config->aiTimeout = 2;

        $this->assertNull((new AiTutorService($this->config))->ask('sistem', 'user'));
    }
}
