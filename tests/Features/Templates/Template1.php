<?php

class Test
{
    public function outputHelloWorld(array $arguments)
    {
        ob_start();
        echo "Hello World!\n";

        if (isset($arguments['value']) && $arguments['value'] === true) {
            echo "value is true\n";
        }

        for ($i = 0; $i < 100; $i++) {
            echo $i;
        }
        return ob_get_clean();
    }
}