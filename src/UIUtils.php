<?php

namespace OmekaCli;

class UIUtils
{
    public static function confirmPrompt($text)
    {
        do {
            echo "$text [y,n] ";
            $ans = trim(fgets(STDIN));
        } while ($ans != 'y' && $ans != 'n');

        return $ans == 'y' ? true : false;
    }

    public static function menuPrompt($text, $options)
    {
        do {
            $i = 0;
            foreach ($options as $option) {
                echo "[$i] $option\n";
                ++$i;
            }
            $max = $i - 1;
            echo "$text [0-$max,q] ";
            $ans = trim(fgets(STDIN));
        } while ((!is_numeric($ans) || $ans < 0 || $ans > $max) &&
                 $ans != 'q');

        return $ans != 'q' ? $ans : null;
    }
}
