<?php

namespace Yitznewton\TwentyFortyEight\Output;

use Yitznewton\TwentyFortyEight\Grid;

interface Output
{
    /**
     * @param Grid $grid
     * @param int $score
     * @return string
     */
    public function renderBoard(Grid $grid, $score);

    /**
     * @param int $score
     * @return string
     */
    public function renderGameOver($score);

    /**
     * @param int $winningTileValue
     * @return string
     */
    public function renderWin($winningTileValue);
}
