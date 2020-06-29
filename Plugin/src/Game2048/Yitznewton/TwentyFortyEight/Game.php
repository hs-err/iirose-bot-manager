<?php

namespace Yitznewton\TwentyFortyEight;

use Yitznewton\TwentyFortyEight\Move\ImpossibleMoveException;
use Yitznewton\TwentyFortyEight\Move\MoveMaker;
use Yitznewton\TwentyFortyEight\Move\Scorer;
use Yitznewton\TwentyFortyEight\Output\Output;

class Game
{
    private $size;
    private $winningTile;
    private $output;
    private $scorer;
    /**
     * @var MoveMaker
     */
    private $moveMaker;
    /**
     * @var Grid
     */
    private $grid;
    /**
     * @var bool
     */
    public $over=false;

    /**
     * @param int $size
     * @param int $winningTile
     * @param Output $output
     */
    public function __construct($size, $winningTile, Output $output)
    {
        $this->size = $size;
        $this->winningTile = $winningTile;
        $this->output = $output;
        $this->scorer = new Scorer();
    }

    public function run()
    {
        $this->grid = $this->createGrid($this->size);
        $this->grid = $this->injectRandom($this->grid, 2);

        $this->output->renderBoard($this->grid, $this->scorer->getScore());

        $this->moveMaker = $this->getMoveMaker($this->grid);
    }
    public function go($move){
        $message='';
        if ($this->moveMaker->hasPossibleMoves()) {
            try {
                $this->grid = $this->takeTurn($this->grid, $move, $this->moveMaker);
            } catch (ImpossibleMoveException $e) {
                // ignore
                return '这步不存在';
            }

            $message.= $this->output->renderBoard($this->grid, $this->scorer->getScore());

            if ($this->hasWinningTile($this->grid)) {
                $message.= $this->output->renderWin($this->winningTile);
                return $message;
            }

            $this->moveMaker = $this->getMoveMaker($this->grid);
        }else{
            $message.= $this->output->renderGameOver($this->scorer->getScore());
            $this->over=true;
        }
        return $message;
    }
    private function injectRandom($grid, $numberOfCells)
    {
        $randomChoices = [2,4];

        for ($i = 0; $i < $numberOfCells; $i++) {
            $randomNumber = $randomChoices[rand(0, 1)];
            $grid = $grid->replaceRandom(Grid::EMPTY_CELL, $randomNumber);
        }

        return $grid;
    }

    /**
     * @param int $size
     * @return Grid
     */
    private function createGrid($size)
    {
        return Grid::fromArray(array_map(function () use ($size) {
            return array_fill(0, $size, Grid::EMPTY_CELL);
        }, range(0, $size-1)));
    }


    private function hasWinningTile($grid)
    {
        return $grid->reduce(function ($carry, $cell) {
            return $carry || $cell >= $this->winningTile;
        }, false);
    }

    private function takeTurn($grid, $move, $moveMaker)
    {
        $grid = $moveMaker->makeMove($move);
        $grid = $this->injectRandom($grid, 1);

        return $grid;
    }

    private function getMoveMaker($grid)
    {
        $moveMaker = new MoveMaker($grid);
        $moveMaker->addListener($this->scorer);
        return $moveMaker;
    }
}
