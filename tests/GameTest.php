<?php

namespace Tests;

use Yahtzee\Game;
use Yahtzee\Roller;
use Yahtzee\Category;
use PHPUnit\Framework\TestCase;
use Yahtzee\Exceptions\NoRollsRemainingException;
use Yahtzee\Exceptions\NoDiceToPlaceInCategoryException;

class GameTest extends TestCase
{
    public function testCanRoll()
    {
        // Arrange
        $game = new Game(new Roller());

        // Act
        $dice = $game->roll();

        // Assert
        self::assertCount(5, $dice);
        self::assertCount(0, array_filter($dice, fn($d) => $d < 1 || $d > 6));
    }

    public function testCanHoldSomeDiceOnTheSecondRoll()
    {
        // Arrange
        $roller = self::createStub(Roller::class);
        $roller->method('roll')->willReturnOnConsecutiveCalls(
            3,4,5,5,2,
                    5,1,    3
        );
        $game = new Game($roller);
        $game->roll();

        // Act
        $dice = $game->roll([0,1,4]);

        // Assert
        self::assertEquals([5,1,5,5,3], $dice);
    }

    public function testCannotRollMoreThanThreeTimesInOneRound()
    {
        // Arrange
        $game = new Game(new Roller());
        self::expectException(NoRollsRemainingException::class);

        // Act
        $game->roll();
        $game->roll();
        $game->roll();
        $game->roll();
    }

    public function testCannotPlaceInCategoryBeforeRolling()
    {
        // Arrange
        $game = new Game(new Roller());
        self::expectException(NoDiceToPlaceInCategoryException::class);

        // Act
        $game->placeIn(Category::chance());
    }

    /**
     * @dataProvider placeInCategoryTestCases
     * @param array    $dice
     * @param Category $category
     * @param int      $score
     * @throws NoDiceToPlaceInCategoryException
     * @throws NoRollsRemainingException
     */
    public function testCanPlaceDiceInCategory(array $dice, Category $category, int $score)
    {
        // Arrange
        $roller = self::createStub(Roller::class);
        $roller->method('roll')->willReturnOnConsecutiveCalls(...$dice);
        $game = new Game($roller);
        $game->roll();

        // Assert
        self::assertEquals($score, $game->placeIn($category));
    }

    public function testShouldResetRoundAfterPlaceInCategory()
    {
        // Arrange
        $game = new Game(new Roller());
        $game->roll();
        $game->roll();
        $game->roll();
        $game->placeIn(Category::chance());

        // Act
        $dice = $game->roll();

        // Assert
        self::assertCount(5, $dice);
    }

    public function placeInCategoryTestCases()
    {
        return [
            'chance  12345' => [[1,2,3,4,5], Category::chance(), 15],
            'yahtzee 11111' => [[1,1,1,1,1], Category::yahtzee(), 50],
            'yahtzee 11112' => [[1,1,1,1,2], Category::yahtzee(), 0],
            'ones    11234' => [[1,1,2,3,4], Category::ones(), 2],
            'ones    23456' => [[2,3,4,5,6], Category::ones(), 0],
            'twos    23452' => [[2,3,4,5,2], Category::twos(), 4],
            'twos    13456' => [[1,3,4,5,6], Category::twos(), 0],
            'threes  34343' => [[3,4,3,4,3], Category::threes(), 9],
            'threes  12421' => [[1,2,4,2,1], Category::threes(), 0],
            'fours  11244' => [[1,1,2,4,4], Category::fours(), 8],
            'fours  12321' => [[1,2,3,2,1], Category::fours(), 0],
            'fives  12345' => [[1,2,3,4,5], Category::fives(), 5],
            'fives  42132' => [[4,2,1,3,2], Category::fives(), 0],
            'sixes  66666' => [[6,6,6,6,6], Category::sixes(), 30],
            'sixes  12345' => [[1,2,3,4,5], Category::sixes(), 0],
            'pair   33344' => [[3,3,3,4,4], Category::pair(), 8],
            'pair   33341' => [[3,3,3,4,1], Category::pair(), 6],
            'pair   11626' => [[1,1,6,2,6], Category::pair(), 12],
            'pair   33331' => [[3,3,3,3,1], Category::pair(), 6],
            '2pairs 11233' => [[1,1,2,3,3], Category::twoPairs(), 8],
            '2pairs 11234' => [[1,1,2,3,4], Category::twoPairs(), 0],
            '2pairs 11222' => [[1,1,2,2,2], Category::twoPairs(), 6],
            '3 of x 33345' => [[3,3,3,4,5], Category::threeOfAKind(), 9],
            '3 of x 33456' => [[3,3,4,5,6], Category::threeOfAKind(), 0],
            '3 of x 33335' => [[3,3,3,3,5], Category::threeOfAKind(), 9],
            '4 of x 33335' => [[3,3,3,3,5], Category::fourOfAKind(), 12],
            '4 of x 33355' => [[3,3,3,5,5], Category::fourOfAKind(), 0],
            '4 of x 33333' => [[3,3,3,3,3], Category::fourOfAKind(), 12],
            'full   22233' => [[2,2,2,3,3], Category::fullHouse(), 12],
            'full   12233' => [[1,2,2,3,3], Category::fullHouse(), 0],
            'full   22222' => [[2,2,2,2,2], Category::fullHouse(), 0],
            's straight 12345' => [[1,2,3,4,5], Category::smallStraight(), 15],
            's straight 23456' => [[2,3,4,5,6], Category::smallStraight(), 0],
            'L straight 12345' => [[1,2,3,4,5], Category::largeStraight(), 0],
            'L straight 23456' => [[2,3,4,5,6], Category::largeStraight(), 20],
        ];
    }

}
