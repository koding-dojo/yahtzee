<?php

namespace Yahtzee;

use Yahtzee\Exceptions\NoRollsRemainingException;
use Yahtzee\Exceptions\NoDiceToPlaceInCategoryException;

class Game
{
    /**
     * @var Roller
     */
    private Roller $roller;
    private array $dice = [];
    private int $rollsRemaining = 3;

    /**
     * Game constructor.
     *
     * @param Roller $roller
     */
    public function __construct(Roller $roller)
    {
        $this->roller = $roller;
    }

    public function roll(array $diceNumbers = [0,1,2,3,4]): array
    {
        if ($this->rollsRemaining <= 0) {
            throw new NoRollsRemainingException();
        }

        foreach ($diceNumbers as $diceNumber) {
            $this->dice[$diceNumber] = $this->roller->roll();
        }

        $this->rollsRemaining--;
        return $this->dice;
    }

    public function placeIn(Category $category)
    {
        if (empty($this->dice)) {
            throw new NoDiceToPlaceInCategoryException();
        }

        $score = 0;
        if ($category->equals(Category::chance())) {
            $score = array_sum($this->dice);
        } elseif ($category->equals(Category::yahtzee())) {
            $score = 1 === count(array_unique($this->dice)) ? 50 : 0;
        } elseif ($category->equals(Category::ones())) {
            $score = $this->sumXs(1);
        } elseif ($category->equals(Category::twos())) {
            $score = $this->sumXs(2);
        } elseif ($category->equals(Category::threes())) {
            $score = $this->sumXs(3);
        } elseif ($category->equals(Category::fours())) {
            $score = $this->sumXs(4);
        } elseif ($category->equals(Category::fives())) {
            $score = $this->sumXs(5);
        } elseif ($category->equals(Category::sixes())) {
            $score = $this->sumXs(6);
        } elseif ($category->equals(Category::pair())) {
            $score = $this->sumTopNPairs(1);
        } elseif ($category->equals(Category::twoPairs())) {
            $score = $this->sumTopNPairs(2);
        } elseif ($category->equals(Category::smallStraight())) {
            $score = empty(array_diff([1,2,3,4,5], $this->dice)) ? 15 : 0;
        } elseif ($category->equals(Category::largeStraight())) {
            $score = empty(array_diff([2,3,4,5,6], $this->dice)) ? 20 : 0;
        } elseif ($category->equals(Category::threeOfAKind())) {
            $score = $this->sumNOfAKind(3);
        } elseif ($category->equals(Category::fourOfAKind())) {
            $score = $this->sumNOfAKind(4);
        } elseif ($category->equals(Category::fullHouse())) {
            $score = empty(array_diff([2, 3], array_count_values($this->dice))) ? array_sum($this->dice) : 0;
        }

        $this->rollsRemaining = 3;
        $this->dice = [];
        return $score;
    }

    /**
     * @param int $x
     * @return int
     */
    private function sumXs(int $x)
    {
        return array_sum(array_filter($this->dice, fn($d) => $d === $x));
    }

    /**
     * @param int $n
     * @return int
     */
    private function sumTopNPairs(int $n): int
    {
        $dice = array_keys(array_filter(array_count_values($this->dice), fn($count) => $count > 1));
        if (count($dice) < $n) {
            return 0;
        }
        rsort($dice);
        return 2 * array_sum(array_slice($dice, 0, $n));
    }

    /**
     * @param int $n
     * @return int
     */
    private function sumNOfAKind(int $n)
    {
        return $n * (array_key_first(array_filter(array_count_values($this->dice), fn($count) => $count >= $n)) ?? 0);
    }
}
