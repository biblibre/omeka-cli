<?php

namespace OmekaCli\Console;

class Prompt
{
    public function menu($text, $options)
    {
        do {
            $i = 0;
            foreach ($options as $option) {
                fwrite(STDERR, "[$i] $option\n");
                ++$i;
            }
            $max = $i - 1;
            fwrite(STDERR, "$text [0-$max,q] ");
            $ans = trim(fgets(STDIN));
        } while ((!is_numeric($ans) || $ans < 0 || $ans > $max) && $ans != 'q');

        return $ans != 'q' ? $ans : -1;
    }
}
