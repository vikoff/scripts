<?php

class Colors {

    protected $_fgColors = array(
        'black' => '0;30',
        'dark_gray' => '1;30',
        'blue' => '0;34',
        'light_blue' => '1;34',
        'green' => '0;32',
        'light_green' => '1;32',
        'cyan' => '0;36',
        'light_cyan' => '1;36',
        'red' => '0;31',
        'light_red' => '1;31',
        'purple' => '0;35',
        'light_purple' => '1;35',
        'brown' => '0;33',
        'yellow' => '1;33',
        'light_gray' => '0;37',
        'white' => '1;37',
    );
    protected $_bgColors = array(
        'black'      => '40',
        'red'        => '41',
        'green'      => '42',
        'yellow'     => '43',
        'blue'       => '44',
        'magenta'    => '45',
        'cyan'       => '46',
        'light_gray' => '47',
    );

    // Returns colored string
    public function getColoredString($string, $fg = null, $bg = null) {
        
        $colored_string = "";
        
        if (isset($this->_fgColors[$fg])) {
            $colored_string .= "\033[" . $this->_fgColors[$fg] . "m";
        }
        if (isset($this->_bgColors[$bg])) {
            $colored_string .= "\033[" . $this->_bgColors[$bg] . "m";
        }

        $tpl = $colored_string ."%s\033[0m";

        $output = sprintf($tpl, $string)
            .' '.sprintf(str_replace("\033", '\033', $tpl), ' text ');

        return $output;
    }

    public function printAll() {


        foreach ($this->_fgColors as $color => $code) {
            echo $this->getColoredString("test fg $color ($code)", $color).PHP_EOL.PHP_EOL;
        }

        foreach ($this->_bgColors as $color => $code) {
            echo $this->getColoredString(" test bg $color ($code) ", null, $color).PHP_EOL.PHP_EOL;
        }
    }
}

$colors = new Colors;
$colors->printAll();

