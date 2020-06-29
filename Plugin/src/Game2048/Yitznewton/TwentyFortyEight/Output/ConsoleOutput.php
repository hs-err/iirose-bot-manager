<?php

namespace Yitznewton\TwentyFortyEight\Output;

use Yitznewton\TwentyFortyEight\Grid;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ConsoleOutput implements Output
{
    const CELL_WIDTH = 6;
    const GAME_TITLE = '2048';

    private $centerer;
    private $op;

    public function __construct()
    {
        $this->centerer = new TextCenterer(TextCenterer::RESOLVE_LEFT);
    }

    /**
     * @param Grid $grid
     * @param int $score
     * @return string
     */
    public function renderBoard(Grid $grid, $score)
    {
        $this->op='';
        $gridArray = $grid->toArray();

        $this->printHeader($gridArray, $score);
        $this->printGrid($gridArray);
        return $this->op;
    }

    /**
     * @param int $score
     * @return string
     */
    public function renderGameOver($score)
    {
        return "\n" . 'Good game! Your score was ' . $score . "\n\n";
    }

    /**
     * @param int $winningTileValue
     * @return string
     */
    public function renderWin($winningTileValue)
    {
        return 'YYYYEEEEEEAH! You got the %d tile!!!!';
    }

    private function printHeader($grid, $score)
    {
        $boardWidth = $this->calculateBoardWidth($grid);
        $scoreString = 'SCORE: ' . $score;

        $spacesCount = $boardWidth - strlen(self::GAME_TITLE) - strlen($scoreString);
        $spaces = str_repeat(' ', $spacesCount);

        $this->op.= $scoreString . $spaces . self::GAME_TITLE . "\n\n";
    }

    private function printGrid($grid)
    {
        $boardWidth = $this->calculateBoardWidth($grid);

        $this->printSolidLine($boardWidth);

        for ($i = 0; $i < count($grid); $i++) {
            $row = $grid[$i];
            $this->printRow($row);

            if (!$this->isLastRow($i, $grid)) {
                $this->printBlankLine($boardWidth);
            }
        }

        $this->printSolidLine($boardWidth);
    }

    private function printRow(array $row)
    {
        $this->op.= '|';

        foreach ($row as $cell) {
            $cellString = $this->cellToString($cell);
            $this->op.= $this->centerer->centerText($cellString, self::CELL_WIDTH);
        }

        $this->op.= "|\n";
    }

    private function printSolidLine($length)
    {
        $this->op.= '+' . str_repeat('-', $length - 2) . '+' . "\n";
    }

    private function printBlankLine($length)
    {
        $this->op.= '|' . str_repeat(' ', $length - 2) . '|' . "\n";
    }

    private function cellToString($cell)
    {
        if ($cell == Grid::EMPTY_CELL) {
            return '';
        }

        return $cell;
    }

    /**
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    private function isLastRow($i, $grid)
    {
        return $i == count($grid) - 1;
    }

    private function calculateBoardWidth($grid)
    {
        $combinedBorderWidth = 2;
        return count($grid) * self::CELL_WIDTH + $combinedBorderWidth;
    }

}
