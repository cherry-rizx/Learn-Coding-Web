<?php

use App\Database\Seeds\ChapterLevelSeeder;
use App\Models\LevelAttemptModel;
use App\Models\LevelModel;
use App\Models\QuestionModel;
use App\Models\QuestionOptionModel;
use App\Models\UserModel;
use App\Services\CurriculumService;
use App\Services\ProgressService;
use App\Services\QuizService;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Alur kuis, buka level, dan pembersihan tamu di database uji (SQLite in-memory).
 *
 * @internal
 */
final class GameFlowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null; // jalankan semua migration (Shield + App)
    protected $refresh   = true;
    protected $seed      = ChapterLevelSeeder::class;

    private int $userId;
    private array $level1;

    protected function setUp(): void
    {
        parent::setUp();

        $users = model(UserModel::class);
        $users->save(new User(['username' => null, 'active' => 1, 'is_guest' => 1]));
        $this->userId = (int) $users->getInsertID();
        $this->level1 = model(LevelModel::class)->where('level_no', 1)->orderBy('id')->first();

        // 12 soal pilihan ganda: opsi pertama selalu benar
        for ($i = 1; $i <= 12; $i++) {
            $qid = model(QuestionModel::class)->insert([
                'level_id' => $this->level1['id'], 'type' => 'mcq', 'prompt' => "Soal {$i}", 'clue' => 'clue', 'explanation' => 'penjelasan',
            ]);
            model(QuestionOptionModel::class)->insert(['question_id' => $qid, 'content' => 'benar', 'is_correct' => 1]);
            model(QuestionOptionModel::class)->insert(['question_id' => $qid, 'content' => 'salah', 'is_correct' => 0]);
        }
    }

    private function option(int $qid, int $correct): int
    {
        return (int) model(QuestionOptionModel::class)->where(['question_id' => $qid, 'is_correct' => $correct])->first()['id'];
    }

    private function freshAttempt(int $id): array
    {
        return model(LevelAttemptModel::class)->find($id);
    }

    public function testQuizStateHidesAnswerKey(): void
    {
        $quiz    = new QuizService();
        $attempt = $quiz->start($this->userId, $this->level1)['attempt'];
        $state   = $quiz->state($attempt, $this->level1);

        $this->assertSame(10, $state['total']);
        $this->assertArrayNotHasKey('clue', $state);
        $this->assertArrayNotHasKey('explanation', $state);

        foreach ($state['question']['options'] as $option) {
            $this->assertSame(['id', 'content'], array_keys($option));
        }
    }

    public function testQuizResumeKeepsSameOrder(): void
    {
        $quiz  = new QuizService();
        $first = $quiz->start($this->userId, $this->level1)['attempt'];
        $again = $quiz->start($this->userId, $this->level1)['attempt'];

        $this->assertSame($first['id'], $again['id']);
        $this->assertSame($first['question_order'], $again['question_order']);
    }

    public function testFullQuizWithMistakesGivesTwoStarsAndUnlocksNextLevel(): void
    {
        $quiz    = new QuizService();
        $attempt = $quiz->start($this->userId, $this->level1)['attempt'];
        $order   = json_decode($attempt['question_order'], true);

        foreach ($order as $i => $qid) {
            $attempt = $this->freshAttempt((int) $attempt['id']);

            if ($i < 2) {
                // Salah dulu (heart -1, clue), lalu benar dengan clue: tetap dihitung salah
                $wrong = $quiz->answer($attempt, $this->level1, $this->option($qid, 0));
                $this->assertSame('wrong', $wrong['result']);
                $right = $quiz->answer($this->freshAttempt((int) $attempt['id']), $this->level1, $this->option($qid, 1));
                $this->assertSame('correct_retry', $right['result']);
            } else {
                $result = $quiz->answer($attempt, $this->level1, $this->option($qid, 1));
                $this->assertSame('correct', $result['result']);
            }
        }

        $this->assertSame(2, $result['finished']['stars']);
        $this->assertSame(8, $result['finished']['correct']);

        $progress = (new ProgressService())->getProgress($this->userId, (int) $this->level1['id']);
        $this->assertSame(2, (int) $progress['best_stars']);
        $this->assertSame(3, (int) $progress['hearts_left']);

        $map = (new CurriculumService())->map($this->userId);
        $this->assertSame('passed', $map[0]['levels'][0]['state']);
        $this->assertTrue($map[0]['levels'][1]['unlocked']);
        $this->assertFalse($map[0]['levels'][2]['unlocked']);
        $this->assertFalse($map[1]['unlocked']);
    }

    public function testBestStarsOnlyAndPassedLevelsCountTowardTotal(): void
    {
        $progress = new ProgressService();
        $progress->recordStars($this->userId, (int) $this->level1['id'], 3);
        $progress->recordStars($this->userId, (int) $this->level1['id'], 2); // ulang dengan hasil lebih jelek

        $this->seeInDatabase('users', ['id' => $this->userId, 'total_stars' => 3]);

        $level2 = model(LevelModel::class)->where(['chapter_id' => $this->level1['chapter_id'], 'level_no' => 2])->first();
        $progress->recordStars($this->userId, (int) $level2['id'], 1); // gagal tidak dihitung

        $this->seeInDatabase('users', ['id' => $this->userId, 'total_stars' => 3]);
    }

    public function testNoHeartsBlocksNewSessionUntilRefill(): void
    {
        $progress = new ProgressService();
        $row      = $progress->getProgress($this->userId, (int) $this->level1['id']);
        model('UserLevelProgressModel')->update($row['id'], ['hearts_left' => 0, 'hearts_updated_at' => date('Y-m-d H:i:s')]);

        $this->assertArrayHasKey('blocked', (new QuizService())->start($this->userId, $this->level1));
    }

    public function testGuestCleanupDeletesInactiveGuestWithChildRows(): void
    {
        (new ProgressService())->getProgress($this->userId, (int) $this->level1['id']);
        $this->db->table('users')->where('id', $this->userId)->update(['last_active' => date('Y-m-d H:i:s', strtotime('-31 days'))]);

        command('guest:cleanup');

        $this->dontSeeInDatabase('users', ['id' => $this->userId]);
        $this->dontSeeInDatabase('user_level_progress', ['user_id' => $this->userId]); // ikut terhapus (CASCADE)
    }
}
