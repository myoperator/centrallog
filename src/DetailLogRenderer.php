<?php
namespace MyOperator;

use \LoggerRenderer;
class DetailLogRenderer implements LoggerRenderer {
    public function render($input) {
        if(is_array($input)) {
            $input = json_encode($input);
        }
        if($input instanceof \Exception) {
            $input = array('kind' => 'exception', 'message'=> $input->getMessage(), 'code' => $input->getCode());
            $input = json_encode($input);
        }

        try {
            return (string) $input;
        } catch (\Exception $e) {
            return $input;
        }
    }
}
