<?php

namespace Yahtzee;

class Roller
{
    public function roll(): int
    {
        return rand(1,6);
    }
}
